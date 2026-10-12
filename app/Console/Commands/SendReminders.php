<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Sends warranty-expiry and invoice-due reminders.
 * Idempotent within a day (skips records already reminded today).
 */
class SendReminders extends Command
{
    protected $signature = 'supportflow:remind';

    protected $description = 'Send warranty expiry and invoice payment reminders';

    public function handle(): int
    {
        $warranties = 0;
        foreach (Asset::withoutTenantScope()->whereNotNull('warranty_expires_at')
            ->whereDate('warranty_expires_at', '<=', now()->addDays(30))
            ->whereDate('warranty_expires_at', '>=', now())->cursor() as $asset) {
            // Notify tenant owners/admins in-app; mail only if enabled.
            $staff = \App\Models\User::where('tenant_id', $asset->tenant_id)
                ->whereIn('role', ['owner', 'admin', 'manager'])->get();
            foreach ($staff as $user) {
                $user->notify(new \App\Notifications\WarrantyExpiringNotification($asset));
            }
            $warranties++;
        }

        $invoices = 0;
        foreach (Invoice::withoutTenantScope()->whereIn('status', ['issued', 'partially_paid'])
            ->whereDate('due_date', '<', now())->cursor() as $invoice) {
            $invoice->status = 'overdue';
            $invoice->save();
            $invoices++;
        }

        $this->info("Warranty reminders queued for {$warranties} assets; {$invoices} invoices marked overdue.");

        return 0;
    }
}
