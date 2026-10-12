<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

class PrivacyOpsTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_personal_export_and_request_id_header(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $this->actingAs($tech);

        $response = $this->get(route('privacy.export'))->assertOk();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
        $payload = json_decode($response->streamedContent(), true);
        $this->assertEquals($tech->email, $payload['profile']['email']);
        $this->assertArrayNotHasKey('password', $payload['profile']);
    }

    public function test_tenant_closure_revokes_tokens_and_cancels(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $owner->createToken('job', ['tickets:read']);
        $this->actingAs($owner);

        $this->post(route('settings.close'), ['confirm' => true, 'reason' => 'Moving on'])->assertRedirect('/');
        $this->assertEquals('cancelled', $tenant->fresh()->status);
        $this->assertSame(0, $owner->fresh()->tokens()->count());
        $this->assertEquals('cancelled', $tenant->fresh()->subscription->status);
        $this->assertGuest();
    }

    public function test_retention_purges_only_ephemeral_records(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');

        \App\Models\TenantInvitation::create(['tenant_id' => $tenant->id, 'email' => 'old@test.local',
            'role' => 'technician', 'token' => 'x', 'expires_at' => now()->subDays(60)]);
        $invoice = \App\Models\Invoice::create(['tenant_id' => $tenant->id,
            'customer_id' => Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One'])->id,
            'status' => 'issued', 'currency' => 'GBP', 'total' => 10]);

        $this->artisan('supportflow:retention', ['--dry-run' => true])->assertOk();
        $this->assertDatabaseHas('tenant_invitations', ['email' => 'old@test.local']);

        $this->artisan('supportflow:retention')->assertOk();
        $this->assertDatabaseMissing('tenant_invitations', ['email' => 'old@test.local']);
        // Financial records are never purged.
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_mail_webhook_requires_secret_and_records_bounces(): void
    {
        config(['mail.webhook_secret' => 's3cr3t']);
        // Unsigned → 403, nothing stored.
        $this->postJson(route('webhooks.mail'), ['recipient' => 'a@test.local', 'kind' => 'bounced'])->assertForbidden();
        $this->assertSame(0, \App\Models\MailEvent::count());

        $this->postJson(route('webhooks.mail'),
            ['recipient' => 'a@test.local', 'kind' => 'bounced'],
            ['X-Mail-Signature' => 's3cr3t'])->assertOk();
        $this->assertDatabaseHas('mail_events', ['recipient' => 'a@test.local', 'kind' => 'bounced']);
    }

    public function test_health_probe_reports_status(): void
    {
        $response = $this->getJson('/up')->assertOk();
        $response->assertJsonStructure(['status', 'checks']);
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
        $this->assertEquals('ok', $response->json('checks.database'));
    }

    public function test_spanish_auth_strings_and_user_locale(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician', ['locale' => 'es']);
        $this->actingAs($tech);

        $this->assertEquals('es', $tech->locale);
        $this->assertEquals(
            'Estas credenciales no coinciden con nuestros registros.',
            __('auth.failed', [], 'es')
        );
        // Locale preference is updatable from the profile.
        $this->patch(route('profile.update'), ['name' => $tech->name, 'locale' => 'es'])->assertRedirect();
    }

    public function test_security_settings_toggle(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $this->put(route('settings.security'), ['require_mfa' => true, 'separation_of_duties' => false])->assertRedirect();
        $this->assertTrue($tenant->fresh()->setting('security.require_mfa'));
        $this->assertFalse($tenant->fresh()->setting('approvals.separation_of_duties'));
    }
}
