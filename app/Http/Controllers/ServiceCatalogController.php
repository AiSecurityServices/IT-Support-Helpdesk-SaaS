<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ServiceCatalogItem;
use App\Models\ServiceRequest;
use App\Models\ServiceTask;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Service catalogue & request fulfilment: configurable request types with
 * validated forms, approvals (with separation of duties), fulfilment tasks,
 * linked tickets/assets, completion evidence and customer confirmation.
 */
class ServiceCatalogController extends Controller
{
    protected function tenantId(): int
    {
        return (int) currentTenantId();
    }

    // ---------- catalogue items (staff config) ----------

    public function index(Request $request)
    {
        $items = ServiceCatalogItem::with('team')->where('tenant_id', $this->tenantId())
            ->when($request->user()->isCustomer(), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')->get();

        return view($request->user()->isCustomer() ? 'portal.catalog' : 'catalog.index', compact('items'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $data = $this->validateItem($request);
        ServiceCatalogItem::create($data + ['tenant_id' => $this->tenantId()]);
        audit('catalog.item_created');

        return back()->with('success', ' catalogue item created.');
    }

    public function update(Request $request, ServiceCatalogItem $item)
    {
        abort_unless((int) $item->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->canManage('configuration'), 403);
        $item->fill($this->validateItem($request))->save();
        audit('catalog.item_updated', $item);

        return back()->with('success', 'Catalogue item updated.');
    }

    public function destroy(Request $request, ServiceCatalogItem $item)
    {
        abort_unless((int) $item->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        abort_if($item->requests()->whereNotIn('status', ['fulfilled', 'cancelled', 'rejected'])->exists(), 422, 'Item has open requests.');
        $item->delete();
        audit('catalog.item_deleted', $item);

        return back()->with('success', 'Catalogue item removed.');
    }

    // ---------- requests ----------

    public function show(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless((int) $serviceRequest->tenant_id === $this->tenantId(), 404);
        $user = $request->user();
        if ($user->isCustomer()) {
            abort_unless((int) $serviceRequest->customer_id === (int) $user->customer_id, 404);
            abort_unless((int) $serviceRequest->requester_id === (int) $user->id, 404);
        }
        $serviceRequest->load(['item', 'requester', 'customer', 'ticket', 'tasks.owner']);

        $view = $user->isCustomer() ? 'portal.request-show' : 'catalog.request-show';

        return view($view, ['serviceRequest' => $serviceRequest]);
    }

    public function storeRequest(Request $request, ServiceCatalogItem $item, TicketService $tickets)
    {
        abort_unless((int) $item->tenant_id === $this->tenantId(), 404);
        abort_if(! $item->is_active, 422, 'This service is currently unavailable.');
        $user = $request->user();

        $formData = $this->validateFormData($request, $item);
        $attachments = [];
        foreach ((array) $request->file('attachments', []) as $file) {
            if ($file && $file->isValid()) {
                $attachments[] = $file->getClientOriginalName();
            }
        }

        return DB::transaction(function () use ($request, $item, $user, $formData, $tickets, $attachments) {
            $serviceRequest = ServiceRequest::create([
                'tenant_id' => $this->tenantId(),
                'service_catalog_item_id' => $item->id,
                'requester_id' => $user->id,
                'customer_id' => $user->isCustomer() ? $user->customer_id : $request->input('customer_id'),
                'asset_id' => $request->input('asset_id'),
                'status' => $item->requires_approval ? 'pending_approval' : 'approved',
                'form_data' => $formData + ($attachments ? ['_attachments' => $attachments] : []),
            ]);

            // Fulfilment ticket on the correct team with the item's SLA.
            $ticket = $tickets->create([
                'subject' => "[{$serviceRequest->number}] {$item->name}",
                'description' => $this->renderSummary($item, $formData),
                'customer_id' => $serviceRequest->customer_id,
                'team_id' => $item->team_id,
                'priority' => 'normal',
                'source' => $user->isCustomer() ? 'portal' : 'staff',
                'sla_policy_id' => $item->sla_policy_id,
                'asset_id' => $serviceRequest->asset_id,
            ], $user);
            $serviceRequest->ticket_id = $ticket->id;
            $serviceRequest->save();

            if ($item->requires_approval) {
                $this->notifyApprovers($serviceRequest);
            }
            \App\Services\AutomationEngine::fire('approval.decided', [
                'tenant_id' => $this->tenantId(), 'subject_id' => $serviceRequest->id,
                'fingerprint' => ['status' => $serviceRequest->status],
            ]);
            audit('catalog.request_created', $serviceRequest, ['number' => $serviceRequest->number]);

            $route = $user->isCustomer() ? 'portal.requests.show' : 'catalog.requests.show';

            return redirect()->route($route, $serviceRequest)->with('success', "Request {$serviceRequest->number} submitted.");
        });
    }

    /** Approve / reject with separation of duties (configurable). */
    public function decide(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless((int) $serviceRequest->tenant_id === $this->tenantId(), 404);
        abort_unless($serviceRequest->status === 'pending_approval', 422, 'Request is not awaiting approval.');
        $user = $request->user();
        abort_unless($user->hasRole($serviceRequest->item->approver_role, 'owner', 'admin'), 403, 'Approval permission required.');
        $request->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:2000']);

        // Separation of duties: authors cannot approve their own requests when enforced.
        $sod = (bool) currentTenant()?->setting('approvals.separation_of_duties', true);
        if ($sod && (int) $serviceRequest->requester_id === (int) $user->id) {
            abort(422, 'Separation of duties: the requester cannot approve their own request.');
        }

        return DB::transaction(function () use ($request, $serviceRequest, $user) {
            $approvals = $serviceRequest->approvals ?? [];
            $approvals[] = ['by' => $user->id, 'name' => $user->name, 'decision' => $request->input('decision'),
                'at' => now()->toIso8601String(), 'note' => $request->input('note')];
            $serviceRequest->approvals = $approvals;
            $serviceRequest->status = $request->input('decision') === 'approved' ? 'approved' : 'rejected';
            if ($serviceRequest->status === 'approved') {
                $serviceRequest->status = 'in_progress';
            }
            $serviceRequest->save();

            $serviceRequest->requester?->notify(new \App\Notifications\ServiceRequestDecidedNotification($serviceRequest));
            \App\Services\AutomationEngine::fire('approval.decided', [
                'tenant_id' => $this->tenantId(), 'subject_id' => $serviceRequest->id,
                'fingerprint' => ['status' => $serviceRequest->status],
            ]);
            audit('catalog.request_decided', $serviceRequest, ['decision' => $request->input('decision')]);

            return back()->with('success', 'Decision recorded.');
        });
    }

    public function storeTask(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless((int) $serviceRequest->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->isStaff(), 403);
        $data = $request->validate([
            'title' => 'required|string|max:255', 'owner_id' => 'nullable|exists:users,id',
        ]);
        if (! empty($data['owner_id'])) {
            abort_unless(\App\Models\User::where('tenant_id', $this->tenantId())->where('id', $data['owner_id'])->exists(), 422);
        }
        $serviceRequest->tasks()->create($data);

        return back()->with('success', 'Task added.');
    }

    public function completeTask(Request $request, ServiceTask $task)
    {
        $serviceRequest = $task->service_request_id
            ? ServiceRequest::where('tenant_id', $this->tenantId())->findOrFail($task->service_request_id)
            : abort(404);
        abort_unless($request->user()->isStaff(), 403);
        $request->validate(['evidence' => 'nullable|string|max:2000', 'status' => 'required|in:done,skipped']);
        $task->status = $request->input('status');
        $task->evidence = $request->input('evidence');
        $task->completed_at = now();
        $task->save();

        return back()->with('success', 'Task updated.');
    }

    /** Fulfil with completion evidence; customer confirms separately. */
    public function fulfil(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless((int) $serviceRequest->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->isStaff(), 403);
        abort_unless(in_array($serviceRequest->status, ['approved', 'in_progress'], true), 422);
        $request->validate(['completion_evidence' => 'required|string|max:5000']);
        $serviceRequest->completion_evidence = $request->input('completion_evidence');
        $serviceRequest->status = 'fulfilled';
        $serviceRequest->save();
        audit('catalog.request_fulfilled', $serviceRequest);

        return back()->with('success', 'Request marked fulfilled. Awaiting customer confirmation.');
    }

    public function confirm(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless((int) $serviceRequest->tenant_id === $this->tenantId(), 404);
        abort_unless($serviceRequest->status === 'fulfilled', 422);
        $user = $request->user();
        if ($user->isCustomer()) {
            abort_unless((int) $serviceRequest->requester_id === (int) $user->id, 403);
        } else {
            abort_unless($user->isStaff(), 403);
        }
        $serviceRequest->customer_confirmed = true;
        $serviceRequest->save();

        return back()->with('success', 'Completion confirmed. Thank you.');
    }

    // ---------- internals ----------

    protected function validateItem(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:2000',
            'form_schema' => 'nullable|array|max:30',
            'form_schema.*.key' => 'required_with:form_schema|string|max:50',
            'form_schema.*.label' => 'required_with:form_schema|string|max:255',
            'form_schema.*.type' => 'required_with:form_schema|in:text,number,date,select,boolean',
            'form_schema.*.required' => 'nullable|boolean',
            'form_schema.*.options' => 'nullable|array|max:50',
            'form_schema.*.depends_on' => 'nullable|array',
            'team_id' => 'nullable|exists:teams,id',
            'sla_policy_id' => 'nullable|exists:sla_policies,id',
            'requires_approval' => 'nullable|boolean',
            'approver_role' => 'nullable|in:manager,admin,owner',
            'is_active' => 'nullable|boolean',
        ]);
        foreach (['team_id' => Team::class, 'sla_policy_id' => \App\Models\SlaPolicy::class] as $field => $model) {
            if (! empty($data[$field])) {
                abort_unless($model::where('tenant_id', $this->tenantId())->where('id', $data[$field])->exists(), 422);
            }
        }

        return $data;
    }

    /** Validate submitted data against the item schema incl. conditional questions. */
    protected function validateFormData(Request $request, ServiceCatalogItem $item): array
    {
        $out = [];
        foreach ($item->form_schema ?? [] as $field) {
            $key = $field['key'];
            // Conditional: skip unless dependency matches (resolved inside fields.*).
            if (! empty($field['depends_on'])) {
                $dep = $field['depends_on'];
                $depValue = $request->input('fields.'.($dep['field'] ?? ''), $request->input($dep['field'] ?? ''));
                if ($depValue != ($dep['value'] ?? null)) {
                    continue;
                }
            }
            $value = $request->input("fields.{$key}");
            if (! empty($field['required']) && ($value === null || $value === '')) {
                abort(response()->json(['message' => "Field {$field['label']} is required."], 422));
            }
            if ($value !== null && $value !== '') {
                match ($field['type']) {
                    'number' => is_numeric($value) ?: abort(response()->json(['message' => "{$field['label']} must be numeric."], 422)),
                    'date' => strtotime($value) !== false ?: abort(response()->json(['message' => "{$field['label']} must be a date."], 422)),
                    'select' => in_array($value, $field['options'] ?? [], false) ?: abort(response()->json(['message' => "{$field['label']} has an invalid option."], 422)),
                    default => null,
                };
                $out[$key] = is_string($value) ? substr($value, 0, 5000) : $value;
            }
        }

        return $out;
    }

    protected function renderSummary(ServiceCatalogItem $item, array $formData): string
    {
        $lines = ["Service request: {$item->name}"];
        foreach ($formData as $k => $v) {
            if (str_starts_with($k, '_')) {
                continue;
            }
            $lines[] = '- '.ucfirst(str_replace('_', ' ', $k)).': '.(is_scalar($v) ? $v : json_encode($v));
        }

        return implode("\n", $lines);
    }

    protected function notifyApprovers(ServiceRequest $serviceRequest): void
    {
        $approvers = \App\Models\User::where('tenant_id', $this->tenantId())
            ->whereIn('role', [$serviceRequest->item->approver_role, 'owner', 'admin'])
            ->where('is_active', true)->get();
        foreach ($approvers as $approver) {
            if ((int) $approver->id === (int) $serviceRequest->requester_id) {
                continue; // never ask the requester to approve their own request
            }
            $approver->notify(new \App\Notifications\ServiceRequestDecidedNotification($serviceRequest, true));
        }
    }
}
