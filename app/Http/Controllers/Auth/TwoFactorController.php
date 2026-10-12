<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP two-factor authentication (optional, per user).
 * Secrets are encrypted at rest with the application key.
 */
class TwoFactorController extends Controller
{
    protected function google(): Google2FA
    {
        return app(Google2FA::class);
    }

    /** Show setup: new secret + otpauth URL for the authenticator app. */
    public function setup(Request $request)
    {
        $user = $request->user();
        abort_if($user->totp_enabled_at, 400, 'Two-factor authentication is already enabled.');

        $secret = $request->session()->get('totp_setup_secret') ?? $this->google()->generateSecretKey();
        $request->session()->put('totp_setup_secret', $secret);
        $otpauth = $this->google()->getQRCodeUrl(
            config('branding.product_name', 'SupportFlow Cloud'), $user->email, $secret
        );

        return view('auth.totp-setup', compact('secret', 'otpauth'));
    }

    public function enable(Request $request)
    {
        $user = $request->user();
        $request->validate(['code' => 'required|digits:6']);
        $secret = $request->session()->get('totp_setup_secret');
        abort_unless($secret, 422, 'Setup session expired. Restart setup.');

        $valid = $this->google()->verifyKey($secret, $request->input('code'), 1);
        abort_unless($valid, 422, 'Invalid code. Check your authenticator app time settings.');

        $user->forceFill([
            'totp_secret' => encrypt($secret),
            'totp_enabled_at' => now(),
        ])->save();
        $request->session()->forget('totp_setup_secret');
        $request->session()->put('totp_passed', true);

        // Single-use recovery codes, shown once and stored hashed.
        $codes = [];
        \App\Models\RecoveryCode::where('user_id', $user->id)->delete();
        for ($i = 0; $i < 10; $i++) {
            $plain = 'RC-'.strtoupper(\Illuminate\Support\Str::random(4)).'-'.strtoupper(\Illuminate\Support\Str::random(4));
            \App\Models\RecoveryCode::create(['user_id' => $user->id, 'code_hash' => hash('sha256', $plain)]);
            $codes[] = $plain;
        }
        $request->session()->put('totp_recovery_codes', $codes);

        audit('security.totp_enabled');
        \App\Models\AuthEvent::record($user, 'mfa_success', ['action' => 'enabled']);
        $user->notify(new \App\Notifications\SecurityAlertNotification('Two-factor authentication was enabled on your account.'));

        return redirect()->route('totp.recovery')->with('success', 'Two-factor authentication enabled. Save your recovery codes now — they are shown only once.');
    }

    /** One-time display of recovery codes. */
    public function recovery(Request $request)
    {
        $codes = $request->session()->get('totp_recovery_codes', []);
        abort_if(empty($codes) && ! $request->user()->totp_enabled_at, 404);

        return view('auth.totp-recovery', compact('codes'));
    }

    public function regenerateCodes(Request $request)
    {
        $user = $request->user();
        abort_unless($user->totp_enabled_at, 422);
        $request->validate(['password' => 'required|current_password']);
        $codes = [];
        \App\Models\RecoveryCode::where('user_id', $user->id)->delete();
        for ($i = 0; $i < 10; $i++) {
            $plain = 'RC-'.strtoupper(\Illuminate\Support\Str::random(4)).'-'.strtoupper(\Illuminate\Support\Str::random(4));
            \App\Models\RecoveryCode::create(['user_id' => $user->id, 'code_hash' => hash('sha256', $plain)]);
            $codes[] = $plain;
        }
        $request->session()->put('totp_recovery_codes', $codes);
        audit('security.recovery_codes_regenerated');

        return redirect()->route('totp.recovery');
    }

    public function disable(Request $request)
    {
        $user = $request->user();
        $request->validate(['password' => 'required|current_password']);
        $user->forceFill(['totp_secret' => null, 'totp_enabled_at' => null])->save();
        audit('security.totp_disabled');
        $user->notify(new \App\Notifications\SecurityAlertNotification('Two-factor authentication was disabled on your account.'));

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    /** Second login step for users with TOTP enabled. */
    public function challenge()
    {
        abort_unless(session()->has('totp_pending_user'), 403);

        return view('auth.totp-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $request->validate(['code' => 'required|string|max:32']);
        $userId = $request->session()->get('totp_pending_user');
        abort_unless($userId, 403);
        $user = User::findOrFail($userId);
        abort_unless($user->totp_secret, 403);

        $code = trim($request->input('code'));
        $usedRecoveryCode = false;

        if (preg_match('/^RC-[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $code)) {
            // Recovery code path (single-use, hashed at rest).
            $record = \App\Models\RecoveryCode::where('user_id', $user->id)
                ->where('code_hash', hash('sha256', strtoupper($code)))
                ->whereNull('used_at')->first();
            if (! $record) {
                \App\Models\AuthEvent::record($user, 'mfa_failed', ['method' => 'recovery_code']);
                abort(422, 'Invalid recovery code.');
            }
            $record->used_at = now();
            $record->save();
            $usedRecoveryCode = true;
        } else {
            $request->validate(['code' => 'digits:6']);
            $valid = $this->google()->verifyKey(decrypt($user->totp_secret), $code, 1);
            if (! $valid) {
                \App\Models\AuthEvent::record($user, 'mfa_failed', ['method' => 'totp']);
                abort(422, 'Invalid authentication code.');
            }
        }

        $request->session()->forget('totp_pending_user');
        Auth::login($user, $request->session()->get('totp_remember', false));
        $request->session()->put('totp_passed', true);
        $request->session()->regenerate();
        $user->last_login_at = now();
        $user->save();
        \App\Models\AuthEvent::record($user, 'mfa_success', ['method' => $usedRecoveryCode ? 'recovery_code' : 'totp']);
        if ($usedRecoveryCode) {
            $remaining = \App\Models\RecoveryCode::where('user_id', $user->id)->whereNull('used_at')->count();
            if ($remaining <= 2) {
                $user->notify(new \App\Notifications\SecurityAlertNotification("You used a recovery code. Only {$remaining} remain — regenerate them in your profile."));
            }
        }

        if ($user->isCustomer()) {
            return redirect()->intended(route('portal.dashboard'));
        }

        return redirect()->intended(\App\Providers\RouteServiceProvider::HOME);
    }
}
