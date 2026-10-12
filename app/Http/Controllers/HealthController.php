<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Public readiness probe (no secrets, no PII): database, storage, queue
 * backlog, failed jobs and scheduler heartbeat for uptime monitors.
 */
class HealthController extends Controller
{
    public function show()
    {
        $checks = [];
        $ok = true;

        try {
            DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
            $ok = false;
        }

        try {
            Storage::disk('local')->put('.health', (string) time());
            Storage::disk('local')->delete('.health');
            $checks['storage'] = 'ok';
        } catch (\Throwable $e) {
            $checks['storage'] = 'fail';
            $ok = false;
        }

        try {
            $checks['failed_jobs'] = DB::table('failed_jobs')->count();
            $checks['queue_backlog'] = DB::table('jobs')->count();
        } catch (\Throwable $e) {
            $checks['failed_jobs'] = 'unknown';
            $checks['queue_backlog'] = 'unknown';
        }

        $heartbeat = Cache::get('scheduler.heartbeat');
        $checks['scheduler_heartbeat'] = $heartbeat ? (string) $heartbeat : 'never';
        if (! $heartbeat || now()->parse($heartbeat)->lt(now()->subMinutes(30))) {
            $checks['scheduler'] = 'stale';
        } else {
            $checks['scheduler'] = 'ok';
        }

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }
}
