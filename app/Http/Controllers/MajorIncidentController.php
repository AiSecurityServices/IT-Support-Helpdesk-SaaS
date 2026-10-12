<?php

namespace App\Http\Controllers;

use App\Models\MajorIncident;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Major incidents: leadership, linked tickets, audience updates. */
class MajorIncidentController extends Controller
{
    protected function tenantId(): int
    {
        return (int) currentTenantId();
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);

        return view('incidents.index', [
            'incidents' => MajorIncident::with(['commander'])->withCount('tickets')
                ->where('tenant_id', $this->tenantId())->latest()->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);
        $data = $request->validate([
            'title' => 'required|string|max:255', 'summary' => 'nullable|string|max:5000',
            'commander_id' => 'nullable|exists:users,id',
        ]);
        if (! empty($data['commander_id'])) {
            abort_unless(User::where('tenant_id', $this->tenantId())->where('id', $data['commander_id'])->exists(), 422);
        }
        $incident = MajorIncident::create($data + ['tenant_id' => $this->tenantId(), 'status' => 'open']);
        audit('incident.declared', $incident);

        return redirect()->route('incidents.show', $incident)->with('success', 'Major incident declared.');
    }

    public function show(MajorIncident $incident)
    {
        abort_unless((int) $incident->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->hasRole('owner', 'admin', 'manager'), 403);
        $incident->load(['tickets.customer', 'commander', 'updates.author']);

        return view('incidents.show', ['incident' => $incident]);
    }

    public function linkTicket(Request $request, MajorIncident $incident)
    {
        abort_unless((int) $incident->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);
        $request->validate(['ticket_id' => 'required|exists:tickets,id']);
        $ticket = Ticket::where('tenant_id', $this->tenantId())->findOrFail($request->input('ticket_id'));
        $incident->tickets()->syncWithoutDetaching([$ticket->id]);
        audit('incident.linked', $incident, ['ticket' => $ticket->number]);

        return back()->with('success', "Linked {$ticket->number}.");
    }

    public function update(Request $request, MajorIncident $incident)
    {
        abort_unless((int) $incident->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);
        $request->validate(['body' => 'required|string|max:10000', 'notify_audience' => 'nullable|boolean']);

        return DB::transaction(function () use ($request, $incident) {
            $update = $incident->updates()->create([
                'author_id' => $request->user()->id, 'body' => $request->input('body'),
                'notify_audience' => $request->boolean('notify_audience'),
            ]);
            if ($update->notify_audience) {
                $this->notifyAudience($incident, $update->body);
            }
            audit('incident.updated', $incident);

            return back()->with('success', 'Update recorded.');
        });
    }

    public function transition(Request $request, MajorIncident $incident)
    {
        abort_unless((int) $incident->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403);
        $request->validate(['to' => 'required|in:open,mitigating,resolved,closed']);
        $incident->status = $request->input('to');
        $incident->save();
        audit('incident.transition', $incident, ['to' => $incident->status]);

        return back()->with('success', 'Incident status updated.');
    }

    /** Notify requesters of all linked tickets (public-safe summary only). */
    protected function notifyAudience(MajorIncident $incident, string $body): void
    {
        $sent = 0;
        foreach ($incident->tickets as $ticket) {
            $requester = $ticket->requester;
            if ($requester && $sent < 100) {
                $requester->notify(new \App\Notifications\MajorIncidentUpdateNotification($incident, $body));
                $sent++;
            }
        }
    }
}
