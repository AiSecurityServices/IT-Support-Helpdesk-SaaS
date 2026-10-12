<?php

namespace Tests\Feature;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Services\SlaCalculator;
use App\Services\SlaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

class SlaTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_business_hour_deadline_skips_weekends(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $calc = new SlaCalculator($tenant);
        // Friday 16:00 + 120 business minutes (90 left on Fri) → Monday 09:30.
        $friday = Carbon::parse('2026-10-09 16:00', 'Europe/London');
        $deadline = $calc->addBusinessMinutes($friday, 120);
        $this->assertEquals('2026-10-12 09:30', $deadline->format('Y-m-d H:i'));
        $this->assertEquals(1, (int) $deadline->format('N')); // Monday
    }

    public function test_holidays_are_excluded(): void
    {
        $tenant = $this->makeTenant('Alpha');
        \App\Models\Holiday::create(['tenant_id' => $tenant->id, 'name' => 'Xmas', 'date' => '2026-12-25']);
        $calc = new SlaCalculator($tenant);
        $christmasEve = Carbon::parse('2026-12-24 16:00', 'Europe/London'); // Thursday
        $deadline = $calc->addBusinessMinutes($christmasEve, 120);
        // 90 min left on Thu (16:00→17:30), 25th is holiday, so remaining 30 min land Mon 28th 09:30.
        $this->assertEquals('2026-12-28 09:30', $deadline->format('Y-m-d H:i'));
    }

    public function test_pending_customer_time_extends_deadline(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $policy = SlaPolicy::create(['tenant_id' => $tenant->id, 'name' => 'Std',
            'targets' => ['normal' => ['first_response' => 60, 'resolution' => 480]],
            'pause_on_pending_customer' => true, 'is_default' => true, 'is_active' => true]);
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'S', 'description' => 'x',
            'priority' => 'normal', 'sla_policy_id' => $policy->id]);

        $calc = new SlaCalculator($tenant);
        $base = $calc->deadlinesFor($ticket)['resolution_deadline'];

        // Simulate a 60-business-minute pause window (forceFill: timestamps are guarded).
        \App\Models\TicketHistory::withoutTenantScope()->forceCreate([
            'tenant_id' => $tenant->id, 'ticket_id' => $ticket->id, 'action' => 'status_paused',
            'created_at' => Carbon::parse('2026-10-12 10:00', 'Europe/London'),
            'updated_at' => Carbon::parse('2026-10-12 10:00', 'Europe/London'),
        ]);
        \App\Models\TicketHistory::withoutTenantScope()->forceCreate([
            'tenant_id' => $tenant->id, 'ticket_id' => $ticket->id, 'action' => 'status_resumed',
            'created_at' => Carbon::parse('2026-10-12 11:00', 'Europe/London'),
            'updated_at' => Carbon::parse('2026-10-12 11:00', 'Europe/London'),
        ]);

        $extended = $calc->deadlinesFor($ticket->fresh())['resolution_deadline'];
        $this->assertEquals(60, $base->diffInMinutes($extended));
    }

    public function test_evaluation_is_idempotent(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $ticket = Ticket::forceCreate(['tenant_id' => $tenant->id, 'subject' => 'Old', 'description' => 'x',
            'priority' => 'low', 'status' => 'open',
            'created_at' => now()->subDays(60), 'updated_at' => now()->subDays(60)]);

        $service = new SlaService(new SlaCalculator($tenant));
        $first = $service->evaluateOpenTickets($tenant->id);
        $second = $service->evaluateOpenTickets($tenant->id);

        $this->assertGreaterThan(0, $first['breached']);
        $this->assertSame(0, $second['breached'], 'Second run must not duplicate breach events');
        // One breach per target (first_response + resolution), recorded exactly once.
        $this->assertSame(2, \App\Models\SlaEvent::withoutTenantScope()
            ->where('ticket_id', $ticket->id)->where('type', 'breached')->count());
    }
}
