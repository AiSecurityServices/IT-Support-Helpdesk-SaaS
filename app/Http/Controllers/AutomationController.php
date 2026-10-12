<?php

namespace App\Http\Controllers;

use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\Team;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Http\Request;

/** Automation rules: triggers, conditions, actions, execution history. */
class AutomationController extends Controller
{
    protected function tenantId(): int
    {
        return (int) currentTenantId();
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);

        return view('automation.index', [
            'rules' => AutomationRule::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'runs' => AutomationRun::where('tenant_id', $this->tenantId())->latest()->limit(50)->get(),
            'teams' => Team::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'technicians' => User::where('tenant_id', $this->tenantId())->whereIn('role', User::STAFF_ROLES)->orderBy('name')->get(),
            'webhooks' => WebhookEndpoint::where('tenant_id', $this->tenantId())->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);
        $data = $this->validateRule($request);
        $rule = AutomationRule::create($data + ['tenant_id' => $this->tenantId()]);
        audit('automation.created', $rule);

        return back()->with('success', 'Automation rule created. Use “dry-run” to verify before enabling.');
    }

    public function update(Request $request, AutomationRule $rule)
    {
        abort_unless((int) $rule->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);
        $rule->fill($this->validateRule($request))->save();
        audit('automation.updated', $rule);

        return back()->with('success', 'Rule updated.');
    }

    public function destroy(Request $request, AutomationRule $rule)
    {
        abort_unless((int) $rule->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        $rule->delete();
        audit('automation.deleted', $rule);

        return back()->with('success', 'Rule deleted.');
    }

    /** Dry-run: evaluate conditions against a recent ticket without acting. */
    public function dryRun(Request $request, AutomationRule $rule)
    {
        abort_unless((int) $rule->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);
        $ticket = \App\Models\Ticket::where('tenant_id', $this->tenantId())->latest()->first();
        abort_unless($ticket, 422, 'No tickets to evaluate against yet.');

        $context = ['tenant_id' => $this->tenantId(), 'subject_id' => $ticket->id, 'ticket_id' => $ticket->id,
            'ticket' => ['status' => $ticket->status, 'priority' => $ticket->priority,
                'customer_id' => $ticket->customer_id, 'team_id' => $ticket->team_id,
                'assigned_to' => $ticket->assigned_to]];
        $matched = true;
        foreach ($rule->conditions ?? [] as $cond) {
            $actual = data_get($context, $cond['field'] ?? '');
            $matched = $matched && match ($cond['operator'] ?? 'eq') {
                'eq' => $actual == ($cond['value'] ?? null),
                'neq' => $actual != ($cond['value'] ?? null),
                default => false,
            };
        }

        return back()->with('success', $matched
            ? "Dry-run: rule WOULD fire for ticket {$ticket->number} (no actions executed)."
            : "Dry-run: rule would NOT fire for ticket {$ticket->number}.");
    }

    protected function validateRule(Request $request): array
    {
        // Friendly JSON textarea for action params (merged into actions[0]).
        if ($request->filled('actions_json')) {
            $params = json_decode($request->input('actions_json'), true);
            abort_unless(is_array($params), 422, 'Action params must be valid JSON.');
            $actions = $request->input('actions', []);
            $actions[0]['params'] = $params;
            $request->merge(['actions' => $actions]);
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|in:'.implode(',', AutomationRule::TRIGGERS),
            'conditions' => 'nullable|array|max:10',
            'conditions.*.field' => 'required_with:conditions|string|max:100',
            'conditions.*.operator' => 'required_with:conditions|in:eq,neq,gt,gte,lt,lte,contains,in',
            'conditions.*.value' => 'nullable|string|max:255',
            'actions' => 'required|array|min:1|max:10',
            'actions.*.type' => 'required|in:assign_team,assign_technician,set_priority,add_label,escalate,notify,webhook,internal_note',
            'actions.*.params' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'max_runs_per_hour' => 'nullable|integer|min:1|max:1000',
        ]);

        // Validate action params reference this tenant's records only.
        foreach ($data['actions'] as $action) {
            $p = $action['params'] ?? [];
            if (! empty($p['team_id'])) {
                abort_unless(Team::where('tenant_id', $this->tenantId())->where('id', $p['team_id'])->exists(), 422);
            }
            if (! empty($p['user_id'])) {
                abort_unless(User::where('tenant_id', $this->tenantId())->where('id', $p['user_id'])->exists(), 422);
            }
            if (! empty($p['endpoint_id'])) {
                abort_unless(WebhookEndpoint::where('tenant_id', $this->tenantId())->where('id', $p['endpoint_id'])->exists(), 422);
            }
        }

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
