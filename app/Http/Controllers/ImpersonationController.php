<?php

namespace App\Http\Controllers;

use App\Models\ImpersonationGrant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Explicit, time-limited, fully audited support impersonation.
 * - Only platform super-admins, only with a recorded reason.
 * - Grants expire (max 30 minutes), are single-target, revocable.
 * - Visible banner while active; every action attributed to the operator.
 * - Target sessions are never hijacked: a fresh session is issued.
 */
class ImpersonationController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.impersonation', [
            'grants' => ImpersonationGrant::with(['target', 'granter'])->latest()->limit(50)->get(),
        ]);
    }

    public function grant(Request $request)
    {
        $data = $request->validate([
            'target_user_id' => 'required|exists:users,id',
            'tenant_id' => 'required|exists:tenants,id',
            'reason' => 'required|string|min:10|max:1000',
            'minutes' => 'required|integer|min:5|max:30',
        ]);

        $target = User::findOrFail($data['target_user_id']);
        abort_unless((int) $target->tenant_id === (int) $data['tenant_id'], 422, 'User does not belong to that tenant.');
        abort_if($target->is_super_admin, 422, 'Platform administrators cannot be impersonated.');
        abort_unless($target->is_active, 422, 'Deactivated accounts cannot be impersonated.');

        $grant = ImpersonationGrant::create([
            'tenant_id' => $data['tenant_id'], 'target_user_id' => $target->id,
            'granted_by' => $request->user()->id, 'reason' => $data['reason'],
            'expires_at' => now('UTC')->addMinutes($data['minutes']),
        ]);
        \App\Models\AuditLog::record(null, $request->user()->id, 'admin.impersonation_granted', $grant, [
            'target' => $target->email, 'reason' => $data['reason'],
        ]);

        return back()->with('success', "Grant created for {$target->email} (expires {$grant->expires_at->format('H:i')}).");
    }

    public function assume(Request $request, ImpersonationGrant $grant)
    {
        abort_unless($grant->isUsable(), 422, 'Grant expired, revoked or already closed.');
        abort_unless(session('platform_context', false), 403);

        $request->session()->put('impersonate.original_id', $request->user()->id);
        $request->session()->put('impersonate.grant_id', $grant->id);
        $request->session()->forget('platform_context');

        $grant->used_at = $grant->used_at ?? now();
        $grant->save();

        Auth::login($grant->target);
        $request->session()->regenerate();
        \App\Models\AuthEvent::record($grant->target, 'impersonation_started', ['grant' => $grant->id, 'by' => $request->user()->id ?? null]);
        \App\Models\AuditLog::record($grant->tenant_id, $request->session()->get('impersonate.original_id'), 'admin.impersonation_started', $grant);

        return redirect()->route('dashboard')->with('error', "Support impersonation ACTIVE ({$grant->target->email}). All actions are audited. Stop from the banner.");
    }

    public function stop(Request $request)
    {
        $originalId = $request->session()->get('impersonate.original_id');
        $grantId = $request->session()->get('impersonate.grant_id');
        abort_unless($originalId, 403);

        $original = User::findOrFail($originalId);
        abort_unless($original->is_super_admin, 403);

        Auth::login($original);
        $request->session()->regenerate();
        $request->session()->forget(['impersonate.original_id', 'impersonate.grant_id']);
        $request->session()->put('platform_context', true);

        if ($grantId && ($grant = ImpersonationGrant::find($grantId))) {
            $grant->ended_at = now();
            $grant->save();
            \App\Models\AuditLog::record(null, $original->id, 'admin.impersonation_ended', $grant);
        }

        return redirect()->route('admin.dashboard')->with('success', 'Impersonation ended. Your own session was restored.');
    }

    public function revoke(Request $request, ImpersonationGrant $grant)
    {
        $grant->revoked_at = now();
        $grant->save();
        \App\Models\AuditLog::record(null, $request->user()->id, 'admin.impersonation_revoked', $grant);

        return back()->with('success', 'Grant revoked.');
    }
}
