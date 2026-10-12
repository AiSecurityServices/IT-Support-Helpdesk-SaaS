<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

class PortalAndApiTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_customer_portal_end_to_end(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $customer = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $user = $this->makeUser($tenant, 'customer', ['customer_id' => $customer->id]);
        $this->actingAs($user);

        // Create request via portal.
        $this->post(route('portal.tickets.store'), [
            'subject' => 'My laptop is slow', 'description' => 'Takes 10 minutes to boot.',
            'priority' => 'normal',
        ])->assertRedirect();
        $ticket = Ticket::where('tenant_id', $tenant->id)->first();
        $this->assertEquals('portal', $ticket->source);

        // Reply to own ticket.
        $this->post(route('portal.tickets.reply', $ticket), ['body' => 'It beeps twice.'])->assertRedirect();

        // Staff internal note must NOT appear in portal conversation.
        $staff = $this->makeUser($tenant, 'technician');
        $ticket->messages()->create(['tenant_id' => $tenant->id, 'author_id' => $staff->id,
            'visibility' => 'internal', 'body' => 'SECRET: motherboard failing.']);
        $response = $this->get(route('portal.tickets.show', $ticket))->assertOk();
        $response->assertDontSee('SECRET');

        // Staff-only areas forbidden for customers.
        $this->get(route('tickets.index'))->assertForbidden();
        $this->get(route('assets.index'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
    }

    public function test_api_ticket_lifecycle_with_token(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');

        // Unauthenticated → 401 (checked first: withHeader persists in tests).
        $this->postJson('/api/v1/tickets', ['subject' => 'x', 'description' => 'y'])->assertUnauthorized();

        $token = $tech->createToken('api')->plainTextToken;

        $created = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/tickets', ['subject' => 'API ticket', 'description' => 'via api'])
            ->assertCreated()->json();
        $this->assertEquals('api', $created['source']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/tickets/'.$created['id'])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/tickets/'.$created['id'].'/replies', ['body' => 'API reply'])
            ->assertCreated();
    }

    public function test_asset_crud_and_warranty_math(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $this->post(route('assets.store'), [
            'name' => 'Dell Latitude 5440', 'type' => 'laptop', 'status' => 'in_stock',
            'serial_number' => 'SN-001', 'warranty_expires_at' => now()->addDays(20)->toDateString(),
        ])->assertRedirect();
        $asset = Asset::where('tenant_id', $tenant->id)->first();
        $this->assertMatchesRegularExpression('/^AST-\d+$/', $asset->asset_tag);
        $this->assertEquals(20, $asset->warrantyDaysLeft());

        $this->post(route('assets.note', $asset), ['details' => 'SSD replaced.']);
        $this->assertEquals(1, $asset->history()->where('action', 'maintenance')->count());

        // Serial is unique per import: duplicate skipped.
        $csv = "name,type,serial_number\nDupe,laptop,SN-001\nFresh,laptop,SN-002\n";
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, $csv);
        $this->call('POST', route('assets.import'), [], [], ['file' => new \Illuminate\Http\UploadedFile($tmp, 'assets.csv', 'text/csv', null, true)])
            ->assertRedirect();
        $this->assertEquals(2, Asset::where('tenant_id', $tenant->id)->count());
    }
}
