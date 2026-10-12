<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\MakesTenants;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_full_ticket_lifecycle(): void
    {
        Notification::fake();
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $tech = $this->makeUser($tenant, 'technician');
        $this->actingAs($owner);

        // Create
        $response = $this->post(route('tickets.store'), [
            'subject' => 'Printer down', 'description' => 'Floor 2 printer offline',
            'priority' => 'high',
        ]);
        $ticket = Ticket::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($ticket);
        $this->assertMatchesRegularExpression('/^TKT-\d+$/', $ticket->number);
        $response->assertRedirect(route('tickets.show', $ticket));

        // Assign (notifies assignee)
        $this->post(route('tickets.assign', $ticket), ['assigned_to' => $tech->id]);
        $this->assertEquals($tech->id, $ticket->fresh()->assigned_to);
        Notification::assertSentTo($tech, \App\Notifications\TicketAssignedNotification::class);

        // Public reply sets first response
        $this->post(route('tickets.reply', $ticket), ['body' => 'On our way.']);
        $this->assertNotNull($ticket->fresh()->first_response_at);

        // Internal note stays internal
        $this->post(route('tickets.note', $ticket), ['body' => 'Spare fuser in stock.']);
        $this->assertEquals(1, $ticket->messages()->where('visibility', 'internal')->count());

        // Invalid transition rejected
        $this->post(route('tickets.transition', $ticket), ['to' => 'nonsense'])->assertStatus(422);

        // Resolve → close
        $this->post(route('tickets.transition', $ticket), ['to' => 'resolved'])->assertRedirect();
        $this->assertNotNull($ticket->fresh()->resolved_at);
        $this->post(route('tickets.transition', $ticket), ['to' => 'closed'])->assertRedirect();

        // Reopen works from closed
        $this->post(route('tickets.transition', $ticket), ['to' => 'open'])->assertRedirect();
        $this->assertEquals('open', $ticket->fresh()->status);

        // History recorded every step
        $this->assertGreaterThanOrEqual(5, $ticket->history()->count());
    }

    public function test_customer_cannot_post_internal_notes_or_edit(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $customer = \App\Models\Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $user = $this->makeUser($tenant, 'customer', ['customer_id' => $customer->id]);
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'Help', 'description' => 'x',
            'priority' => 'normal', 'customer_id' => $customer->id]);

        $this->actingAs($user);
        $this->post(route('tickets.note', $ticket), ['body' => 'sneaky'])->assertForbidden();
        $this->get(route('tickets.edit', $ticket))->assertForbidden();
        // Public reply allowed
        $this->post(route('tickets.reply', $ticket), ['body' => 'More info: room 4.'])->assertRedirect();
    }

    public function test_time_logging_and_billable_totals(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'Work', 'description' => 'x', 'priority' => 'normal']);
        $this->actingAs($tech);

        $this->post(route('tickets.time.store', $ticket), ['minutes' => 90, 'description' => 'Fix', 'billable' => true, 'hourly_rate' => 100]);
        $this->post(route('tickets.time.store', $ticket), ['minutes' => 30, 'description' => 'Call', 'billable' => false]);
        $this->assertEquals(90, $ticket->billableMinutes());
        $this->assertEquals(120, $ticket->timeEntries()->sum('minutes'));
    }
}
