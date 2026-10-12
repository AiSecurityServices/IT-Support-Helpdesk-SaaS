<?php

namespace App\Services;

use App\Models\Tenant;

/**
 * Guided workspace setup: derived from real tenant state, no stored flags
 * to drift out of sync. Shown until complete, then dismissed automatically.
 */
class OnboardingChecklist
{
    public function steps(Tenant $tenant): array
    {
        $id = $tenant->id;
        $steps = [
            [
                'key' => 'workspace',
                'label' => 'Name your workspace',
                'done' => (bool) $tenant->name && ! str_starts_with($tenant->name, 'Untitled'),
                'url' => route('settings.index'),
            ],
            [
                'key' => 'team',
                'label' => 'Create a support team',
                'done' => \App\Models\Team::where('tenant_id', $id)->exists(),
                'url' => route('teams.index'),
            ],
            [
                'key' => 'technician',
                'label' => 'Invite a technician',
                'done' => $tenant->users()->wherePivotIn('role', ['technician', 'manager', 'admin'])->exists()
                    || \App\Models\TenantInvitation::where('tenant_id', $id)->exists(),
                'url' => route('technicians.index'),
            ],
            [
                'key' => 'customer',
                'label' => 'Add your first customer',
                'done' => \App\Models\Customer::where('tenant_id', $id)->exists(),
                'url' => route('customers.index'),
            ],
            [
                'key' => 'ticket',
                'label' => 'Raise your first ticket',
                'done' => \App\Models\Ticket::where('tenant_id', $id)->exists(),
                'url' => route('tickets.create'),
            ],
            [
                'key' => 'sla',
                'label' => 'Review the SLA policy',
                'done' => \App\Models\SlaPolicy::where('tenant_id', $id)->exists(),
                'url' => route('sla.index'),
            ],
        ];
        $done = collect($steps)->where('done', true)->count();

        return ['steps' => $steps, 'done' => $done, 'total' => count($steps), 'complete' => $done === count($steps)];
    }
}
