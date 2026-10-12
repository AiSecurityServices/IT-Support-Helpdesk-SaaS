<?php

namespace App\Services;

use App\Models\Category;
use App\Models\SlaPolicy;
use App\Models\Tenant;

/** Seeds per-workspace defaults for new tenants. */
class ProvisioningService
{
    public function seedDefaults(Tenant $tenant): void
    {
        app()->instance('currentTenant', $tenant);
        app()->instance('currentTenantId', $tenant->id);

        foreach (['Hardware', 'Software', 'Network', 'Access & Accounts', 'Email & Collaboration'] as $i => $name) {
            Category::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['sort_order' => $i, 'is_active' => true]
            );
        }

        SlaPolicy::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Standard'],
            [
                'description' => 'Default response & resolution targets.',
                'targets' => config('supportflow.sla_default_targets'),
                'working_hours' => [
                    'days' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '17:30',
                    'timezone' => $tenant->timezone ?? config('branding.default_timezone'),
                ],
                'pause_on_pending_customer' => true,
                'is_default' => true, 'is_active' => true,
            ]
        );

        $tenant->updateSettings([
            'working_hours' => [
                'days' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '17:30',
                'timezone' => $tenant->timezone ?? config('branding.default_timezone'),
            ],
            'invoice' => [
                'prefix' => config('supportflow.invoice_prefix', 'INV-'),
                'currency' => $tenant->currency ?? 'GBP',
                'tax_percent' => 20, 'payment_terms_days' => 14,
            ],
            'notifications' => ['email_enabled' => true],
        ]);
    }
}
