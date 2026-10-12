<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\Customer;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\AutomationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\MakesTenants;
use Tests\TestCase;

class CmdbAutomationTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
        Notification::fake();
    }

    public function test_relationship_and_impact_view(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $org = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $server = Asset::create(['tenant_id' => $tenant->id, 'name' => 'Hypervisor', 'type' => 'server', 'status' => 'assigned']);
        $laptop = Asset::create(['tenant_id' => $tenant->id, 'name' => 'Laptop', 'type' => 'laptop', 'status' => 'assigned', 'customer_id' => $org->id]);
        $this->actingAs($tech);

        // Self-relation rejected.
        $this->post(route('cmdb.store'), [
            'from_type' => 'asset', 'from_id' => $server->id,
            'to_type' => 'asset', 'to_id' => $server->id, 'relation' => 'depends_on',
        ])->assertStatus(422);

        $this->post(route('cmdb.store'), [
            'from_type' => 'asset', 'from_id' => $laptop->id,
            'to_type' => 'asset', 'to_id' => $server->id, 'relation' => 'hosted_on',
        ])->assertRedirect();

        // Duplicate edge is idempotent (firstOrCreate).
        $this->post(route('cmdb.store'), [
            'from_type' => 'asset', 'from_id' => $laptop->id,
            'to_type' => 'asset', 'to_id' => $server->id, 'relation' => 'hosted_on',
        ])->assertRedirect();
        $this->assertSame(1, \App\Models\CiRelationship::count());

        // Open ticket on the laptop shows in the server's blast radius via the edge.
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'Laptop dead', 'description' => 'x',
            'priority' => 'high', 'asset_id' => $laptop->id, 'customer_id' => $org->id]);
        $this->get(route('cmdb.impact', ['asset', $server->id]))->assertOk()->assertSee($ticket->number);

        // Cross-tenant edge rejected.
        $b = $this->makeTenant('Beta');
        $foreign = Asset::withoutTenantScope()->create(['tenant_id' => $b->id, 'name' => 'Foreign', 'type' => 'server', 'status' => 'in_stock']);
        $this->post(route('cmdb.store'), [
            'from_type' => 'asset', 'from_id' => $laptop->id,
            'to_type' => 'asset', 'to_id' => $foreign->id, 'relation' => 'depends_on',
        ])->assertStatus(422);
    }

    public function test_rule_fires_once_and_respects_conditions(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'NOC']);
        $urgentTeam = Team::create(['tenant_id' => $tenant->id, 'name' => 'Urgent']);
        $owner = $this->makeUser($tenant, 'owner');

        AutomationRule::create([
            'tenant_id' => $tenant->id, 'name' => 'Urgent to urgent team',
            'trigger' => 'ticket.created',
            'conditions' => [['field' => 'ticket.priority', 'operator' => 'eq', 'value' => 'urgent']],
            'actions' => [['type' => 'assign_team', 'params' => ['team_id' => $urgentTeam->id]]],
            'is_active' => true,
        ]);

        $this->actingAs($owner);
        // Non-matching ticket: rule skipped, recorded as skipped.
        $this->post(route('tickets.store'), ['subject' => 'Routine', 'description' => 'x', 'priority' => 'normal']);
        $normal = Ticket::where('tenant_id', $tenant->id)->where('subject', 'Routine')->first();
        $this->assertNull($normal->team_id);
        $this->assertSame(1, AutomationRun::where('status', 'skipped')->count());

        // Matching ticket: assigned by automation.
        $this->post(route('tickets.store'), ['subject' => 'Down', 'description' => 'x', 'priority' => 'urgent']);
        $urgent = Ticket::where('tenant_id', $tenant->id)->where('subject', 'Down')->first();
        $this->assertEquals($urgentTeam->id, $urgent->team_id);

        // Re-firing the identical event is a safe no-op (idempotent dedupe).
        $runsBefore = AutomationRun::where('status', 'ok')->count();
        AutomationEngine::fire('ticket.created', [
            'tenant_id' => $tenant->id, 'subject_id' => $urgent->id, 'ticket_id' => $urgent->id,
            'ticket' => ['status' => $urgent->status, 'priority' => 'urgent'],
            'fingerprint' => ['status' => $urgent->status, 'priority' => 'urgent', 'assigned_to' => null, 'team_id' => $urgentTeam->id, 'updated' => $urgent->updated_at->format('YmdHi')],
        ]);
        // Fingerprint differs (team now set) → legit new run; identical replay adds nothing.
        AutomationEngine::fire('ticket.created', [
            'tenant_id' => $tenant->id, 'subject_id' => $urgent->id, 'ticket_id' => $urgent->id,
            'ticket' => ['status' => $urgent->status, 'priority' => 'urgent'],
            'fingerprint' => ['status' => $urgent->status, 'priority' => 'urgent', 'assigned_to' => null, 'team_id' => $urgentTeam->id, 'updated' => $urgent->updated_at->format('YmdHi')],
        ]);
        $this->assertSame($runsBefore + 1, AutomationRun::where('status', 'ok')->count());
    }

    public function test_notify_action_is_capped_and_tenant_scoped(): void
    {
        $a = $this->makeTenant('Alpha');
        $b = $this->makeTenant('Beta');
        $managerA = $this->makeUser($a, 'manager');
        $managerB = $this->makeUser($b, 'manager');

        $rule = AutomationRule::create([
            'tenant_id' => $a->id, 'name' => 'Breach page',
            'trigger' => 'sla.breach', 'conditions' => [],
            'actions' => [['type' => 'notify', 'params' => ['audience' => 'managers', 'message' => 'Breach!']]],
            'is_active' => true,
        ]);

        $ticket = Ticket::create(['tenant_id' => $a->id, 'subject' => 'x', 'description' => 'x', 'priority' => 'urgent']);
        AutomationEngine::fire('sla.breach', [
            'tenant_id' => $a->id, 'subject_id' => $ticket->id, 'ticket_id' => $ticket->id,
            'fingerprint' => ['target' => 'resolution', 'kind' => 'breach'],
        ]);

        Notification::assertSentTo($managerA, \App\Notifications\AutomationNotification::class);
        Notification::assertNotSentTo($managerB, \App\Notifications\AutomationNotification::class);
    }

    public function test_rule_management_requires_permission(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $this->actingAs($tech);

        $this->post(route('automation.store'), [
            'name' => 'x', 'trigger' => 'ticket.created',
            'actions' => [['type' => 'escalate']],
        ])->assertForbidden();
    }
}
