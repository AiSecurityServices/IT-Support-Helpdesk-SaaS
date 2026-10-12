<?php

namespace Tests\Feature;

use App\Models\BillingEvent;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_registration_creates_tenant_trial_and_defaults(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Owner', 'email' => 'owner@newco.test',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'company' => 'NewCo',
        ]);
        $response->assertRedirect('/dashboard');

        $tenant = \App\Models\Tenant::where('name', 'NewCo')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('trial', $tenant->status);
        $this->assertNotNull($tenant->activeSubscription);
        $this->assertEquals('starter', $tenant->activeSubscription->plan->slug);
        $this->assertGreaterThanOrEqual(3, \App\Models\Category::where('tenant_id', $tenant->id)->count());
        $this->assertNotNull(\App\Models\SlaPolicy::where('tenant_id', $tenant->id)->where('is_default', true)->first());
        $this->assertEquals('owner', auth()->user()->role);
    }

    public function test_manual_plan_change_and_cancel(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $growth = Plan::where('slug', 'growth')->first();
        $this->post(route('billing.checkout', ['plan' => $growth->id, 'interval' => 'annual']))->assertRedirect();
        $this->assertEquals('growth', $tenant->activeSubscription->fresh()->plan->slug);

        $this->post(route('billing.cancel'))->assertRedirect();
        $this->assertEquals('cancelled', $tenant->subscription()->latest()->first()->status);
    }

    public function test_webhook_rejects_bad_signature_and_dedupes(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $tenant = $this->makeTenant('Alpha');

        // Bad signature → 400, nothing recorded.
        $this->postJson(route('billing.webhook'), ['x' => 1], ['Stripe-Signature' => 'bogus'])
            ->assertStatus(400);
        $this->assertSame(0, BillingEvent::where('tenant_id', $tenant->id)->count());

        // Valid signature on a handled event → recorded once; replay ignored.
        // (Stripe enforces a timestamp tolerance, so sign with the current time.)
        $payload = json_encode(['id' => 'evt_test_1', 'type' => 'invoice.payment_failed',
            'data' => ['object' => ['metadata' => ['tenant_id' => $tenant->id]]]]);
        $stamp = time();
        $sig = 't='.$stamp.',v1='.hash_hmac('sha256', $stamp.'.'.$payload, 'whsec_test');
        $this->call('POST', route('billing.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $sig,
        ], $payload)->assertOk();
        $this->call('POST', route('billing.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $sig,
        ], $payload)->assertOk()->assertJson(['duplicate' => true]);
    }

    public function test_seat_limit_blocks_invitations(): void
    {
        $tenant = $this->makeTenant('Alpha'); // growth plan = 10 seats
        $owner = $this->makeUser($tenant, 'owner');
        // Fill seats to the limit.
        for ($i = 0; $i < 9; $i++) {
            $this->makeUser($tenant, 'technician');
        }
        $this->actingAs($owner);
        $this->post(route('technicians.invite'), ['email' => 'extra@test.local', 'role' => 'technician'])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('tenant_invitations', ['email' => 'extra@test.local']);
    }
}
