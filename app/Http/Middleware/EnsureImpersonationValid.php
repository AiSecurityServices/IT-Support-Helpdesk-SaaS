<?php

namespace App\Http\Middleware;

use App\Models\ImpersonationGrant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Validates active impersonation sessions on every web request:
 * expired/revoked grants immediately end the assumed session.
 */
class EnsureImpersonationValid
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('impersonate.original_id')) {
            $grant = ImpersonationGrant::find($request->session()->get('impersonate.grant_id'));
            if (! $grant || ! $grant->isUsable()) {
                $request->session()->forget(['impersonate.original_id', 'impersonate.grant_id', 'platform_context']);
                Auth::logout();
                $request->session()->invalidate();

                return redirect()->route('login')->withErrors(['email' => 'The support session expired and was closed securely.']);
            }
        }

        return $next($request);
    }
}
