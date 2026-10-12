<?php

namespace Tests\Feature;

use App\Mail\TestMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\MakesTenants;
use Tests\TestCase;

class MailDeliveryTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_preview_renders_and_test_email_sends(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $tech = $this->makeUser($tenant, 'technician');
        Mail::fake();

        $this->actingAs($tech);
        $this->get(route('settings.mail.preview'))->assertForbidden();

        $this->actingAs($owner);
        $this->get(route('settings.mail.preview'))->assertOk()->assertSee('delivery test');

        $this->post(route('settings.mail.send'), ['email' => 'ops@example.test'])->assertRedirect();
        Mail::assertSent(TestMail::class, fn ($m) => $m->hasTo('ops@example.test'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'mail.test_sent']);
    }

    public function test_invalid_recipient_rejected(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);
        Mail::fake();

        $this->post(route('settings.mail.send'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }
}
