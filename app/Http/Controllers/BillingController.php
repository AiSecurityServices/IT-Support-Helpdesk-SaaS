<?php

namespace App\Http\Controllers;

use App\Models\BillingEvent;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;

class BillingController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()->hasRole('owner', 'admin'), 403);
        $tenant = currentTenant();
        $subscription = $tenant->activeSubscription ?? $tenant->subscription;
        $usage = [
            'technicians' => $tenant->users()->count(),
            'assets' => \App\Models\Asset::where('tenant_id', $tenant->id)->count(),
            'tickets_month' => \App\Models\Ticket::where('tenant_id', $tenant->id)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('billing.index', [
            'plans' => Plan::where('is_active', true)->orderBy('price_monthly')->get(),
            'subscription' => $subscription, 'usage' => $usage,
            'events' => BillingEvent::where('tenant_id', $tenant->id)->latest()->limit(20)->get(),
            'stripe_configured' => (bool) config('services.stripe.secret'),
        ]);
    }

    public function checkout(Request $request, Plan $plan)
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        $request->validate(['interval' => 'required|in:monthly,annual']);
        $interval = $request->input('interval');

        if (! config('services.stripe.secret')) {
            // Manual/test path: record the change without charging.
            return $this->manualChange($request, $plan, $interval);
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $tenant = currentTenant();
        $amount = $interval === 'annual' ? $plan->price_annual : $plan->price_monthly;

        $session = CheckoutSession::create([
            'mode' => 'payment',
            'customer_email' => $request->user()->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($plan->currency ?? 'gbp'),
                    'unit_amount' => (int) round($amount * 100),
                    'product_data' => ['name' => brand('product_name').' — '.$plan->name.' ('.$interval.')'],
                ],
                'quantity' => 1,
            ]],
            'metadata' => ['tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'interval' => $interval],
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.index'),
        ]);

        return redirect()->away($session->url);
    }

    public function success(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        // Never trust the success URL alone: verify with Stripe before activating.
        if (config('services.stripe.secret') && $request->filled('session_id')) {
            Stripe::setApiKey(config('services.stripe.secret'));
            try {
                $session = CheckoutSession::retrieve($request->input('session_id'));
                if ($session->payment_status === 'paid') {
                    $this->applyPaidSubscription(
                        (int) $session->metadata->tenant_id,
                        (int) $session->metadata->plan_id,
                        $session->metadata->interval,
                        $session->payment_intent ?? $session->id,
                        ((float) $session->amount_total) / 100
                    );

                    return redirect()->route('billing.index')->with('success', 'Payment confirmed — subscription active.');
                }
            } catch (\Throwable $e) {
                report($e);

                return redirect()->route('billing.index')->with('error', 'Could not verify payment. If you were charged, contact support.');
            }

            return redirect()->route('billing.index')->with('error', 'Payment not completed.');
        }

        return redirect()->route('billing.index');
    }

    public function manualChange(Request $request, Plan $plan, string $interval)
    {
        $sub = app(\App\Services\SubscriptionService::class)->changePlan(currentTenant(), $plan, $interval);

        return redirect()->route('billing.index')->with('success', "Plan changed to {$plan->name} ({$interval}). Recorded for manual invoicing — no online charge was made.");
    }

    public function cancel(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        $request->validate(['at_period_end' => 'nullable|boolean']);
        $sub = app(\App\Services\SubscriptionService::class)->cancel(currentTenant(), $request->boolean('at_period_end'));

        return back()->with('success', $sub->cancel_at_period_end
            ? 'Cancellation scheduled for the end of the current billing period ('.$sub->current_period_end->format('d M Y').'). Access continues until then.'
            : 'Subscription cancelled. Your data is retained per the retention policy (see docs/BACKUPS.md).');
    }

    public function portal(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        if (! config('services.stripe.secret')) {
            return back()->with('error', 'Stripe is not configured. Set STRIPE_SECRET to enable the billing portal.');
        }
        Stripe::setApiKey(config('services.stripe.secret'));
        $sub = currentTenant()?->activeSubscription;
        $customerId = $sub?->provider_customer_id;
        abort_unless($customerId, 422, 'No Stripe customer is linked to this workspace yet.');
        $portal = \Stripe\BillingPortal\Session::create([
            'customer' => $customerId, 'return_url' => route('billing.index'),
        ]);

        return redirect()->away($portal->url);
    }

    // ---- Webhook (CSRF-exempt, signature-verified, idempotent) ----

    public function webhook(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        if (! $secret) {
            return response()->json(['message' => 'Stripe webhooks not configured.'], 503);
        }
        try {
            $event = StripeWebhook::constructEvent($request->getContent(), $request->header('Stripe-Signature'), $secret);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        // Idempotency: ignore already-processed provider events.
        if (BillingEvent::where('provider_event_id', $event->id)->exists()) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($event),
            'invoice.paid' => $this->onInvoicePaid($event),
            'invoice.payment_failed' => $this->onPaymentFailed($event),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($event),
            'customer.subscription.updated' => $this->onSubscriptionUpdated($event),
            'charge.refunded' => $this->onChargeRefunded($event),
            'charge.dispute.created' => $this->onDisputeCreated($event),
            // Unhandled provider events are logged, never persisted against a
            // fake tenant (tenant_id is a real foreign key).
            default => \Illuminate\Support\Facades\Log::info('Stripe unhandled event', ['id' => $event->id, 'type' => $event->type]),
        };

        return response()->json(['received' => true]);
    }

    protected function onCheckoutCompleted($event): void
    {
        $session = $event->data->object;
        $meta = $session->metadata ?? [];
        if (empty($meta['tenant_id']) || empty($meta['plan_id'])) {
            return;
        }
        $this->applyPaidSubscription(
            (int) $meta['tenant_id'], (int) $meta['plan_id'], $meta['interval'] ?? 'monthly',
            $session->payment_intent ?? $session->id, ((float) ($session->amount_total ?? 0)) / 100,
            $event->id
        );
    }

    protected function applyPaidSubscription(int $tenantId, int $planId, string $interval, string $paymentId, float $amount, ?string $providerEventId = null): void
    {
        DB::transaction(function () use ($tenantId, $planId, $interval, $paymentId, $amount, $providerEventId) {
            $tenant = \App\Models\Tenant::find($tenantId);
            abort_unless($tenant, 404);
            $sub = $tenant->activeSubscription ?? $tenant->subscription;
            $periodEnd = $interval === 'annual' ? now()->addYear() : now()->addMonth();
            if ($sub) {
                $sub->plan_id = $planId;
                $sub->interval = $interval;
                $sub->status = 'active';
                $sub->provider = 'stripe';
                $sub->cancel_at_period_end = false;
                $sub->cancelled_at = null;
                $sub->grace_ends_at = null;
                $sub->current_period_start = now();
                $sub->current_period_end = $periodEnd;
                $sub->save();
            } else {
                $sub = Subscription::create(['tenant_id' => $tenantId, 'plan_id' => $planId,
                    'status' => 'active', 'interval' => $interval, 'provider' => 'stripe',
                    'current_period_start' => now(), 'current_period_end' => $periodEnd]);
            }
            BillingEvent::create(['tenant_id' => $tenantId, 'subscription_id' => $sub->id,
                'type' => 'renewed', 'amount' => $amount, 'currency' => $sub->plan?->currency ?? 'GBP',
                'provider_event_id' => $providerEventId ?? ('pi_'.$paymentId),
                'payload' => ['payment_id' => $paymentId, 'interval' => $interval]]);
            $tenant->status = 'active';
            $tenant->save();
        });
    }

    protected function onPaymentFailed($event): void
    {
        $object = $event->data->object;
        $tenantId = (int) ($object->metadata->tenant_id ?? 0);
        if (! $tenantId) {
            return;
        }
        $sub = Subscription::where('tenant_id', $tenantId)->latest()->first();
        if ($sub) {
            $sub->status = 'past_due';
            $sub->grace_ends_at = now()->addDays(7);
            $sub->save();
            BillingEvent::create(['tenant_id' => $tenantId, 'subscription_id' => $sub->id,
                'type' => 'payment_failed', 'provider_event_id' => $event->id]);
        }
    }

    protected function onSubscriptionDeleted($event): void
    {
        $object = $event->data->object;
        $sub = Subscription::where('provider_subscription_id', $object->id ?? null)->first();
        if ($sub) {
            $sub->status = 'cancelled';
            $sub->cancelled_at = now();
            $sub->save();
            BillingEvent::create(['tenant_id' => $sub->tenant_id, 'subscription_id' => $sub->id,
                'type' => 'cancelled', 'provider_event_id' => $event->id]);
        }
    }

    /** Renewal: roll the billing period, reactivate access, reconcile. */
    protected function onInvoicePaid($event): void
    {
        $object = $event->data->object;
        $tenant = $this->resolveTenant($object, $event->id);
        if (! $tenant) {
            return;
        }
        $sub = $tenant->activeSubscription ?? $tenant->subscription;
        if (! $sub) {
            return;
        }
        DB::transaction(function () use ($tenant, $sub, $object, $event) {
            $periodEnd = isset($object->lines) ? null : null;
            $interval = $sub->interval;
            $sub->status = 'active';
            $sub->provider = 'stripe';
            $sub->cancel_at_period_end = false;
            $sub->cancelled_at = null;
            $sub->grace_ends_at = null;
            $sub->current_period_start = now();
            $sub->current_period_end = $interval === 'annual' ? now()->addYear() : now()->addMonth();
            $sub->save();
            $amount = isset($object->amount_paid) ? ((float) $object->amount_paid) / 100 : null;
            BillingEvent::create(['tenant_id' => $tenant->id, 'subscription_id' => $sub->id,
                'type' => 'renewed', 'amount' => $amount, 'currency' => $sub->plan?->currency ?? 'GBP',
                'provider_event_id' => $event->id,
                'payload' => ['stripe_invoice' => $object->id ?? null]]);
            if ($tenant->status === 'suspended') {
                $tenant->status = 'active';
                $tenant->suspended_at = null;
                $tenant->save();
            }
            // Silence unused-variable analysis for documented future use.
            unset($periodEnd);
        });
    }

    /** Keep provider-side subscription state in sync (status + period). */
    protected function onSubscriptionUpdated($event): void
    {
        $object = $event->data->object;
        $sub = Subscription::where('provider_subscription_id', $object->id ?? null)->first();
        if (! $sub) {
            return;
        }
        $map = ['trialing' => 'trial', 'active' => 'active', 'past_due' => 'past_due',
            'canceled' => 'cancelled', 'unpaid' => 'past_due', 'incomplete' => 'past_due'];
        $status = $map[$object->status ?? ''] ?? null;
        if (! $status) {
            return;
        }
        $sub->status = $status;
        if (isset($object->current_period_end) && is_numeric($object->current_period_end)) {
            $sub->current_period_end = \Carbon\Carbon::createFromTimestampUTC((int) $object->current_period_end);
        }
        if ($status === 'cancelled') {
            $sub->cancelled_at = now();
        }
        $sub->save();
        BillingEvent::create(['tenant_id' => $sub->tenant_id, 'subscription_id' => $sub->id,
            'type' => 'synced', 'provider_event_id' => $event->id,
            'payload' => ['status' => $status]]);
    }

    protected function onChargeRefunded($event): void
    {
        $object = $event->data->object;
        $tenant = $this->resolveTenant($object, $event->id);
        if (! $tenant) {
            return;
        }
        $sub = $tenant->activeSubscription ?? $tenant->subscription;
        $amount = isset($object->amount_refunded) ? ((float) $object->amount_refunded) / 100 : null;
        BillingEvent::create(['tenant_id' => $tenant->id, 'subscription_id' => $sub?->id,
            'type' => 'refunded', 'amount' => $amount, 'currency' => $sub?->plan?->currency ?? 'GBP',
            'provider_event_id' => $event->id, 'payload' => ['charge' => $object->id ?? null]]);
        foreach ($tenant->users()->wherePivot('role', 'owner')->get() as $owner) {
            try {
                $owner->notify(new \App\Notifications\SubscriptionStatusNotification($tenant, 'refunded'));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        audit('billing.refunded', $sub, ['amount' => $amount]);
    }

    protected function onDisputeCreated($event): void
    {
        $object = $event->data->object;
        $tenant = $this->resolveTenant($object, $event->id);
        if (! $tenant) {
            return;
        }
        $sub = $tenant->activeSubscription ?? $tenant->subscription;
        $amount = isset($object->amount) ? ((float) $object->amount) / 100 : null;
        BillingEvent::create(['tenant_id' => $tenant->id, 'subscription_id' => $sub?->id,
            'type' => 'disputed', 'amount' => $amount, 'currency' => $sub?->plan?->currency ?? 'GBP',
            'provider_event_id' => $event->id, 'payload' => ['dispute' => $object->id ?? null]]);
        foreach ($tenant->users()->wherePivot('role', 'owner')->get() as $owner) {
            try {
                $owner->notify(new \App\Notifications\SubscriptionStatusNotification($tenant, 'disputed'));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        audit('billing.disputed', $sub, ['amount' => $amount]);
    }

    /**
     * Resolve the tenant for a provider object without ever guessing:
     * explicit metadata first, then reconciliation against our recorded
     * payment ids, otherwise null (logged, safely skipped).
     */
    protected function resolveTenant($object, string $eventId): ?\App\Models\Tenant
    {
        $metaTenant = (int) ($object->metadata->tenant_id ?? 0);
        if ($metaTenant && ($tenant = \App\Models\Tenant::find($metaTenant))) {
            return $tenant;
        }
        // Reconciliation fallback: match any recorded payment/checkout reference.
        foreach (['payment_intent', 'id'] as $key) {
            $ref = $object->{$key} ?? null;
            if (! $ref || ! is_string($ref)) {
                continue;
            }
            $hit = BillingEvent::where('payload', 'like', '%'.$ref.'%')->first();
            if ($hit && ($tenant = \App\Models\Tenant::find($hit->tenant_id))) {
                return $tenant;
            }
        }
        \Illuminate\Support\Facades\Log::warning('Stripe event without tenant match — skipped safely', ['event' => $eventId]);

        return null;
    }

    /** Service invoices (customer billing) — kept visibly separate. */
    public function serviceInvoices()
    {
        return redirect()->route('invoices.index');
    }
}
