<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Daily subscription lifecycle: expire trials, finalize period-end
 * cancellations, suspend past-due workspaces whose grace elapsed.
 * Idempotent — safe to re-run; each transition fires once by status guard.
 */
class ProcessSubscriptions extends Command
{
    protected $signature = 'supportflow:subscriptions';

    protected $description = 'Process trial expiries, period-end cancellations and grace expiries';

    public function handle(SubscriptionService $service): int
    {
        $trials = $service->expireTrials();
        $cancels = $service->finalizePeriodEndCancellations();
        $suspensions = $service->expireGraces();
        $this->info("Trials expired: {$trials}; period-end cancellations: {$cancels}; suspensions: {$suspensions}.");

        return 0;
    }
}
