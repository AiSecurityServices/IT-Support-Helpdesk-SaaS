<?php

namespace App\Console\Commands;

use App\Services\SlaService;
use Illuminate\Console\Command;

/**
 * Evaluates SLA deadlines for open tickets (warnings, breaches, escalation).
 * Idempotent — safe to run every 5 minutes via scheduler/cron.
 */
class EvaluateSla extends Command
{
    protected $signature = 'sla:evaluate {--tenant= : Limit to a single tenant ID}';

    protected $description = 'Evaluate SLA deadlines, send warnings/breaches, escalate overdue tickets';

    public function handle(SlaService $sla): int
    {
        $stats = $sla->evaluateOpenTickets($this->option('tenant') ? (int) $this->option('tenant') : null);
        $this->info("Checked {$stats['checked']} tickets: {$stats['warned']} warned, {$stats['breached']} breached, {$stats['escalated']} escalated.");
        // Scheduler heartbeat for the /up readiness probe and admin health.
        \Illuminate\Support\Facades\Cache::put('scheduler.heartbeat', now()->toIso8601String(), now()->addHour());

        return 0;
    }
}
