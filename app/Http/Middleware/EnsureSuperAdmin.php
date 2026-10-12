<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Platform super-admin area guard (audited support access only). */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->is_super_admin) {
            abort(403, 'Platform administration is restricted.');
        }

        // MFA is mandatory for platform administrators.
        if (! $user->totp_enabled_at && ! $request->routeIs('totp.*', 'logout')) {
            return redirect()->route('totp.setup')
                ->with('error', 'Platform administrators must enable two-factor authentication first.');
        }

        session(['platform_context' => true]);

        return $next($request);
    }
}
