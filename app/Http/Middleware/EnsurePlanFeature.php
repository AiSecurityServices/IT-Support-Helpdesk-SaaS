<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Blocks staff-only features that the tenant's subscription plan
 * does not entitle (e.g. sla, invoicing, api). Customer portal is
 * always allowed; suspension is handled by SetTenantContext.
 */
class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $tenant = currentTenant();

        if ($tenant) {
            $plan = $tenant->activeSubscription?->plan;
            $features = $plan?->features ?? ['tickets', 'customer_portal', 'assets_basic', 'reports_basic'];
            if (! in_array($feature, $features, true)) {
                return redirect()->route('billing.index')
                    ->with('error', 'Your current plan does not include this feature. Please upgrade to continue.');
            }
        }

        return $next($request);
    }
}
