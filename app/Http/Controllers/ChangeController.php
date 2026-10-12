<?php

namespace App\Http\Controllers;

use App\Models\Change;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Change management with audited approvals. Separation of duties enforced:
 * the requester can never approve their own change when the tenant requires it.
 */
class ChangeController extends Controller
{
    protected function tenantId(): int
    {
        return (int) currentTenantId();
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);

        return view('changes.index', [
            'changes' => Change::with('requester')->where('tenant_id', $this->tenantId())
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
                ->latest()->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager', 'technician'), 403);
        $data = $this->validateChange($request);
        $change = Change::create($data + [
            'tenant_id' => $this->tenantId(), 'requester_id' => $request->user()->id, 'status' => 'draft',
        ]);
        audit('change.created', $change);

        return redirect()->route('changes.show', $change)->with('success', 'Change request drafted.');
    }

    public function show(Change $change)
    {
        abort_unless((int) $change->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->isStaff(), 403);
        $change->load(['tickets.customer', 'requester']);

        return view('changes.show', compact('change'));
    }

    public function update(Request $request, Change $change)
    {
        abort_unless((int) $change->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->isStaff(), 403);
        abort_if(in_array($change->status, ['approved', 'closed'], true), 422, 'Approved/closed changes are locked.');
        $change->fill($this->validateChange($request))->save();
        audit('change.updated', $change);

        return back()->with('success', 'Change updated.');
    }

    public function submit(Request $request, Change $change)
    {
        abort_unless((int) $change->tenant_id === $this->tenantId(), 404);
        abort_unless($change->status === 'draft', 422);
        abort_unless($change->implementation_plan && $change->rollback_plan, 422, 'Implementation and rollback plans are required before submission.');
        $change->status = 'pending_approval';
        $change->save();
        $this->notifyApprovers($change);
        audit('change.submitted', $change);

        return back()->with('success', 'Submitted for approval.');
    }

    public function decide(Request $request, Change $change)
    {
        abort_unless((int) $change->tenant_id === $this->tenantId(), 404);
        abort_unless($change->status === 'pending_approval', 422);
        abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403, 'Change approval requires manager or above.');
        $request->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:2000']);

        $sod = (bool) currentTenant()?->setting('approvals.separation_of_duties', true);
        if ($sod && (int) $change->requester_id === (int) $request->user()->id) {
            abort(422, 'Separation of duties: authors cannot approve their own changes.');
        }

        return DB::transaction(function () use ($request, $change) {
            $approvals = $change->approvals ?? [];
            $approvals[] = ['by' => $request->user()->id, 'name' => $request->user()->name,
                'decision' => $request->input('decision'), 'at' => now()->toIso8601String(),
                'note' => $request->input('note')];
            $change->approvals = $approvals;
            $change->status = $request->input('decision') === 'approved' ? 'approved' : 'rejected';
            $change->save();
            audit('change.decided', $change, ['decision' => $request->input('decision')]);

            return back()->with('success', 'Decision recorded with full audit history.');
        });
    }

    public function implement(Request $request, Change $change)
    {
        abort_unless((int) $change->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->isStaff(), 403);
        $request->validate(['outcome' => 'required|string|max:5000', 'result' => 'required|in:implemented,failed']);
        abort_unless(in_array($change->status, ['approved', 'scheduled'], true), 422);
        $change->outcome = $request->input('outcome');
        $change->status = $request->input('result');
        $change->save();
        audit('change.implemented', $change, ['result' => $request->input('result')]);

        return back()->with('success', 'Outcome recorded.');
    }

    public function linkTicket(Request $request, Change $change)
    {
        abort_unless((int) $change->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->isStaff(), 403);
        $request->validate(['ticket_id' => 'required|exists:tickets,id']);
        $ticket = Ticket::where('tenant_id', $this->tenantId())->findOrFail($request->input('ticket_id'));
        $change->tickets()->syncWithoutDetaching([$ticket->id]);

        return back()->with('success', "Linked {$ticket->number}.");
    }

    protected function validateChange(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255', 'type' => 'nullable|in:standard,normal,emergency',
            'impact' => 'nullable|string|max:5000', 'risk' => 'nullable|in:low,medium,high',
            'implementation_plan' => 'nullable|string|max:10000',
            'test_plan' => 'nullable|string|max:10000',
            'rollback_plan' => 'nullable|string|max:10000',
            'window_start' => 'nullable|date', 'window_end' => 'nullable|date|after_or_equal:window_start',
        ]);
    }

    protected function notifyApprovers(Change $change): void
    {
        $approvers = User::where('tenant_id', $this->tenantId())
            ->whereIn('role', ['owner', 'admin', 'manager'])->where('is_active', true)->get();
        foreach ($approvers as $approver) {
            if ((int) $approver->id === (int) $change->requester_id) {
                continue;
            }
            $approver->notify(new \App\Notifications\ChangeApprovalNotification($change));
        }
    }
}
