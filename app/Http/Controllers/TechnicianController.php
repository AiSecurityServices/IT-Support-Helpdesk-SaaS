<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\Team;
use App\Models\TenantInvitation;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManage('teams'), 403);
        $technicians = User::with(['teams', 'skills'])
            ->where('tenant_id', $this->tenantId())->whereIn('role', User::STAFF_ROLES)
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->paginate(15);

        // Workload snapshot: open tickets + logged minutes this month.
        $workload = Ticket::where('tenant_id', $this->tenantId())
            ->whereIn('status', Ticket::OPEN_STATUSES)->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as open_count')->groupBy('assigned_to')->pluck('open_count', 'assigned_to');
        $minutes = TimeEntry::where('tenant_id', $this->tenantId())
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('technician_id, SUM(minutes) as mins')->groupBy('technician_id')->pluck('mins', 'technician_id');

        return view('technicians.index', [
            'technicians' => $technicians, 'workload' => $workload, 'minutes' => $minutes,
            'teams' => Team::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'skills' => Skill::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function show(User $technician)
    {
        abort_unless((int) $technician->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->canManage('teams') || request()->user()->id === $technician->id, 403);

        $openTickets = Ticket::with('customer')->where('tenant_id', $this->tenantId())
            ->where('assigned_to', $technician->id)->whereIn('status', Ticket::OPEN_STATUSES)->get();
        $monthMinutes = TimeEntry::where('tenant_id', $this->tenantId())
            ->where('technician_id', $technician->id)->where('created_at', '>=', now()->startOfMonth())->sum('minutes');
        $monthBillable = TimeEntry::where('tenant_id', $this->tenantId())
            ->where('technician_id', $technician->id)->where('billable', true)
            ->where('created_at', '>=', now()->startOfMonth())->sum('minutes');
        $resolvedPairs = Ticket::where('tenant_id', $this->tenantId())->where('assigned_to', $technician->id)
            ->whereNotNull('resolved_at')->where('created_at', '>=', now()->subDays(90))
            ->select(['created_at', 'resolved_at'])->cursor();
        $total = 0;
        $count = 0;
        foreach ($resolvedPairs as $pair) {
            $total += $pair->created_at->diffInMinutes($pair->resolved_at);
            $count++;
        }
        $avgResolution = $count ? $total / $count : null;

        return view('technicians.show', compact('technician', 'openTickets', 'monthMinutes', 'monthBillable', 'avgResolution'));
    }

    public function update(Request $request, User $technician)
    {
        abort_unless((int) $technician->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->canManage('teams'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255', 'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:255', 'role' => 'required|in:owner,admin,manager,technician',
            'is_active' => 'nullable|boolean', 'team_ids' => 'nullable|string|max:500', 'skill_ids' => 'nullable|string|max:500',
        ]);
        // Owners cannot demote themselves out of the last-owner seat.
        if ($technician->id === $request->user()->id && $technician->role === 'owner' && $data['role'] !== 'owner') {
            $owners = User::where('tenant_id', $this->tenantId())->where('role', 'owner')->count();
            abort_if($owners <= 1, 422, 'You are the last owner — transfer ownership first.');
        }
        $teamIds = $this->parseIds($data['team_ids'] ?? null);
        $skillIds = $this->parseIds($data['skill_ids'] ?? null);
        $technician->fill(collect($data)->except(['team_ids', 'skill_ids'])->toArray())->save();
        if ($request->filled('team_ids')) {
            // team sync uses team_user pivot; guard cross-tenant teams:
            $technician->teams()->sync(
                Team::where('tenant_id', $this->tenantId())->whereIn('id', $teamIds)->pluck('id')
            );
        }
        if ($request->filled('skill_ids')) {
            $names = Skill::where('tenant_id', $this->tenantId())->whereIn('id', $skillIds)->pluck('id');
            $technician->skills()->sync($names);
        }
        audit('technician.updated', $technician);

        return back()->with('success', 'Team member updated.');
    }

    public function invite(Request $request)
    {
        abort_unless($request->user()->canManage('teams'), 403);
        $data = $request->validate([
            'email' => 'required|email|max:255', 'role' => 'required|in:admin,manager,technician',
        ]);
        abort_if(User::where('email', $data['email'])->exists(), 422, 'This email is already registered.');

        $seats = currentTenant()?->activeSubscription?->plan?->technician_seats;
        if ($seats && currentTenant()->users()->count() >= $seats) {
            return back()->with('error', 'Technician seat limit reached for your plan. Upgrade to invite more staff.');
        }

        $invitation = TenantInvitation::create([
            'tenant_id' => $this->tenantId(), 'email' => $data['email'], 'role' => $data['role'],
            'token' => Str::random(48), 'invited_by' => $request->user()->id,
            'expires_at' => now('UTC')->addDays(7),
        ]);

        $url = route('register', ['invitation' => $invitation->token]);
        try {
            \Illuminate\Support\Facades\Notification::route('mail', $data['email'])
                ->notify(new StaffInvitationNotification($invitation, $url));
        } catch (\Throwable $e) {
            report($e);
        }
        audit('invitation.sent', null, ['email' => $data['email'], 'role' => $data['role']]);

        return back()->with('success', 'Invitation sent to '.$data['email'].'.');
    }

    public function deactivate(User $technician)
    {
        abort_unless((int) $technician->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->canManage('teams'), 403);
        abort_if($technician->id === request()->user()->id, 422, 'You cannot deactivate your own account.');
        $openCount = Ticket::where('tenant_id', $this->tenantId())->where('assigned_to', $technician->id)
            ->whereIn('status', Ticket::OPEN_STATUSES)->count();
        abort_if($openCount > 0, 422, "Reassign {$openCount} open ticket(s) before deactivating.");
        $technician->is_active = false;
        $technician->save();
        // Immediate revocation: API tokens die with the account.
        $technician->tokens()->delete();
        audit('technician.deactivated', $technician);

        return back()->with('success', 'Team member deactivated and API tokens revoked.');
    }

    /** Revoke a pending staff invitation (offboarding before acceptance). */
    public function revokeInvitation(Request $request, TenantInvitation $invitation)
    {
        abort_unless((int) $invitation->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->canManage('teams'), 403);
        abort_if($invitation->accepted_at, 422, 'Invitation was already accepted — deactivate the user instead.');
        $invitation->delete();
        audit('invitation.revoked', null, ['email' => $invitation->email]);

        return back()->with('success', 'Invitation revoked.');
    }

    /** Accepts "1,2,3" or [1,2,3]; drops non-numeric values. */
    protected function parseIds($value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = explode(',', (string) $value);
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => is_numeric(trim((string) $v)) ? (int) trim((string) $v) : null, $parts
        ))));
    }
}
