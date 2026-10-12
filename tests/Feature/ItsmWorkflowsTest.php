<?php

namespace Tests\Feature;

use App\Models\Change;
use App\Models\Customer;
use App\Models\MajorIncident;
use App\Models\Problem;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

class ItsmWorkflowsTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_problem_links_and_known_error(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $this->actingAs($tech);

        $this->post(route('problems.store'), ['title' => 'VPN drops at 15:00', 'description' => 'Daily pattern'])->assertRedirect();
        $problem = Problem::where('tenant_id', $tenant->id)->first();

        $t1 = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'VPN down', 'description' => 'x', 'priority' => 'high']);
        $t2 = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'VPN slow', 'description' => 'x', 'priority' => 'normal']);
        $this->post(route('problems.tickets.link', $problem), ['ticket_id' => $t1->id])->assertRedirect();
        $this->post(route('problems.tickets.link', $problem), ['ticket_id' => $t2->id])->assertRedirect();
        $this->assertSame(2, $problem->tickets()->count());

        $this->put(route('problems.update', $problem), [
            'title' => 'VPN drops at 15:00', 'status' => 'known_error',
            'root_cause' => 'Failover bug in firmware 4.2', 'workaround' => 'Manual failover',
            'known_error' => true,
        ])->assertRedirect();
        $this->assertTrue($problem->fresh()->known_error);

        // Cross-tenant link attempt rejected.
        $b = $this->makeTenant('Beta');
        $ticketB = Ticket::withoutTenantScope()->create(['tenant_id' => $b->id, 'subject' => 'Other', 'description' => 'x', 'priority' => 'low']);
        $this->post(route('problems.tickets.link', $problem), ['ticket_id' => $ticketB->id])->assertNotFound();
    }

    public function test_change_lifecycle_with_separation_of_duties(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $manager = $this->makeUser($tenant, 'manager');
        $this->actingAs($tech);

        $this->post(route('changes.store'), ['title' => 'Upgrade firewall firmware', 'type' => 'normal', 'risk' => 'high'])->assertRedirect();
        $change = Change::where('tenant_id', $tenant->id)->first();
        $this->assertEquals('draft', $change->status);

        // Cannot submit without implementation + rollback plans.
        $this->post(route('changes.submit', $change))->assertStatus(422);
        $this->put(route('changes.update', $change), [
            'title' => 'Upgrade firewall firmware',
            'implementation_plan' => 'Step 1…', 'rollback_plan' => 'Restore snapshot…',
            'window_start' => now()->addDay()->format('Y-m-d H:i'),
        ])->assertRedirect();
        $this->post(route('changes.submit', $change))->assertRedirect();
        $this->assertEquals('pending_approval', $change->fresh()->status);

        // Technician cannot approve; manager can; requester cannot self-approve.
        $this->post(route('changes.decide', $change), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($manager);
        $this->post(route('changes.decide', $change), ['decision' => 'approved'])->assertRedirect();
        $this->assertEquals('approved', $change->fresh()->status);

        // Approved changes are locked against edits.
        $this->put(route('changes.update', $change), ['title' => 'Changed title'])->assertStatus(422);

        // Record implementation outcome.
        $this->post(route('changes.implement', $change), ['outcome' => 'Completed 02:14, verified.', 'result' => 'implemented'])->assertRedirect();
        $this->assertEquals('implemented', $change->fresh()->status);
    }

    public function test_major_incident_links_and_audience_updates(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $manager = $this->makeUser($tenant, 'manager');
        $commander = $this->makeUser($tenant, 'manager');
        $org = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $requester = $this->makeUser($tenant, 'customer', ['customer_id' => $org->id]);
        $this->actingAs($manager);

        $this->post(route('incidents.store'), [
            'title' => 'Mail outage EU', 'summary' => 'Exchange down', 'commander_id' => $commander->id,
        ])->assertRedirect();
        $incident = MajorIncident::where('tenant_id', $tenant->id)->first();

        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'No mail', 'description' => 'x',
            'priority' => 'urgent', 'customer_id' => $org->id, 'requester_id' => $requester->id]);
        $this->post(route('incidents.tickets.link', $incident), ['ticket_id' => $ticket->id])->assertRedirect();

        $this->post(route('incidents.updates.store', $incident), [
            'body' => 'Failover in progress, ETA 20 min.', 'notify_audience' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $requester->id]);

        $this->post(route('incidents.transition', $incident), ['to' => 'mitigating'])->assertRedirect();

        // Technicians cannot declare major incidents.
        $tech = $this->makeUser($tenant, 'technician');
        $this->actingAs($tech);
        $this->post(route('incidents.store'), ['title' => 'x'])->assertForbidden();
    }
}
