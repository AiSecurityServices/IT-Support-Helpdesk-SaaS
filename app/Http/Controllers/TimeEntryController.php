<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TimeEntry;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $data = $request->validate([
            'description' => 'nullable|string|max:2000',
            'minutes' => 'required|integer|min:1|max:1440',
            'billable' => 'nullable|boolean',
            'hourly_rate' => 'nullable|numeric|min:0|max:10000',
            'started_at' => 'nullable|date',
        ]);
        $entry = $ticket->timeEntries()->create($data + [
            'tenant_id' => $ticket->tenant_id,
            'technician_id' => $request->user()->id,
            'status' => 'logged',
            'billable' => $request->boolean('billable', true),
        ]);
        \App\Models\TicketHistory::create([
            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
            'actor_id' => $request->user()->id, 'action' => 'time_logged',
            'changes' => ['minutes' => $entry->minutes, 'billable' => $entry->billable],
        ]);

        return back()->with('success', 'Time logged.');
    }

    public function start(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        abort_if(TimeEntry::where('tenant_id', $ticket->tenant_id)
            ->where('technician_id', $request->user()->id)->where('status', 'running')->exists(),
            422, 'You already have a running timer. Stop it first.');
        $request->validate(['description' => 'nullable|string|max:2000']);
        TimeEntry::create([
            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
            'technician_id' => $request->user()->id, 'description' => $request->input('description'),
            'minutes' => 0, 'billable' => true, 'started_at' => now(), 'status' => 'running',
        ]);

        return back()->with('success', 'Timer started.');
    }

    public function stop(Request $request, TimeEntry $entry)
    {
        abort_unless((int) $entry->tenant_id === $this->tenantId(), 404);
        abort_unless($entry->technician_id === $request->user()->id || $request->user()->canManage('teams'), 403);
        abort_unless($entry->status === 'running', 422, 'Timer is not running.');
        $entry->ended_at = now();
        $entry->minutes = max(1, (int) $entry->started_at->diffInMinutes($entry->ended_at));
        $entry->status = 'logged';
        $entry->save();

        return back()->with('success', "Timer stopped: {$entry->minutes} min logged.");
    }

    public function destroy(TimeEntry $entry)
    {
        abort_unless((int) $entry->tenant_id === $this->tenantId(), 404);
        abort_if($entry->status === 'invoiced', 422, 'Invoiced time cannot be deleted.');
        $this->authorize('update', $entry->ticket);
        $entry->delete();

        return back()->with('success', 'Time entry removed.');
    }
}
