<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

/** Regression tests for previously identified security issues. */
class SecurityRegressionTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_ssrf_policy_blocks_internal_targets(): void
    {
        $svc = app(WebhookService::class);

        $this->assertSame('private_ip_literal', $svc->ssrfBlockReason('http://127.0.0.1/hook'));
        $this->assertSame('private_ip_literal', $svc->ssrfBlockReason('http://169.254.169.254/latest/meta-data'));
        $this->assertSame('private_ip_literal', $svc->ssrfBlockReason('http://10.0.0.5/x'));
        $this->assertSame('private_ip_literal', $svc->ssrfBlockReason('http://192.168.1.1/x'));
        $this->assertSame('scheme_not_allowed', $svc->ssrfBlockReason('file:///etc/passwd'));
        $this->assertSame('scheme_not_allowed', $svc->ssrfBlockReason('ftp://example.com/x'));
        $this->assertSame('scheme_not_allowed', $svc->ssrfBlockReason('gopher://example.com/x'));
        $this->assertSame('unresolvable_host', $svc->ssrfBlockReason('https://nonexistent-host-xyz.invalid/hook'));
    }

    public function test_blocked_webhook_never_fetches_and_is_logged(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $endpoint = \App\Models\WebhookEndpoint::create([
            'tenant_id' => $tenant->id, 'url' => 'http://169.254.169.254/hook',
            'events' => ['ticket.assigned'], 'secret' => 's', 'is_active' => true,
        ]);

        app(WebhookService::class)->deliver($endpoint, 'ticket.assigned', ['a' => 1]);

        $delivery = \App\Models\WebhookDelivery::where('webhook_endpoint_id', $endpoint->id)->first();
        $this->assertNotNull($delivery);
        $this->assertFalse((bool) $delivery->delivered);
    }

    public function test_notification_redirect_cannot_leave_the_app(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $this->actingAs($tech);

        $notification = $tech->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'x', 'data' => ['url' => 'https://evil.example/steal'],
        ]);

        $this->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard'));
    }

    public function test_security_and_request_id_headers_present(): void
    {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
    }

    public function test_customer_cannot_elevate_role_via_profile(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $org = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $customer = $this->makeUser($tenant, 'customer', ['customer_id' => $org->id]);
        $this->actingAs($customer);

        // Mass-assignment attempt on privileged fields.
        $this->patch(route('profile.update'), [
            'name' => 'Sneaky', 'role' => 'owner', 'is_super_admin' => true,
            'tenant_id' => $tenant->id + 999,
        ])->assertRedirect();
        $fresh = $customer->fresh();
        $this->assertEquals('customer', $fresh->role);
        $this->assertFalse((bool) $fresh->is_super_admin);
        $this->assertEquals($tenant->id, (int) $fresh->tenant_id);
    }
}
