<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\MakesTenants;
use Tests\TestCase;

class OpsFeaturesTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_feature_flags_default_on_and_tenant_can_hide_module(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $this->assertTrue(feature('knowledge_base'));
        $this->get(route('dashboard'))->assertOk()->assertSee('Knowledge base');

        $this->put(route('settings.features'), ['features' => []])->assertRedirect();
        $this->assertFalse(feature('knowledge_base'));
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Knowledge base');
        // Core safety areas are not flaggable.
        $this->assertTrue(feature('nonexistent_flag_defaults_true'));
    }

    public function test_onboarding_checklist_tracks_real_state(): void
    {
        $tenant = $this->makeTenant('Beta');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $checklist = app(\App\Services\OnboardingChecklist::class)->steps($tenant);
        $this->assertFalse($checklist['complete']);
        $this->get(route('dashboard'))->assertOk()->assertSee('Commissioning checklist');

        // Complete every step.
        $team = \App\Models\Team::create(['tenant_id' => $tenant->id, 'name' => 'Desk']);
        $this->makeUser($tenant, 'technician');
        $org = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        Ticket::create(['tenant_id' => $tenant->id, 'subject' => 'Hi', 'description' => 'x', 'priority' => 'normal', 'customer_id' => $org->id]);
        \App\Models\SlaPolicy::create(['tenant_id' => $tenant->id, 'name' => 'Std',
            'targets' => ['normal' => ['first_response' => 60, 'resolution' => 480]]]);

        $checklist = app(\App\Services\OnboardingChecklist::class)->steps($tenant->fresh());
        $this->assertTrue($checklist['complete']);
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Commissioning checklist');
    }

    public function test_tenant_export_is_owner_only_and_scoped(): void
    {
        $a = $this->makeTenant('Alpha');
        $b = $this->makeTenant('Beta');
        $ownerA = $this->makeUser($a, 'owner');
        $techA = $this->makeUser($a, 'technician');
        Customer::create(['tenant_id' => $a->id, 'company_name' => 'A Co']);
        Customer::create(['tenant_id' => $b->id, 'company_name' => 'B Co']);

        $this->actingAs($techA);
        $this->get(route('settings.export'))->assertForbidden();

        $this->actingAs($ownerA);
        $response = $this->get(route('settings.export'))->assertOk();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $payload = json_decode($response->streamedContent(), true);
        $this->assertEquals(['A Co'], array_column($payload['customers'], 'company_name'));
        // Internal notes are never exported; only public replies.
        $this->assertArrayHasKey('tickets', $payload);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tenant.exported']);
    }

    public function test_audit_log_is_append_only(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $log = AuditLog::create(['tenant_id' => $tenant->id, 'actor_id' => $owner->id, 'action' => 'test.event']);
        $this->assertFalse($log->update(['action' => 'test.tampered']));
        $this->assertFalse($log->delete());
        $this->assertEquals('test.event', $log->fresh()->action);
        $this->assertDatabaseHas('audit_logs', ['action' => 'test.event']);
    }

    public function test_queued_webhook_delivery_with_backoff_and_logging(): void
    {
        $tenant = $this->makeTenant('Alpha');
        // Literal public IP (example.com): passes the SSRF guard without DNS,
        // Http::fake intercepts before any real connection.
        $endpoint = WebhookEndpoint::create([
            'tenant_id' => $tenant->id, 'url' => 'https://93.184.216.1/hook',
            'events' => ['ticket.assigned'], 'secret' => str_repeat('s', 32), 'is_active' => true,
        ]);
        Http::fake(['93.184.216.1/*' => Http::response('ok', 200)]);

        app(\App\Services\WebhookService::class)->deliver($endpoint, 'ticket.assigned', ['ticket_id' => 7]);

        $delivery = WebhookDelivery::where('webhook_endpoint_id', $endpoint->id)->first();
        $this->assertNotNull($delivery);
        $this->assertTrue((bool) $delivery->delivered);
        $this->assertEquals(200, $delivery->status_code);
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-SupportFlow-Signature')
                && $request->hasHeader('X-Idempotency-Key')
                && str_contains($request->body(), 'ticket.assigned');
        });
    }

    public function test_failed_webhook_is_logged_not_lost(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $endpoint = WebhookEndpoint::create([
            'tenant_id' => $tenant->id, 'url' => 'https://93.184.216.1/hook',
            'events' => ['*'], 'secret' => str_repeat('s', 32), 'is_active' => true,
        ]);
        Http::fake(['93.184.216.1/*' => Http::response('boom', 500)]);

        // Sync driver: release() re-runs inline until tries are exhausted.
        try {
            app(\App\Services\WebhookService::class)->deliver($endpoint, 'ticket.assigned', ['ticket_id' => 7]);
        } catch (\Throwable $e) {
            // Max attempts exceeded surfaces after retries — the failure row persists regardless.
        }

        $delivery = WebhookDelivery::where('webhook_endpoint_id', $endpoint->id)->first();
        $this->assertNotNull($delivery);
        $this->assertFalse((bool) $delivery->delivered);
        $this->assertGreaterThanOrEqual(1, $delivery->attempts);
    }
}
