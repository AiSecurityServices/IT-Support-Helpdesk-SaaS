<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\SavedReply;
use App\Models\SlaPolicy;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Clearly-labelled DEMO data for development/trial environments only.
 * Never run in production (guarded in DatabaseSeeder).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => 'demo-msp'], [
            'name' => 'Demo MSP Ltd',
            'email' => 'demo@example.test',
            'status' => 'active',
            'timezone' => 'Europe/London',
            'currency' => 'GBP',
            'settings' => ['demo' => true],
        ]);

        app()->instance('currentTenant', $tenant);
        app()->instance('currentTenantId', $tenant->id);

        $owner = User::firstOrCreate(['email' => 'owner@demo.test'], [
            'name' => 'Demo Owner', 'password' => Hash::make('DemoPass123!'),
            'tenant_id' => $tenant->id, 'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        $tech = User::firstOrCreate(['email' => 'tech@demo.test'], [
            'name' => 'Dana Technician', 'password' => Hash::make('DemoPass123!'),
            'tenant_id' => $tenant->id, 'role' => 'technician',
            'email_verified_at' => now(),
        ]);

        $team = Team::firstOrCreate(['tenant_id' => $tenant->id, 'name' => 'Service Desk']);
        $team->members()->syncWithoutDetaching([$owner->id, $tech->id]);

        foreach (['Hardware', 'Software', 'Network', 'Access & Accounts'] as $name) {
            Category::firstOrCreate(['tenant_id' => $tenant->id, 'name' => $name]);
        }

        SlaPolicy::firstOrCreate(['tenant_id' => $tenant->id, 'name' => 'Standard'], [
            'targets' => config('supportflow.sla_default_targets'),
            'is_default' => true, 'is_active' => true,
        ]);

        $customer = Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'company_name' => 'Acme Distribution Ltd'],
            ['email' => 'it@acme.test', 'status' => 'active']
        );
        Contact::firstOrCreate(
            ['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'email' => ' Priya@acme.test'],
            ['first_name' => 'Priya', 'last_name' => 'Sharma', 'is_primary' => true]
        );

        SavedReply::firstOrCreate(['tenant_id' => $tenant->id, 'title' => 'Acknowledgement'], [
            'body' => "Thanks for contacting support — we've logged your request and a technician will respond shortly.",
        ]);

        if (Ticket::where('tenant_id', $tenant->id)->count() === 0) {
            $service = app(\App\Services\TicketService::class);
            $category = Category::where('tenant_id', $tenant->id)->first();
            $service->create([
                'subject' => 'Demo: VPN drops every afternoon',
                'description' => 'Several users report the VPN disconnecting after 15:00. Started Monday.',
                'customer_id' => $customer->id, 'category_id' => $category?->id,
                'priority' => 'high', 'team_id' => $team->id, 'source' => 'staff',
            ], $owner);
        }
    }
}
