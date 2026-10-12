<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Customer;
use App\Models\ImportLog;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Asset::class);
        $query = Asset::with(['customer', 'assignee'])
            ->where('tenant_id', $this->tenantId())->latest();

        foreach (['status', 'type', 'customer_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('asset_tag', 'like', $term)
                ->orWhere('serial_number', 'like', $term)->orWhere('computer_name', 'like', $term)
                ->orWhere('manufacturer', 'like', $term)->orWhere('model', 'like', $term));
        }
        if ($request->input('warranty') === 'expiring') {
            $query->whereNotNull('warranty_expires_at')
                ->whereDate('warranty_expires_at', '<=', now()->addDays(30));
        }

        return view('assets.index', [
            'assets' => $query->paginate(15)->withQueryString(),
            'customers' => Customer::where('tenant_id', $this->tenantId())->orderBy('company_name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Asset::class);

        return view('assets.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Asset::class);
        $limit = currentTenant()?->activeSubscription?->plan?->asset_limit;
        if ($limit && Asset::where('tenant_id', $this->tenantId())->count() >= $limit) {
            return back()->with('error', 'Asset limit reached for your plan. Upgrade to add more assets.');
        }
        $asset = Asset::create($this->validateAsset($request) + ['tenant_id' => $this->tenantId()]);
        $this->history($asset, 'created', 'Asset registered.', $request->user()->id);
        audit('asset.created', $asset);

        return redirect()->route('assets.show', $asset)->with('success', "Asset {$asset->asset_tag} created.");
    }

    public function show(Asset $asset)
    {
        $this->authorize('view', $asset);
        $asset->load(['customer', 'location', 'assignee', 'history', 'tickets.customer']);

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        $this->authorize('update', $asset);

        return view('assets.edit', ['asset' => $asset] + $this->formData());
    }

    public function update(Request $request, Asset $asset)
    {
        $this->authorize('update', $asset);
        $before = $asset->only(['status', 'customer_id', 'assigned_to_user_id', 'location_id']);
        $asset->fill($this->validateAsset($request))->save();
        $after = $asset->only(array_keys($before));
        if ($before !== $after) {
            $action = ($before['status'] ?? null) !== ($after['status'] ?? null) ? 'status_changed' : 'transferred';
            $this->history($asset, $action, 'Changed: '.json_encode(array_diff_assoc($after, $before)), $request->user()->id);
        }
        audit('asset.updated', $asset);

        return redirect()->route('assets.show', $asset)->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset)
    {
        $this->authorize('delete', $asset);
        abort_if($asset->tickets()->whereIn('status', \App\Models\Ticket::OPEN_STATUSES)->exists(), 422, 'Asset has open tickets.');
        $asset->delete();
        audit('asset.archived', $asset);

        return redirect()->route('assets.index')->with('success', 'Asset archived.');
    }

    public function note(Request $request, Asset $asset)
    {
        $this->authorize('update', $asset);
        $request->validate(['details' => 'required|string|max:2000']);
        $this->history($asset, 'maintenance', $request->input('details'), $request->user()->id);

        return back()->with('success', 'Maintenance record added.');
    }

    public function export()
    {
        $this->authorize('viewAny', Asset::class);
        $rows = Asset::where('tenant_id', $this->tenantId())->with('customer')->orderBy('asset_tag')
            ->cursor()->map(fn ($a) => [
                $a->asset_tag, $a->name, $a->type, $a->manufacturer, $a->model,
                $a->serial_number, $a->computer_name, $a->operating_system,
                $a->customer?->company_name, $a->status,
                $a->purchase_date?->format('Y-m-d'), $a->warranty_expires_at?->format('Y-m-d'),
            ]);

        return $this->csvDownload('assets-'.now()->format('Ymd').'.csv',
            ['Tag', 'Name', 'Type', 'Manufacturer', 'Model', 'Serial', 'Computer', 'OS', 'Customer', 'Status', 'Purchased', 'Warranty expires'], $rows);
    }

    public function import(Request $request)
    {
        $this->authorize('create', Asset::class);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $rows = array_map('str_getcsv', file($request->file('file')->getRealPath()));
        $header = array_map(fn ($h) => strtolower(trim($h)), array_shift($rows));
        abort_if(count($rows) > config('supportflow.import.max_rows', 2000), 422, 'Import exceeds row limit.');

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $log = ImportLog::create(['tenant_id' => $this->tenantId(), 'imported_by' => $request->user()->id,
            'entity' => 'assets', 'original_filename' => $request->file('file')->getClientOriginalName(), 'rows_total' => count($rows)]);

        DB::transaction(function () use ($rows, $header, &$imported, &$skipped, &$errors) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;
                $data = array_combine($header, array_pad($row, count($header), null));
                $name = trim($data['name'] ?? '');
                if ($name === '') {
                    $skipped++;
                    $errors[] = "Row {$line}: name is required.";

                    continue;
                }
                $serial = trim($data['serial_number'] ?? $data['serial'] ?? '');
                if ($serial !== '' && Asset::where('tenant_id', $this->tenantId())->where('serial_number', $serial)->exists()) {
                    $skipped++;
                    $errors[] = "Row {$line}: serial '{$serial}' already exists — skipped.";

                    continue;
                }
                Asset::create([
                    'tenant_id' => $this->tenantId(), 'name' => $name,
                    'type' => $data['type'] ?? 'computer',
                    'manufacturer' => $data['manufacturer'] ?? null,
                    'model' => $data['model'] ?? null, 'serial_number' => $serial ?: null,
                    'status' => in_array($data['status'] ?? '', array_keys(config('supportflow.asset_statuses'))) ? $data['status'] : 'in_stock',
                ]);
                $imported++;
            }
        });

        $log->update(['rows_imported' => $imported, 'rows_skipped' => $skipped, 'errors' => array_slice($errors, 0, 100)]);

        return back()->with('success', "Import complete: {$imported} imported, {$skipped} skipped.");
    }

    // ---- internals ----

    protected function validateAsset(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'asset_tag' => 'nullable|string|max:50',
            'type' => 'required|string|max:50', 'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255', 'serial_number' => 'nullable|string|max:255',
            'computer_name' => 'nullable|string|max:255', 'operating_system' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:100', 'ip_address' => 'nullable|ip',
            'customer_id' => 'nullable|exists:customers,id', 'location_id' => 'nullable|exists:locations,id',
            'assigned_to_user_id' => 'nullable|exists:users,id', 'department' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date', 'supplier' => 'nullable|string|max:255',
            'purchase_cost' => 'nullable|numeric|min:0', 'warranty_expires_at' => 'nullable|date|after:purchase_date',
            'status' => 'required|in:in_stock,assigned,in_repair,retired,disposed',
            'notes' => 'nullable|string|max:5000',
        ]);
        // Tenant-scope guard for linked records.
        foreach (['customer_id' => Customer::class, 'location_id' => Location::class] as $field => $model) {
            if (! empty($data[$field])) {
                abort_unless($model::where('tenant_id', $this->tenantId())->where('id', $data[$field])->exists(), 422, 'Invalid reference.');
            }
        }

        return $data;
    }

    protected function formData(): array
    {
        return [
            'customers' => Customer::where('tenant_id', $this->tenantId())->orderBy('company_name')->get(),
            'locations' => Location::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'users' => User::where('tenant_id', $this->tenantId())->whereIn('role', User::STAFF_ROLES)->orderBy('name')->get(),
        ];
    }

    protected function history(Asset $asset, string $action, string $details, ?int $actorId): void
    {
        AssetHistory::create(['tenant_id' => $asset->tenant_id, 'asset_id' => $asset->id,
            'actor_id' => $actorId, 'action' => $action, 'details' => $details]);
    }
}
