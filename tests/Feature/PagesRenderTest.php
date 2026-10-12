<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

/** Every major screen must render HTTP 200 with real data (no dead views). */
class PagesRenderTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $customer = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Desk']);
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'Seed', 'description' => 'x',
            'priority' => 'normal', 'customer_id' => $customer->id, 'team_id' => $team->id]);
        $asset = Asset::create(['tenant_id' => $tenant->id, 'name' => 'Laptop', 'type' => 'laptop', 'status' => 'in_stock']);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id,
            'status' => 'draft', 'currency' => 'GBP']);

        $this->actingAs($owner);
        $this->seed = compact('customer', 'team', 'ticket', 'asset', 'invoice');
    }

    /** @dataProvider staffPages */
    public function test_staff_pages_render(string $method, string $route, array $params = []): void
    {
        $url = route($route, $params);
        $response = $method === 'GET' ? $this->get($url) : $this->post($url);
        $response->assertOk();
    }

    public function staffPages(): array
    {
        return [
            'marketing home' => ['GET', 'home'],
            'dashboard' => ['GET', 'dashboard'],
            'tickets all' => ['GET', 'tickets.index'],
            'tickets mine' => ['GET', 'tickets.index', ['queue' => 'mine']],
            'ticket create' => ['GET', 'tickets.create'],
            'customers' => ['GET', 'customers.index'],
            'customer create' => ['GET', 'customers.create'],
            'teams' => ['GET', 'teams.index'],
            'technicians' => ['GET', 'technicians.index'],
            'assets' => ['GET', 'assets.index'],
            'asset create' => ['GET', 'assets.create'],
            'sla' => ['GET', 'sla.index'],
            'remote' => ['GET', 'remote.index'],
            'invoices' => ['GET', 'invoices.index'],
            'invoice create' => ['GET', 'invoices.create'],
            'reports' => ['GET', 'reports.index'],
            'reports tickets' => ['GET', 'reports.tickets'],
            'reports sla' => ['GET', 'reports.sla'],
            'reports workload' => ['GET', 'reports.workload'],
            'reports financial' => ['GET', 'reports.financial'],
            'reports assets' => ['GET', 'reports.assets'],
            'reports activity' => ['GET', 'reports.activity'],
            'billing' => ['GET', 'billing.index'],
            'settings' => ['GET', 'settings.index'],
            'settings tokens' => ['GET', 'settings.tokens'],
            'notifications' => ['GET', 'notifications.index'],
            'profile' => ['GET', 'profile.edit'],
            'kb' => ['GET', 'kb.index'],
            'kb create' => ['GET', 'kb.create'],
            'catalog' => ['GET', 'catalog.index'],
            'problems' => ['GET', 'problems.index'],
            'changes' => ['GET', 'changes.index'],
            'cmdb' => ['GET', 'cmdb.index'],
            'automation' => ['GET', 'automation.index'],
            'pricing' => ['GET', 'pricing'],
            'features' => ['GET', 'features'],
            'faq' => ['GET', 'faq'],
        ];
    }

    public function test_record_pages_render(): void
    {
        $s = $this->seed;
        $this->get(route('tickets.show', $s['ticket']))->assertOk();
        $this->get(route('tickets.edit', $s['ticket']))->assertOk();
        $this->get(route('customers.show', $s['customer']))->assertOk();
        $this->get(route('customers.edit', $s['customer']))->assertOk();
        $this->get(route('assets.show', $s['asset']))->assertOk();
        $this->get(route('assets.edit', $s['asset']))->assertOk();
        $this->get(route('invoices.show', $s['invoice']))->assertOk();
        $this->get(route('technicians.show', auth()->user()))->assertOk();
        $this->get(route('search', ['q' => 'seed']))->assertOk();
    }

    public function test_portal_pages_render(): void
    {
        $tenant = \App\Models\Tenant::first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $user = $this->makeUser($tenant, 'customer', ['customer_id' => $customer->id]);
        $this->actingAs($user);

        $this->get(route('portal.dashboard'))->assertOk();
        $this->get(route('portal.tickets'))->assertOk();
        $this->get(route('portal.tickets.create'))->assertOk();
        $this->get(route('portal.services'))->assertOk();
        $this->get(route('portal.kb'))->assertOk();
        $this->get(route('portal.assets'))->assertOk();
        $this->get(route('portal.invoices'))->assertOk();
        $this->get(route('portal.profile'))->assertOk();
        $ticket = Ticket::where('tenant_id', $tenant->id)->first();
        $this->get(route('portal.tickets.show', $ticket))->assertOk(); // own-org ticket visible
        $otherCustomer = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'Other']);
        $otherTicket = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'X', 'description' => 'x',
            'priority' => 'normal', 'customer_id' => $otherCustomer->id]);
        $this->get(route('portal.tickets.show', $otherTicket))->assertNotFound(); // other-org hidden
    }

    public function test_admin_pages_render(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'Root', 'email' => 'root@platform.test',
            'password' => bcrypt('Password123!'), 'role' => 'owner', 'is_super_admin' => true,
        ]);
        $admin->forceFill(['email_verified_at' => now(), 'totp_enabled_at' => now()])->save();
        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.tenants'))->assertOk();
        $this->get(route('admin.plans'))->assertOk();
        $this->get(route('admin.audit'))->assertOk();
        $this->get(route('admin.health'))->assertOk();
        $this->get(route('admin.impersonation'))->assertOk();
        $this->get(route('admin.tenants.show', \App\Models\Tenant::first()))->assertOk();
    }
}
