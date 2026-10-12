<?php

namespace App\Services;

use App\Models\BillingEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Central subscription state machine (manual + Stripe paths share it).
 *
 * Lifecycle: trial → active ⇄ past_due → grace → suspended (tenant) → …
 * cancelled (access retained for reactivation) → expired (post-retention).
 *
 * Money: proration credits use decimal-safe maths on monthly equivalents.
 * All transitions are transactional and recorded as billing events.
 */
class SubscriptionService
{
    public function recordEvent(Tenant $tenant, ?Subscription $sub, string $type, array $data = []): BillingEvent
    {
        return BillingEvent::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub?->id,
            'type' => $type,
            'amount' => isset($data['amount']) ? round((float) $data['amount'], 2) : null,
            'currency' => $data['currency'] ?? null,
            'provider_event_id' => $data['provider_event_id'] ?? null,
            'payload' => $data['payload'] ?? null,
        ]);
    }

    /**
     * Unused-value credit when changing plan mid-period, based on the old
     * plan's monthly equivalent. Returns the credit (>= 0, 2dp).
     */
    public function prorationCredit(Subscription $sub): float
    {
        $plan = $sub->plan;
        if (! $plan || ! $sub->current_period_start || ! $sub->current_period_end) {
            return 0.0;
        }
        $start = $sub->current_period_start;
        $end = $sub->current_period_end;
        $totalDays = max(1, $start->diffInDays($end));
        $remainingDays = max(0, (int) now()->startOfDay()->diffInDays($end, false));
        if ($remainingDays <= 0) {
            return 0.0;
        }
        $monthly = $sub->interval === 'annual'
            ? (float) $plan->price_annual / 12
            : (float) $plan->price_monthly;
        $daily = $monthly / 30;

        return round($daily * $remainingDays, 2);
    }

    public function changePlan(Tenant $tenant, Plan $plan, string $interval): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $interval) {
            $sub = $tenant->activeSubscription ?? $tenant->subscription;
            $previous = $sub?->plan?->slug;
            $credit = $sub ? $this->prorationCredit($sub) : 0.0;

            if ($sub) {
                $sub->plan_id = $plan->id;
                $sub->interval = $interval;
                $sub->status = in_array($sub->status, ['trial', 'cancelled', 'expired'], true) ? ($sub->status === 'trial' ? 'trial' : 'active') : 'active';
                $sub->cancel_at_period_end = false;
                $sub->cancelled_at = null;
                $sub->grace_ends_at = null;
                $sub->save();
            } else {
                $sub = Subscription::create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id,
                    'status' => 'active', 'interval' => $interval, 'provider' => 'manual',
                    'current_period_start' => now(), 'current_period_end' => now()->addMonth()]);
            }
            if ($credit > 0) {
                $this->recordEvent($tenant, $sub, 'proration_credit', [
                    'amount' => $credit, 'currency' => $plan->currency,
                    'payload' => ['from' => $previous, 'to' => $plan->slug],
                ]);
            }
            $this->recordEvent($tenant, $sub, $previous ? 'plan_changed' : 'subscribed', [
                'payload' => ['from' => $previous, 'to' => $plan->slug, 'interval' => $interval, 'proration_credit' => $credit],
            ]);
            if (! in_array($tenant->status, ['suspended', 'cancelled'], true)) {
                $tenant->status = 'active';
                $tenant->save();
            } elseif ($tenant->status === 'suspended' && $sub->isActive()) {
                // Recovery: paying again lifts a billing suspension.
                $tenant->status = 'active';
                $tenant->suspended_at = null;
                $tenant->save();
            }
            audit('billing.plan_changed', $sub, ['plan' => $plan->slug, 'credit' => $credit]);

            return $sub->fresh();
        });
    }

    public function cancel(Tenant $tenant, bool $atPeriodEnd): Subscription
    {
        return DB::transaction(function () use ($tenant, $atPeriodEnd) {
            $sub = $tenant->activeSubscription ?? $tenant->subscription;
            abort_unless($sub, 404);
            if ($atPeriodEnd && $sub->current_period_end && $sub->current_period_end->isFuture()) {
                $sub->cancel_at_period_end = true;
                $sub->save();
                $this->recordEvent($tenant, $sub, 'cancel_scheduled', [
                    'payload' => ['effective' => $sub->current_period_end->toIso8601String()],
                ]);
                audit('billing.cancel_scheduled', $sub);
            } else {
                $sub->status = 'cancelled';
                $sub->cancelled_at = now();
                $sub->cancel_at_period_end = false;
                $sub->save();
                $this->recordEvent($tenant, $sub, 'cancelled');
                audit('billing.cancelled', $sub);
            }

            return $sub->fresh();
        });
    }

    /** Expire trials whose end passed: suspend workspace, notify owner. */
    public function expireTrials(): int
    {
        $count = 0;
        Subscription::where('status', 'trial')->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())->with('tenant')->chunkById(100, function ($subs) use (&$count) {
                foreach ($subs as $sub) {
                    DB::transaction(function () use ($sub, &$count) {
                        $sub->status = 'expired';
                        $sub->save();
                        $tenant = $sub->tenant;
                        if ($tenant && $tenant->status !== 'cancelled') {
                            $tenant->status = 'suspended';
                            $tenant->suspended_at = now();
                            $tenant->save();
                        }
                        $this->recordEvent($tenant, $sub, 'trial_expired');
                        $this->notifyOwners($tenant, 'trial_expired');
                        $count++;
                    });
                }
            });

        return $count;
    }

    /** Finalize period-end cancellations whose period has elapsed. */
    public function finalizePeriodEndCancellations(): int
    {
        $count = 0;
        Subscription::where('cancel_at_period_end', true)->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now())->with('tenant')->chunkById(100, function ($subs) use (&$count) {
                foreach ($subs as $sub) {
                    DB::transaction(function () use ($sub, &$count) {
                        $sub->status = 'cancelled';
                        $sub->cancelled_at = now();
                        $sub->cancel_at_period_end = false;
                        $sub->save();
                        $this->recordEvent($sub->tenant, $sub, 'cancelled', ['payload' => ['at_period_end' => true]]);
                        $count++;
                    });
                }
            });

        return $count;
    }

    /** Grace windows that elapsed suspend the workspace (reversible on payment). */
    public function expireGraces(): int
    {
        $count = 0;
        Subscription::whereIn('status', ['past_due', 'grace'])->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<=', now())->with('tenant')->chunkById(100, function ($subs) use (&$count) {
                foreach ($subs as $sub) {
                    DB::transaction(function () use ($sub, &$count) {
                        $tenant = $sub->tenant;
                        if ($tenant && $tenant->status !== 'cancelled') {
                            $tenant->status = 'suspended';
                            $tenant->suspended_at = now();
                            $tenant->save();
                        }
                        $this->recordEvent($tenant, $sub, 'suspended', ['payload' => ['reason' => 'grace_expired']]);
                        $this->notifyOwners($tenant, 'suspended');
                        $count++;
                    });
                }
            });

        return $count;
    }

    protected function notifyOwners(?Tenant $tenant, string $reason): void
    {
        if (! $tenant) {
            return;
        }
        $owners = $tenant->users()->wherePivot('role', 'owner')->where('is_active', true)->get();
        foreach ($owners as $owner) {
            try {
                $owner->notify(new \App\Notifications\SubscriptionStatusNotification($tenant, $reason));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
