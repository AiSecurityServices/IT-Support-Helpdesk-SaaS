<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Ensures the user holds one of the given tenant roles. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(...$roles)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
