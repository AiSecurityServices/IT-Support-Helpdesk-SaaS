<?php

namespace App\Http\Middleware;

use App\Models\AuthEvent;
use Closure;
use Illuminate\Http\Request;

/** Rejects expired Sanctum tokens and records token usage for audit. */
class EnsureTokenValid
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        // Session-based (transient) tokens carry no expiry or scopes.
        if ($token && $token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            // UTC-explicit: token expiries are stored as UTC wall-clock values.
            $raw = $token->getAttributes()['expires_at'] ?? null;
            $expired = $raw && \Carbon\Carbon::parse($raw, 'UTC')->lessThan(\Carbon\Carbon::now('UTC'));
            if ($expired) {
                AuthEvent::record($user, 'token_used', ['result' => 'expired', 'token' => $token->name]);

                return response()->json(['message' => 'API token has expired.'], 401);
            }
            AuthEvent::record($user, 'token_used', ['token' => $token->name]);
        }

        return $next($request);
    }
}
