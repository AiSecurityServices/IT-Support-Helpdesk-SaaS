<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceCatalogItem;
use App\Models\ServiceRequest;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

class ItsmCatalogTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    protected function makeItem($tenant, $team, bool $approval = true): ServiceCatalogItem
    {
        return ServiceCatalogItem::create([
            'tenant_id' => $tenant->id, 'name' => 'New-user onboarding',
            'description' => 'Laptop + accounts for joiners.',
            'form_schema' => [
                ['key' => 'full_name', 'label' => 'Full name', 'type' => 'text', 'required' => true],
                ['key' => 'needs_laptop', 'label' => 'Needs laptop', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                ['key' => 'laptop_model', 'label' => 'Laptop model', 'type' => 'text', 'required' => true,
                    'depends_on' => ['field' => 'needs_laptop', 'value' => 'yes']],
            ],
            'team_id' => $team->id, 'requires_approval' => $approval,
            'approver_role' => 'manager', 'is_active' => true,
        ]);
    }

    public function test_request_validated_approved_fulfilled_confirmed(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $manager = $this->makeUser($tenant, 'manager');
        $manager2 = $this->makeUser($tenant, 'manager');
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Desk']);
        $org = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $customer = $this->makeUser($tenant, 'customer', ['customer_id' => $org->id]);
        $item = $this->makeItem($tenant, $team);

        $this->actingAs($customer);

        // Missing required field → 422, nothing created.
        $this->post(route('portal.services.request', $item), ['fields' => ['needs_laptop' => 'yes']])->assertStatus(422);
        $this->assertSame(0, ServiceRequest::count());

        // Conditional field enforced: laptop model required when needs_laptop=yes.
        $this->post(route('portal.services.request', $item), ['fields' => [
            'full_name' => 'Jo Bloggs', 'needs_laptop' => 'yes',
        ]])->assertStatus(422);

        // Invalid select option rejected.
        $this->post(route('portal.services.request', $item), ['fields' => [
            'full_name' => 'Jo Bloggs', 'needs_laptop' => 'maybe',
        ]])->assertStatus(422);

        // Valid request → pending approval + fulfilment ticket on the right team.
        $this->post(route('portal.services.request', $item), ['fields' => [
            'full_name' => 'Jo Bloggs', 'needs_laptop' => 'no',
        ]])->assertRedirect();
        $req = ServiceRequest::where('tenant_id', $tenant->id)->first();
        $this->assertEquals('pending_approval', $req->status);
        $this->assertNotNull($req->ticket_id);
        $this->assertEquals($team->id, $req->ticket->team_id);

        // Manager approves → in_progress; requester notified.
        $this->actingAs($manager);
        $this->post(route('catalog.requests.decide', $req), ['decision' => 'approved'])->assertRedirect();
        $this->assertEquals('in_progress', $req->fresh()->status);
        $this->assertCount(1, $req->fresh()->approvals);

        // Fulfil with evidence, then customer confirms.
        $this->post(route('catalog.requests.fulfil', $req), ['completion_evidence' => 'Accounts created, laptop imaged.'])->assertRedirect();
        $this->assertEquals('fulfilled', $req->fresh()->status);
        $this->actingAs($customer);
        $this->post(route('portal.requests.confirm', $req))->assertRedirect();
        $this->assertTrue($req->fresh()->customer_confirmed);
    }

    public function test_separation_of_duties_blocks_self_approval(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $manager = $this->makeUser($tenant, 'manager');
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Desk']);
        $item = $this->makeItem($tenant, $team);

        // Manager files the request themselves…
        $this->actingAs($manager);
        $this->post(route('catalog.request.store', $item), ['fields' => [
            'full_name' => 'Self', 'needs_laptop' => 'no',
        ]])->assertRedirect();
        $req = ServiceRequest::where('tenant_id', $tenant->id)->first();

        // …and cannot approve their own request.
        $this->post(route('catalog.requests.decide', $req), ['decision' => 'approved'])->assertStatus(422);
        $this->assertEquals('pending_approval', $req->fresh()->status);

        // A different manager can.
        $manager2 = $this->makeUser($tenant, 'manager');
        $this->actingAs($manager2);
        $this->post(route('catalog.requests.decide', $req), ['decision' => 'approved'])->assertRedirect();
        $this->assertEquals('in_progress', $req->fresh()->status);
    }

    public function test_tasks_and_cross_tenant_protection(): void
    {
        $a = $this->makeTenant('Alpha');
        $b = $this->makeTenant('Beta');
        $managerA = $this->makeUser($a, 'manager');
        $team = Team::create(['tenant_id' => $a->id, 'name' => 'Desk']);
        $item = $this->makeItem($a, $team, false);

        $this->actingAs($managerA);
        $this->post(route('catalog.request.store', $item), ['fields' => [
            'full_name' => 'Jo', 'needs_laptop' => 'no',
        ]])->assertRedirect();
        $req = ServiceRequest::where('tenant_id', $a->id)->first();
        $this->assertEquals('approved', $req->status); // no approval needed

        $this->post(route('catalog.requests.tasks.store', $req), ['title' => 'Create AD account'])->assertRedirect();
        $task = $req->tasks()->first();
        $this->post(route('catalog.tasks.complete', $task), ['status' => 'done', 'evidence' => 'j.bloggs created'])->assertRedirect();
        $this->assertEquals('done', $task->fresh()->status);

        // Tenant B staff cannot see Tenant A's request.
        $managerB = $this->makeUser($b, 'manager');
        $this->actingAs($managerB);
        $this->get(route('catalog.requests.show', $req))->assertNotFound();
    }
}
