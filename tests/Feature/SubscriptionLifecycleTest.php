<?php

namespace Tests\Feature;

use App\Models\BillingEvent;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\MakesTenants;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        Notification::fake();
    }

    protected function signed(string $id, string $type, array $object): array
    {
        $payload = json_encode(['id' => $id, 'type' => $type, 'data' => ['object' => $object]]);
        $stamp = time();
        $sig = 't='.$stamp.',v1='.hash_hmac('sha256', $stamp.'.'.$payload, 'whsec_test');

        return [$payload, ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $sig]];
    }

    public function test_trial_expiry_suspends_workspace(): void
    {
        $tenant = $this->makeTenant('Beta', 'trial');
        $owner = $this->makeUser($tenant, 'owner');
        $sub = $tenant->subscription;
        $sub->trial_ends_at = now()->subDay();
        $sub->save();

        $this->artisan('supportflow:subscriptions')->assertOk();

        $this->assertEquals('expired', $sub->fresh()->status);
        $this->assertEquals('suspended', $tenant->fresh()->status);
        $this->assertDatabaseHas('billing_events', ['tenant_id' => $tenant->id, 'type' => 'trial_expired']);
        Notification::assertSentTo($owner, \App\Notifications\SubscriptionStatusNotification::class);
    }

    public function test_cancel_at_period_end_finalizes_only_after_period(): void
    {
        $tenant = $this->makeTenant('Gamma');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $this->post(route('billing.cancel'), ['at_period_end' => true])->assertRedirect();
        $sub = $tenant->subscription()->latest()->first();
        $this->assertTrue((bool) $sub->cancel_at_period_end);
        $this->assertEquals('active', $sub->status); // access continues

        // Command before period end: no-op.
        $this->artisan('supportflow:subscriptions')->assertOk();
        $this->assertEquals('active', $sub->fresh()->status);

        // Period elapsed: finalized.
        $sub->current_period_end = now()->subMinute();
        $sub->save();
        $this->artisan('supportflow:subscriptions')->assertOk();
        $this->assertEquals('cancelled', $sub->fresh()->status);
        $this->assertDatabaseHas('billing_events', ['type' => 'cancelled']);
    }

    public function test_reactivation_restores_suspended_workspace(): void
    {
        $tenant = $this->makeTenant('Delta', 'suspended');
        $sub = $tenant->subscription;
        $sub->status = 'past_due';
        $sub->grace_ends_at = now()->subDay();
        $sub->save();
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        // Suspended workspaces are confined to billing (recovery path open).
        $this->get(route('dashboard'))->assertRedirect(route('billing.index'));
        $this->get(route('billing.index'))->assertOk();

        $plan = Plan::where('slug', 'starter')->first();

        $this->post(route('billing.checkout', ['plan' => $plan->id, 'interval' => 'monthly']))->assertRedirect();
        $this->assertEquals('active', $sub->fresh()->status);
        $this->assertEquals('active', $tenant->fresh()->status);
    }

    public function test_mid_period_downgrade_records_proration_credit(): void
    {
        $tenant = $this->makeTenant('Epsilon');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);
        $sub = $tenant->subscription; // growth monthly, period started today
        $starter = Plan::where('slug', 'starter')->first();

        $this->post(route('billing.checkout', ['plan' => $starter->id, 'interval' => 'monthly']))->assertRedirect();

        $credit = BillingEvent::where('tenant_id', $tenant->id)->where('type', 'proration_credit')->first();
        $this->assertNotNull($credit);
        // Growth £49/mo, ~30 days remain: 49/30*30 ≈ 49.
        $this->assertEqualsWithDelta(49.0, (float) $credit->amount, 2.0);
        $this->assertEquals('starter', $sub->fresh()->plan->slug);
    }

    public function test_invoice_paid_webhook_renews_and_reactivates(): void
    {
        $tenant = $this->makeTenant('Zeta', 'suspended');
        $sub = $tenant->subscription;
        $sub->status = 'past_due';
        $sub->grace_ends_at = now()->addDays(3);
        $sub->save();
        [$payload, $server] = $this->signed('evt_renew_1', 'invoice.paid', [
            'id' => 'in_123', 'amount_paid' => 4900, 'metadata' => ['tenant_id' => $tenant->id],
        ]);

        $this->call('POST', route('billing.webhook'), [], [], [], $server, $payload)->assertOk();
        // Replay is a safe no-op.
        $this->call('POST', route('billing.webhook'), [], [], [], $server, $payload)->assertOk()->assertJson(['duplicate' => true]);

        $this->assertEquals('active', $sub->fresh()->status);
        $this->assertEquals('active', $tenant->fresh()->status);
        $this->assertDatabaseHas('billing_events', ['type' => 'renewed', 'amount' => 49.00]);
    }

    public function test_refund_and_dispute_events_recorded_and_notified(): void
    {
        $tenant = $this->makeTenant('Eta');
        $owner = $this->makeUser($tenant, 'owner');
        [$p1, $s1] = $this->signed('evt_ref_1', 'charge.refunded', [
            'id' => 'ch_1', 'amount_refunded' => 1900, 'metadata' => ['tenant_id' => $tenant->id],
        ]);
        $this->call('POST', route('billing.webhook'), [], [], [], $s1, $p1)->assertOk();
        $this->assertDatabaseHas('billing_events', ['type' => 'refunded', 'amount' => 19.00]);
        Notification::assertSentTo($owner, \App\Notifications\SubscriptionStatusNotification::class);

        [$p2, $s2] = $this->signed('evt_dsp_1', 'charge.dispute.created', [
            'id' => 'dp_1', 'amount' => 4900, 'metadata' => ['tenant_id' => $tenant->id],
        ]);
        $this->call('POST', route('billing.webhook'), [], [], [], $s2, $p2)->assertOk();
        $this->assertDatabaseHas('billing_events', ['type' => 'disputed', 'amount' => 49.00]);
        // Access continues during disputes.
        $this->assertEquals('active', $tenant->fresh()->status);
    }

    public function test_subscription_updated_syncs_status(): void
    {
        $tenant = $this->makeTenant('Theta');
        $sub = $tenant->subscription;
        $sub->provider_subscription_id = 'sub_abc';
        $sub->save();
        [$payload, $server] = $this->signed('evt_upd_1', 'customer.subscription.updated', [
            'id' => 'sub_abc', 'status' => 'past_due', 'current_period_end' => time() + 86400,
        ]);
        $this->call('POST', route('billing.webhook'), [], [], [], $server, $payload)->assertOk();
        $this->assertEquals('past_due', $sub->fresh()->status);
        $this->assertDatabaseHas('billing_events', ['type' => 'synced']);
    }

    public function test_unmatched_refund_is_skipped_safely(): void
    {
        [$payload, $server] = $this->signed('evt_ref_x', 'charge.refunded', ['id' => 'ch_nope', 'amount_refunded' => 100]);
        $this->call('POST', route('billing.webhook'), [], [], [], $server, $payload)->assertOk();
        $this->assertSame(0, BillingEvent::where('type', 'refunded')->count());
    }
}
