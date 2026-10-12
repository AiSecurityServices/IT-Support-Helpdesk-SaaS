<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Data-retention enforcement. Conservative by design:
 * - Expired invitations: purge 30 days after expiry.
 * - Automation runs: keep 180 days (operational audit trail).
 * - Read notifications: purge after 365 days.
 * - Mail/bounce events: keep 365 days.
 * - Auth events: keep 365 days (security investigations).
 * Financial records (invoices, payments, billing_events) and audit_logs are
 * NEVER purged here — see docs/DATA_RETENTION_AND_DELETION.md.
 */
class EnforceRetention extends Command
{
    protected $signature = 'supportflow:retention {--dry-run : Report only, delete nothing}';

    protected $description = 'Enforce data-retention windows for ephemeral records';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $report = [];

        $report['invitations'] = $this->purge('tenant_invitations', 'expires_at', now()->subDays(30), $dry);
        $report['automation_runs'] = $this->purge('automation_runs', 'created_at', now()->subDays(180), $dry);
        $report['notifications'] = DB::table('notifications')->whereNotNull('read_at')->where('created_at', '<', now()->subDays(365));
        $report['notifications'] = $dry ? $report['notifications']->count() : $report['notifications']->delete();
        $report['mail_events'] = $this->purge('mail_events', 'created_at', now()->subDays(365), $dry);
        $report['auth_events'] = $this->purge('auth_events', 'created_at', now()->subDays(365), $dry);

        foreach ($report as $k => $v) {
            $this->info(($dry ? '[dry-run] would purge ' : 'purged ').$v.' '.$k);
        }

        return 0;
    }

    protected function purge(string $table, string $column, $before, bool $dry): int
    {
        $q = DB::table($table)->where($column, '<', $before);

        return $dry ? $q->count() : $q->delete();
    }
}
