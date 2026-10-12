<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Resolves the current tenant from the authenticated user and binds it
 * into the container. All tenant-scoped queries depend on this.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            $tenant = \App\Models\Tenant::find($user->tenant_id);
            app()->instance('currentTenant', $tenant);
            app()->instance('currentTenantId', $tenant?->id);

            if ($tenant) {
                // Suspended/cancelled tenants keep billing access so they can
                // pay to reactivate; everything else redirects there (except logout).
                if ($tenant->subscriptionBlocked() && ! $request->routeIs('logout') && ! $request->routeIs('billing.*')) {
                    if ($request->expectsJson()) {
                        abort(403, 'This workspace has been suspended. Please contact support.');
                    }

                    return redirect()->route('billing.index')
                        ->withErrors(['email' => 'This workspace has been suspended. Choose a plan below to reactivate it.']);
                }
                date_default_timezone_set($tenant->timezone ?? config('app.timezone'));
                // Preferred locale: user → tenant → app default.
                app()->setLocale($user->locale ?? $tenant->locale ?? config('app.locale'));
            }
        } else {
            // Never bind null via instance(): isset() treats null instances as
            // missing and rebound() then fatal-errors resolving the name.
            app()->forgetInstance('currentTenant');
            app()->forgetInstance('currentTenantId');
        }

        return $next($request);
    }
}
