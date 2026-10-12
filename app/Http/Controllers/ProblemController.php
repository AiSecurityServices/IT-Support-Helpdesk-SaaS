<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Models\Ticket;
use Illuminate\Http\Request;

/** Problem management: recurring issues, root cause, known errors, linked incidents. */
class ProblemController extends Controller
{
    protected function tenantId(): int
    {
        return (int) currentTenantId();
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);

        return view('problems.index', [
            'problems' => Problem::with(['owner'])->withCount('tickets')
                ->where('tenant_id', $this->tenantId())->latest()->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager', 'technician'), 403);
        $data = $this->validateProblem($request);
        $problem = Problem::create($data + ['tenant_id' => $this->tenantId()]);
        audit('problem.created', $problem);

        return redirect()->route('problems.show', $problem)->with('success', 'Problem recorded.');
    }

    public function show(Problem $problem)
    {
        abort_unless((int) $problem->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->isStaff(), 403);
        $problem->load(['tickets.customer', 'owner']);

        return view('problems.show', compact('problem'));
    }

    public function update(Request $request, Problem $problem)
    {
        abort_unless((int) $problem->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->isStaff(), 403);
        $problem->fill($this->validateProblem($request))->save();
        audit('problem.updated', $problem);

        return back()->with('success', 'Problem updated.');
    }

    public function linkTicket(Request $request, Problem $problem)
    {
        abort_unless((int) $problem->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->isStaff(), 403);
        $request->validate(['ticket_id' => 'required|exists:tickets,id']);
        $ticket = Ticket::where('tenant_id', $this->tenantId())->findOrFail($request->input('ticket_id'));
        $problem->tickets()->syncWithoutDetaching([$ticket->id]);
        audit('problem.linked', $problem, ['ticket' => $ticket->number]);

        return back()->with('success', "Linked {$ticket->number}.");
    }

    protected function validateProblem(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000',
            'status' => 'nullable|in:open,investigating,known_error,resolved,closed',
            'root_cause' => 'nullable|string|max:10000', 'workaround' => 'nullable|string|max:10000',
            'known_error' => 'nullable|boolean', 'owner_id' => 'nullable|exists:users,id',
        ]);
    }
}
