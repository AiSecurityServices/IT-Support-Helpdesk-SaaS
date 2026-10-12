<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Team::class);

        return view('teams.index', [
            'teams' => Team::with(['department', 'lead', 'members'])->where('tenant_id', $this->tenantId())->get(),
            'departments' => Department::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManage('teams'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id', 'lead_id' => 'nullable|exists:users,id',
        ]);
        Team::create($data + ['tenant_id' => $this->tenantId()]);
        audit('team.created');

        return back()->with('success', 'Team created.');
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);
        abort_unless($request->user()->canManage('teams'), 403);
        $team->fill($request->validate([
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id', 'lead_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
        ]))->save();

        return back()->with('success', 'Team updated.');
    }

    public function members(Request $request, Team $team)
    {
        $this->authorize('update', $team);
        abort_unless($request->user()->canManage('teams'), 403);
        $request->validate(['user_ids' => 'required|array', 'user_ids.*' => 'exists:users,id']);
        $valid = User::where('tenant_id', $this->tenantId())->whereIn('id', $request->input('user_ids'))->pluck('id');
        $team->members()->sync($valid);

        return back()->with('success', 'Team membership updated.');
    }

    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);
        abort_if($team->tickets()->whereIn('status', Ticket::OPEN_STATUSES)->exists(), 422, 'Team has open tickets and cannot be deleted.');
        $team->delete();

        return back()->with('success', 'Team deleted.');
    }
}
