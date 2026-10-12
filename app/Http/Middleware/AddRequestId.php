<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Correlation/request IDs on every response + log context (safe: no PII). */
class AddRequestId
{
    public function handle(Request $request, Closure $next)
    {
        $id = (string) ($request->header('X-Request-ID') ?: Str::uuid());
        app()->instance('requestId', $id);
        if (method_exists(\Illuminate\Support\Facades\Log::class, 'shareContext')) {
            \Illuminate\Support\Facades\Log::shareContext(['request_id' => $id]);
        }

        $response = $next($request);
        $response->headers->set('X-Request-ID', $id);

        return $response;
    }
}
