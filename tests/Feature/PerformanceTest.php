<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\MakesTenants;
use Tests\TestCase;

/**
 * Workload assumptions: single tenant, 300 tickets, 900 messages, 5 techs.
 * Targets (local sqlite, debug on): dashboard p95 < 3s, ticket list < 3s,
 * ticket show < 40 queries (N+1 guard). CI hardware will differ — see
 * docs/PRODUCTION_READINESS/PERFORMANCE_REPORT.md for the measurement method.
 */
class PerformanceTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function seedWorkload($tenant, $owner): void
    {
        $org = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'Load Co']);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Load']);
        for ($i = 0; $i < 300; $i++) {
            $t = Ticket::create([
                'tenant_id' => $tenant->id, 'subject' => "Load ticket {$i}", 'description' => str_repeat('x', 200),
                'priority' => ['low', 'normal', 'high', 'urgent'][$i % 4],
                'status' => $i % 5 === 0 ? 'resolved' : 'open',
                'customer_id' => $org->id, 'team_id' => $team->id,
            ]);
            for ($m = 0; $m < 3; $m++) {
                $t->messages()->create(['tenant_id' => $tenant->id, 'author_id' => $owner->id,
                    'visibility' => 'public', 'body' => str_repeat('y', 100)]);
            }
        }
    }

    public function test_dashboard_and_listings_stay_fast(): void
    {
        $this->seedPlans();
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->seedWorkload($tenant, $owner);
        $this->actingAs($owner);

        $start = microtime(true);
        $this->get(route('dashboard'))->assertOk();
        $dashboardMs = (microtime(true) - $start) * 1000;

        $start = microtime(true);
        $this->get(route('tickets.index'))->assertOk();
        $listMs = (microtime(true) - $start) * 1000;

        fwrite(STDERR, sprintf("\n[perf] dashboard=%.0fms list=%.0fms (300 tickets)\n", $dashboardMs, $listMs));
        $this->assertLessThan(3000, $dashboardMs, 'Dashboard exceeded 3s with 300 tickets.');
        $this->assertLessThan(3000, $listMs, 'Ticket list exceeded 3s with 300 tickets.');
    }

    public function test_ticket_show_has_no_n_plus_one(): void
    {
        $this->seedPlans();
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->seedWorkload($tenant, $owner);
        $ticket = Ticket::where('tenant_id', $tenant->id)->first();
        $this->actingAs($owner);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        $this->get(route('tickets.show', $ticket))->assertOk();
        fwrite(STDERR, "\n[perf] ticket.show queries={$queries}\n");

        $this->assertLessThan(40, $queries, "N+1 suspected: {$queries} queries for one ticket page.");
    }
}
