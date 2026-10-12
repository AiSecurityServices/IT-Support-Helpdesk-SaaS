<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            throw ValidationException::withMessages(['email' => 'This account has been deactivated. Contact your administrator.']);
        }

        // TOTP second step: park the login until the code is verified.
        if ($user->totp_enabled_at && $user->totp_secret) {
            $remember = $request->boolean('remember');
            Auth::logout();
            $request->session()->put('totp_pending_user', $user->id);
            $request->session()->put('totp_remember', $remember);
            $request->session()->regenerate();

            return redirect()->route('totp.challenge');
        }

        $user->last_login_at = now();
        $user->save();
        \App\Models\AuthEvent::record($user, 'login_success');

        // Configurable tenant MFA enforcement for staff (no lockout: setup is one click).
        if ($user->isStaff() && ! $user->totp_enabled_at
            && $user->tenant && $user->tenant->setting('security.require_mfa', false)) {
            return redirect()->route('totp.setup')
                ->with('error', 'Your workspace requires two-factor authentication. Please enable it now.');
        }

        // Customers land in the portal; staff in the workspace dashboard.
        if ($user->isCustomer()) {
            return redirect()->intended(route('portal.dashboard'));
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        \App\Models\AuthEvent::record($request->user(), 'logout');
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
