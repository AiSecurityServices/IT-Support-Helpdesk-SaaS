<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('supportflow.plans', []) as $slug => $plan) {
            Plan::updateOrCreate(['slug' => $slug], [
                'name' => $plan['name'],
                'price_monthly' => $plan['price_monthly'],
                'price_annual' => $plan['price_annual'],
                'currency' => $plan['currency'] ?? 'GBP',
                'technician_seats' => $plan['technician_seats'],
                'asset_limit' => $plan['asset_limit'],
                'ticket_limit_monthly' => $plan['ticket_limit_monthly'],
                'storage_mb' => $plan['storage_mb'],
                'features' => $plan['features'],
                'is_active' => true,
                'trial_eligible' => true,
            ]);
        }
    }
}
