<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\MakesTenants;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_enable_and_login_with_totp(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        // Setup stores a secret in session.
        $this->get(route('totp.setup'))->assertOk();
        $secret = session('totp_setup_secret');
        $this->assertNotEmpty($secret);

        // Wrong code rejected.
        $this->post(route('totp.enable'), ['code' => '000000'])->assertStatus(422);

        // Correct code enables + issues one-time recovery codes.
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $this->post(route('totp.enable'), ['code' => $code])->assertRedirect(route('totp.recovery'));
        $this->assertNotNull($owner->fresh()->totp_enabled_at);
        $this->assertCount(10, session('totp_recovery_codes'));
        $this->get(route('totp.recovery'))->assertOk()->assertSee('RC-');

        // Logout → password login parks at challenge.
        $this->post(route('logout'))->assertRedirect('/');
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'Password123!'])
            ->assertRedirect(route('totp.challenge'));
        $this->assertGuest();

        // Wrong challenge code stays guest.
        $this->post(route('totp.verify'), ['code' => '000000'])->assertStatus(422);

        // Correct code completes login.
        $code = app(Google2FA::class)->getCurrentOtp(decrypt($owner->fresh()->totp_secret));
        $this->post(route('totp.verify'), ['code' => $code])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_disable_requires_password(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $owner->forceFill(['totp_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'totp_enabled_at' => now()])->save();
        $this->actingAs($owner);

        $this->delete(route('totp.disable'), ['password' => 'wrong'])->assertRedirect(); // validation fails
        $this->assertNotNull($owner->fresh()->totp_enabled_at);

        $this->delete(route('totp.disable'), ['password' => 'Password123!'])->assertRedirect();
        $this->assertNull($owner->fresh()->totp_enabled_at);
    }
}
