<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);
        $query = Customer::with(['primaryContact', 'owner'])
            ->where('tenant_id', $this->tenantId())->latest();

        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('company_name', 'like', $term)->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)->orWhere('account_code', 'like', $term);
            });
        }
        if ($request->filled('status')) {
            $archived = $request->input('status') === 'archived';
            $archived ? $query->onlyTrashed()->orWhere('status', 'archived') : $query->where('status', $request->input('status'));
        }

        return view('customers.index', ['customers' => $query->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', ['owners' => $this->staffList(), 'policies' => $this->slaPolicies()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);
        $data = $this->validateCustomer($request);
        $customer = Customer::create($data + ['tenant_id' => $this->tenantId()]);
        audit('customer.created', $customer);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);
        $customer->load(['contacts', 'locations', 'owner', 'slaPolicy',
            'tickets' => fn ($q) => $q->latest()->limit(10),
            'assets' => fn ($q) => $q->limit(10),
            'invoices' => fn ($q) => $q->latest()->limit(10)]);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        return view('customers.edit', ['customer' => $customer, 'owners' => $this->staffList(), 'policies' => $this->slaPolicies()]);
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $customer->fill($this->validateCustomer($request, $customer->id))->save();
        audit('customer.updated', $customer);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        $customer->status = 'archived';
        $customer->save();
        $customer->delete(); // soft delete retains history
        audit('customer.archived', $customer);

        return redirect()->route('customers.index')->with('success', 'Customer archived.');
    }

    public function restore(int $id)
    {
        $customer = Customer::withoutTenantScope()->where('tenant_id', $this->tenantId())->withTrashed()->findOrFail($id);
        $this->authorize('update', $customer);
        $customer->restore();
        $customer->status = 'active';
        $customer->save();

        return redirect()->route('customers.show', $customer)->with('success', 'Customer restored.');
    }

    // ---- Contacts ----

    public function storeContact(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $data = $request->validate([
            'first_name' => 'required|string|max:255', 'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:255', 'department' => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean', 'portal_access' => 'nullable|boolean',
            'create_portal_user' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($customer, $data) {
            if (! empty($data['is_primary'])) {
                $customer->contacts()->update(['is_primary' => false]);
            }
            $contact = $customer->contacts()->create($data + ['tenant_id' => $this->tenantId()]);

            // Optionally provision a customer-portal login for this contact.
            if (! empty($data['create_portal_user']) && ! empty($data['email'])) {
                abort_if(User::where('email', $data['email'])->exists(), 422, 'A user with this email already exists.');
                User::create([
                    'name' => $contact->fullName(), 'email' => $data['email'],
                    'password' => Hash::make(str()->random(24)),
                    'tenant_id' => $this->tenantId(), 'role' => 'customer',
                    'customer_id' => $customer->id,
                ]);
            }
            audit('contact.created', $contact);

            return back()->with('success', 'Contact added.');
        });
    }

    public function destroyContact(Customer $customer, Contact $contact)
    {
        $this->authorize('update', $customer);
        abort_unless((int) $contact->customer_id === (int) $customer->id, 404);
        $contact->delete();

        return back()->with('success', 'Contact removed.');
    }

    // ---- CSV import / export ----

    public function export()
    {
        $this->authorize('viewAny', Customer::class);
        $rows = Customer::where('tenant_id', $this->tenantId())->with('primaryContact')
            ->orderBy('company_name')->cursor()
            ->map(fn ($c) => [
                $c->company_name, $c->email, $c->phone, $c->website,
                $c->address, $c->city, $c->postcode, $c->country,
                $c->primaryContact?->first_name.' '.$c->primaryContact?->last_name,
                $c->primaryContact?->email, $c->status,
            ]);

        return $this->csvDownload('customers-'.now()->format('Ymd').'.csv',
            ['Company', 'Email', 'Phone', 'Website', 'Address', 'City', 'Postcode', 'Country', 'Primary contact', 'Contact email', 'Status'], $rows);
    }

    public function import(Request $request)
    {
        $this->authorize('create', Customer::class);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $path = $request->file('file')->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        $header = array_map(fn ($h) => strtolower(trim($h)), array_shift($rows));

        $limit = config('supportflow.import.max_rows', 2000);
        abort_if(count($rows) > $limit, 422, "Import exceeds the {$limit}-row limit.");

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $log = ImportLog::create([
            'tenant_id' => $this->tenantId(), 'imported_by' => $request->user()->id,
            'entity' => 'customers', 'original_filename' => $request->file('file')->getClientOriginalName(),
            'rows_total' => count($rows),
        ]);

        DB::transaction(function () use ($rows, $header, &$imported, &$skipped, &$errors) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;
                $data = array_combine($header, array_pad($row, count($header), null));
                $name = trim($data['company'] ?? $data['company_name'] ?? '');
                if ($name === '') {
                    $skipped++;
                    $errors[] = "Row {$line}: company name is required.";

                    continue;
                }
                // Duplicate detection (same tenant + company name).
                if (Customer::where('tenant_id', $this->tenantId())->where('company_name', $name)->exists()) {
                    $skipped++;
                    $errors[] = "Row {$line}: '{$name}' already exists — skipped (not overwritten).";

                    continue;
                }
                Customer::create([
                    'tenant_id' => $this->tenantId(), 'company_name' => $name,
                    'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null,
                    'city' => $data['city'] ?? null, 'country' => $data['country'] ?? null,
                    'address' => $data['address'] ?? null, 'postcode' => $data['postcode'] ?? null,
                    'status' => 'active',
                ]);
                $imported++;
            }
        });

        $log->update(['rows_imported' => $imported, 'rows_skipped' => $skipped, 'errors' => array_slice($errors, 0, 100)]);
        audit('customers.imported', null, ['imported' => $imported, 'skipped' => $skipped]);

        return back()->with('success', "Import complete: {$imported} imported, {$skipped} skipped.");
    }

    // ---- internals ----

    protected function validateCustomer(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'company_name' => 'required|string|max:255',
            'account_code' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,archived',
            'owner_id' => 'nullable|exists:users,id',
            'sla_policy_id' => 'nullable|exists:sla_policies,id',
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    protected function staffList()
    {
        return User::where('tenant_id', $this->tenantId())->whereIn('role', User::STAFF_ROLES)->orderBy('name')->get();
    }

    protected function slaPolicies()
    {
        return \App\Models\SlaPolicy::where('tenant_id', $this->tenantId())->where('is_active', true)->orderBy('name')->get();
    }
}
