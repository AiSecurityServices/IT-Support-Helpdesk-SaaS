<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        // Demo data is strictly opt-in and NEVER seeded in production.
        if (app()->environment('local', 'testing') && env('SEED_DEMO_DATA', false)) {
            $this->call(DemoSeeder::class);
        }
    }
}
