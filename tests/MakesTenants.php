<?php

namespace Tests;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

trait MakesTenants
{
    use RefreshDatabase;

    protected function seedPlans(): void
    {
        foreach (config('supportflow.plans', []) as $slug => $plan) {
            Plan::create([
                'slug' => $slug, 'name' => $plan['name'],
                'price_monthly' => $plan['price_monthly'], 'price_annual' => $plan['price_annual'],
                'currency' => $plan['currency'] ?? 'GBP',
                'technician_seats' => $plan['technician_seats'], 'asset_limit' => $plan['asset_limit'],
                'ticket_limit_monthly' => $plan['ticket_limit_monthly'],
                'storage_mb' => $plan['storage_mb'], 'features' => $plan['features'],
                'is_active' => true, 'trial_eligible' => true,
            ]);
        }
    }

    protected function makeTenant(string $name = 'Acme', string $status = 'active'): Tenant
    {
        $tenant = Tenant::create([
            'name' => $name.' Ltd', 'email' => strtolower($name).'@test.local',
            'status' => $status, 'timezone' => 'Europe/London', 'currency' => 'GBP',
        ]);
        $plan = Plan::where('slug', 'growth')->first();
        Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan?->id,
            'status' => $status === 'active' ? 'active' : 'trial',
            'interval' => 'monthly', 'provider' => 'manual',
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        app()->instance('currentTenant', $tenant);
        app()->instance('currentTenantId', $tenant->id);

        return $tenant;
    }

    protected function makeUser(Tenant $tenant, string $role = 'technician', array $overrides = []): User
    {
        static $n = 0;
        $n++;
        $user = User::create(array_merge([
            'name' => ucfirst($role).' '.$n, 'email' => "{$role}{$n}@".$tenant->id.'.test',
            'password' => Hash::make('Password123!'), 'tenant_id' => $tenant->id,
            'role' => $role,
        ], $overrides));
        // Guarded attribute: set outside mass assignment.
        $user->forceFill(['email_verified_at' => now()])->save();
        $tenant->users()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

        return $user;
    }
}
