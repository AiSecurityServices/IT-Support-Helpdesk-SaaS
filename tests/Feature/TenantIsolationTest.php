<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

/**
 * Proves Tenant A cannot access or modify Tenant B's records through
 * direct URLs, APIs, downloads or exports.
 */
class TenantIsolationTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_cross_tenant_ticket_urls_are_blocked(): void
    {
        $a = $this->makeTenant('Alpha');
        $b = $this->makeTenant('Beta');
        $techA = $this->makeUser($a, 'technician');

        $ticketB = Ticket::withoutTenantScope()->create([
            'tenant_id' => $b->id, 'subject' => 'Secret', 'description' => 'Secret',
            'priority' => 'normal', 'status' => 'new',
        ]);

        $this->actingAs($techA);
        app()->instance('currentTenant', $a);
        app()->instance('currentTenantId', $a->id);

        // Cross-tenant records are invisible at the query layer (404 reveals
        // nothing about existence); policies add a second 403 layer in-app.
        $this->get(route('tickets.show', $ticketB))->assertNotFound();
        $this->post(route('tickets.reply', $ticketB), ['body' => 'hijack'])->assertNotFound();
        $this->put(route('tickets.update', $ticketB), ['subject' => 'x', 'description' => 'y', 'priority' => 'low'])->assertNotFound();
        $this->assertEquals('Secret', $ticketB->fresh()->subject); // unmodified
    }

    public function test_tenant_scopes_hide_other_tenants_records(): void
    {
        $a = $this->makeTenant('Alpha');
        $b = $this->makeTenant('Beta');
        $this->makeUser($a, 'owner');

        Ticket::withoutTenantScope()->create(['tenant_id' => $b->id, 'subject' => 'B ticket', 'description' => 'x', 'priority' => 'normal']);
        Customer::withoutTenantScope()->create(['tenant_id' => $b->id, 'company_name' => 'B Co']);
        Asset::withoutTenantScope()->create(['tenant_id' => $b->id, 'name' => 'B laptop']);

        app()->instance('currentTenant', $a);
        app()->instance('currentTenantId', $a->id);

        $this->assertSame(0, Ticket::count());
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Asset::count());
        $this->assertSame(1, Ticket::withoutTenantScope()->where('tenant_id', $b->id)->count());
    }

    public function test_api_cross_tenant_access_returns_404(): void
    {
        $a = $this->makeTenant('Alpha');
        $b = $this->makeTenant('Beta');
        $techA = $this->makeUser($a, 'technician');
        $ticketB = Ticket::withoutTenantScope()->create([
            'tenant_id' => $b->id, 'subject' => 'B', 'description' => 'x', 'priority' => 'normal',
        ]);

        $token = $techA->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/tickets/'.$ticketB->id)->assertNotFound();
    }

    public function test_customer_cannot_see_other_customers_tickets_or_invoices(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $c1 = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $c2 = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'Two']);
        $user = $this->makeUser($tenant, 'customer', ['customer_id' => $c1->id]);

        $other = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'Other', 'description' => 'x',
            'priority' => 'normal', 'customer_id' => $c2->id]);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'customer_id' => $c2->id, 'status' => 'issued',
            'currency' => 'GBP', 'total' => 100]);

        $this->actingAs($user);
        $this->get(route('portal.tickets.show', $other))->assertNotFound();
        $this->get(route('portal.invoices.show', $invoice))->assertNotFound();
    }

    public function test_exports_only_contain_own_tenant_data(): void
    {
        $a = $this->makeTenant('Alpha');
        $b = $this->makeTenant('Beta');
        $ownerA = $this->makeUser($a, 'owner');

        Customer::create(['tenant_id' => $a->id, 'company_name' => 'A Co']);
        Customer::withoutTenantScope()->create(['tenant_id' => $b->id, 'company_name' => 'B Co']);

        $this->actingAs($ownerA);
        $response = $this->get(route('customers.export'))->assertOk();
        $this->assertStringContainsString('A Co', $response->streamedContent());
        $this->assertStringNotContainsString('B Co', $response->streamedContent());
    }
}
