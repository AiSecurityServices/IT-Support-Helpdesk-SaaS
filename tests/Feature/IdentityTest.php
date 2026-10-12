<?php

namespace Tests\Feature;

use App\Models\RecoveryCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\MakesTenants;
use Tests\TestCase;

class IdentityTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    protected function enableTotp(User $user): array
    {
        $this->actingAs($user);
        $this->get(route('totp.setup'))->assertOk();
        $secret = session('totp_setup_secret');
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $this->post(route('totp.enable'), ['code' => $code])->assertRedirect(route('totp.recovery'));

        return session('totp_recovery_codes');
    }

    public function test_superadmin_mfa_is_mandatory(): void
    {
        $root = User::create(['name' => 'Root', 'email' => 'root@platform.test',
            'password' => bcrypt('Password123!'), 'role' => 'owner', 'is_super_admin' => true]);
        $root->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($root);

        // Without MFA, platform admin redirects to setup.
        $this->get(route('admin.dashboard'))->assertRedirect(route('totp.setup'));

        // After enabling, access granted.
        $this->enableTotp($root);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_tenant_mfa_enforcement_redirects_to_setup(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tenant->updateSettings(['security' => ['require_mfa' => true]]);
        $tech = $this->makeUser($tenant, 'technician');

        $this->post(route('login'), ['email' => $tech->email, 'password' => 'Password123!'])
            ->assertRedirect(route('totp.setup'));
        $this->assertAuthenticatedAs($tech);
    }

    public function test_recovery_code_login_and_single_use(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $codes = $this->enableTotp($owner);
        $this->assertCount(10, $codes);

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'Password123!'])
            ->assertRedirect(route('totp.challenge'));

        // Login with a recovery code.
        $this->post(route('totp.verify'), ['code' => $codes[0]])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($owner);
        $this->assertNotNull(RecoveryCode::where('user_id', $owner->id)->whereNotNull('used_at')->first());

        // Same code cannot be reused.
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'Password123!']);
        $this->post(route('totp.verify'), ['code' => $codes[0]])->assertStatus(422);
        $this->assertGuest();
    }

    public function test_api_token_scopes_expiry_and_revocation(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $this->actingAs($tech);

        // Issue a read-only token expiring tomorrow.
        $this->post(route('settings.tokens.store'), [
            'name' => 'RO', 'abilities' => ['tickets:read'], 'expires_at' => now()->addDay()->toDateString(),
        ])->assertRedirect()->assertSessionHas('plain_token');
        $plain = session('plain_token');

        // Drop the session so calls authenticate by token only
        // (session auth would otherwise shadow token scopes).
        $this->post(route('logout'))->assertRedirect('/');
        $auth = ['Authorization' => 'Bearer '.$plain];

        // NOTE: guard instances are cached per app in tests, so forget them
        // before each token scenario to force re-authentication from the
        // current request (production boots a fresh container per request).
        \Illuminate\Support\Facades\Auth::forgetGuards();
        // Read allowed…
        $this->getJson('/api/v1/tickets', $auth)->assertOk();
        // …write denied by scope.
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->postJson('/api/v1/tickets', ['subject' => 'x', 'description' => 'y'], $auth)->assertStatus(403);

        // Expired token → 401.
        $expired = $tech->createToken('old', ['tickets:read'], now()->subMinute())->plainTextToken;
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->getJson('/api/v1/tickets', ['Authorization' => 'Bearer '.$expired])->assertUnauthorized();

        // Revocation kills the token immediately.
        $this->actingAs($tech);
        $tokenId = $tech->tokens()->where('name', 'RO')->first()->id;
        $this->delete(route('settings.tokens.revoke', $tokenId))->assertRedirect();
        $this->post(route('logout'));
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->getJson('/api/v1/tickets', $auth)->assertUnauthorized();
    }

    public function test_service_account_cannot_password_login(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $this->post(route('settings.service-accounts.store'), ['name' => 'Monitor'])->assertRedirect();
        $svc = User::where('tenant_id', $tenant->id)->where('is_service_account', true)->first();
        $this->assertNotNull($svc);

        // Password login rejected (and logged).
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $svc->email, 'password' => 'anything'])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('auth_events', ['user_id' => $svc->id, 'event' => 'login_failed']);
    }

    public function test_deactivation_revokes_tokens_and_blocks_login(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $tech = $this->makeUser($tenant, 'technician');
        $tech->createToken('job', ['tickets:read']);
        $this->assertSame(1, $tech->tokens()->count());

        $this->actingAs($owner);
        $this->post(route('technicians.deactivate', $tech))->assertRedirect();
        $this->assertSame(0, $tech->fresh()->tokens()->count());

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $tech->email, 'password' => 'Password123!'])
            ->assertSessionHasErrors('email');
    }

    public function test_invitation_revocation(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);

        $this->post(route('technicians.invite'), ['email' => 'newbie@test.local', 'role' => 'technician'])->assertRedirect();
        $inv = \App\Models\TenantInvitation::where('tenant_id', $tenant->id)->first();
        $this->post(route('technicians.invitations.revoke', $inv))->assertRedirect();
        $this->assertDatabaseMissing('tenant_invitations', ['id' => $inv->id]);
    }

    public function test_auth_events_recorded(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');

        $this->post(route('login'), ['email' => $tech->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('auth_events', ['user_id' => $tech->id, 'event' => 'login_failed']);

        $this->post(route('login'), ['email' => $tech->email, 'password' => 'Password123!'])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('auth_events', ['user_id' => $tech->id, 'event' => 'login_success']);
    }

    public function test_impersonation_grant_assume_stop_cycle(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $target = $this->makeUser($tenant, 'owner');
        $root = User::create(['name' => 'Root', 'email' => 'root@platform.test',
            'password' => bcrypt('Password123!'), 'role' => 'owner', 'is_super_admin' => true]);
        $root->forceFill(['email_verified_at' => now(), 'totp_enabled_at' => now()])->save();
        $this->actingAs($root);

        // Validation: short reason and long durations rejected.
        $this->post(route('admin.impersonation.grant'), [
            'tenant_id' => $tenant->id, 'target_user_id' => $target->id,
            'reason' => 'short', 'minutes' => 15,
        ])->assertSessionHasErrors('reason');
        $this->post(route('admin.impersonation.grant'), [
            'tenant_id' => $tenant->id, 'target_user_id' => $target->id,
            'reason' => 'Investigating ticket visibility complaint #12345',
            'minutes' => 120,
        ])->assertSessionHasErrors('minutes');

        // Super-admins cannot be impersonated.
        $this->post(route('admin.impersonation.grant'), [
            'tenant_id' => $tenant->id, 'target_user_id' => $root->id,
            'reason' => 'Investigating ticket visibility complaint #12345', 'minutes' => 15,
        ])->assertStatus(422);

        $this->post(route('admin.impersonation.grant'), [
            'tenant_id' => $tenant->id, 'target_user_id' => $target->id,
            'reason' => 'Investigating ticket visibility complaint #12345', 'minutes' => 15,
        ])->assertRedirect();
        $grant = \App\Models\ImpersonationGrant::first();

        // Assume: banner route works, audit attributes the operator.
        $this->post(route('admin.impersonation.assume', $grant))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($target);
        $this->get(route('dashboard'))->assertOk()->assertSee('impersonation ACTIVE');
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.impersonation_started']);

        // Stop restores the super-admin.
        $this->post(route('impersonation.stop'))->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($root);
        $this->assertNotNull($grant->fresh()->ended_at);
    }
}
