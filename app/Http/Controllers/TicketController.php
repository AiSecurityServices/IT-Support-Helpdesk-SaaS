<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomFieldDefinition;
use App\Models\SavedReply;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\SlaCalculator;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    protected TicketService $tickets;

    public function __construct()
    {
        $this->tickets = new TicketService;
    }

    /** Ticket queues: mine, unassigned, team, urgent, overdue, awaiting_*, resolved, closed, all. */
    public function index(Request $request, string $queue = 'all')
    {
        $this->authorize('viewAny', Ticket::class);
        $user = $request->user();

        $query = Ticket::with(['customer', 'assignee', 'team', 'category'])
            ->where('tenant_id', $this->tenantId())->latest();

        $queues = [
            'mine' => fn ($q) => $q->where('assigned_to', $user->id)->whereIn('status', Ticket::OPEN_STATUSES),
            'unassigned' => fn ($q) => $q->whereNull('assigned_to')->whereIn('status', Ticket::OPEN_STATUSES),
            'team' => fn ($q) => $q->whereIn('team_id', $user->teams()->pluck('teams.id'))->whereIn('status', Ticket::OPEN_STATUSES),
            'urgent' => fn ($q) => $q->where('priority', 'urgent')->whereIn('status', Ticket::OPEN_STATUSES),
            'overdue' => fn ($q) => $q->whereNotNull('due_at')->where('due_at', '<', now())->whereIn('status', Ticket::OPEN_STATUSES),
            'awaiting_customer' => fn ($q) => $q->where('status', 'pending_customer'),
            'awaiting_internal' => fn ($q) => $q->where('status', 'pending_internal'),
            'resolved' => fn ($q) => $q->where('status', 'resolved'),
            'closed' => fn ($q) => $q->where('status', 'closed'),
            'all' => fn ($q) => $q,
        ];
        abort_unless(isset($queues[$queue]), 404);
        $queues[$queue]($query);

        // Technicians only see their teams' + assigned tickets in shared queues.
        if ($user->hasRole('technician') && in_array($queue, ['all', 'unassigned', 'urgent', 'overdue'], true)) {
            $teamIds = $user->teams()->pluck('teams.id');
            $query->where(function ($q) use ($user, $teamIds) {
                $q->where('assigned_to', $user->id)->orWhereIn('team_id', $teamIds);
            });
        }

        foreach (['status', 'priority', 'category_id', 'customer_id', 'assigned_to', 'team_id', 'source'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('subject', 'like', $term)->orWhere('description', 'like', $term)
                    ->orWhere('number', 'like', $term)->orWhere('tags', 'like', $term);
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        if ($request->input('sla') === 'breached') {
            $query->whereIn('id', $this->breachedTicketIds());
        }

        $tickets = $query->paginate(15)->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets, 'queue' => $queue,
            'customers' => Customer::where('tenant_id', $this->tenantId())->orderBy('company_name')->get(),
            'technicians' => User::where('tenant_id', $this->tenantId())->whereIn('role', User::STAFF_ROLES)->orderBy('name')->get(),
            'teams' => Team::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'categories' => Category::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Ticket::class);

        return view('tickets.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ticket::class);

        // Plan ticket-volume enforcement.
        $limit = currentTenant()?->activeSubscription?->plan?->ticket_limit_monthly;
        if ($limit) {
            $used = Ticket::where('tenant_id', $this->tenantId())
                ->where('created_at', '>=', now()->startOfMonth())->count();
            abort_if($used >= $limit, 422, 'Monthly ticket limit for your plan has been reached. Please upgrade.');
        }

        $data = $this->validateTicket($request);
        $ticket = $this->tickets->create($data, $request->user());

        if ($request->hasFile('attachments')) {
            $this->storeAttachments($request, $ticket, $ticket, 'public');
        }

        return redirect()->route('tickets.show', $ticket)->with('success', "Ticket {$ticket->number} created.");
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load(['customer', 'contact', 'requester', 'category', 'assignee', 'team', 'asset',
            'messages.author', 'history.actor', 'watchers', 'timeEntries.technician', 'slaPolicy']);

        $calculator = new SlaCalculator(currentTenant());
        $deadlines = $calculator->deadlinesFor($ticket);
        $remaining = [
            'first_response' => $ticket->first_response_at ? null
                : $calculator->remainingBusinessMinutes($deadlines['first_response_deadline'], $calculator->calendar($deadlines['policy'])),
            'resolution' => in_array($ticket->status, ['resolved', 'closed'], true) ? null
                : $calculator->remainingBusinessMinutes($deadlines['resolution_deadline'], $calculator->calendar($deadlines['policy'])),
        ];

        $savedReplies = SavedReply::where('tenant_id', $this->tenantId())->orderBy('title')->get();
        $transitions = config("supportflow.ticket_transitions.{$ticket->status}", []);

        return view('tickets.show', compact('ticket', 'deadlines', 'remaining', 'savedReplies', 'transitions'));
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        return view('tickets.edit', ['ticket' => $ticket] + $this->formData($ticket));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $data = $this->validateTicket($request, $ticket);
        $before = $ticket->only(['priority', 'category_id', 'customer_id', 'due_at']);
        $ticket->fill($data);
        $ticket->save();
        \App\Models\TicketHistory::create([
            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
            'actor_id' => $request->user()->id, 'action' => 'updated',
            'changes' => ['before' => $before, 'after' => $ticket->only(array_keys($before))],
        ]);
        audit('ticket.updated', $ticket);

        $ctx = ['tenant_id' => $ticket->tenant_id, 'subject_id' => $ticket->id, 'ticket_id' => $ticket->id,
            'ticket' => ['status' => $ticket->status, 'priority' => $ticket->priority,
                'customer_id' => $ticket->customer_id, 'team_id' => $ticket->team_id,
                'assigned_to' => $ticket->assigned_to],
            'fingerprint' => ['priority' => $ticket->priority, 'status' => $ticket->status,
                'updated' => $ticket->updated_at?->format('YmdHi')]];
        \App\Services\AutomationEngine::fire('ticket.updated', $ctx);
        if (($before['priority'] ?? null) !== $ticket->priority) {
            \App\Services\AutomationEngine::fire('priority.changed', $ctx + ['from_priority' => $before['priority'] ?? null]);
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket updated.');
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        $ticket->delete();
        audit('ticket.deleted', $ticket, ['number' => $ticket->number]);

        return redirect()->route('tickets.index')->with('success', 'Ticket archived.');
    }

    // ---- Workflow actions ----

    public function reply(Request $request, Ticket $ticket)
    {
        $this->authorize('reply', $ticket);
        $request->validate(['body' => 'required|string|max:20000']);
        $message = $this->tickets->addMessage($ticket, $request->user(), $request->input('body'), 'public');

        if ($request->hasFile('attachments')) {
            $this->storeAttachments($request, $ticket, $message, 'public');
        }

        return back()->with('success', 'Reply posted.');
    }

    public function note(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        abort_unless($request->user()->isStaff(), 403);
        $request->validate(['body' => 'required|string|max:20000']);
        $message = $this->tickets->addMessage($ticket, $request->user(), $request->input('body'), 'internal');

        if ($request->hasFile('attachments')) {
            $this->storeAttachments($request, $ticket, $message, 'internal');
        }

        return back()->with('success', 'Internal note saved (hidden from customer).');
    }

    public function transition(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $request->validate([
            'to' => 'required|string',
            'note' => 'nullable|string|max:2000',
            'satisfaction_rating' => 'nullable|integer|min:1|max:5',
        ]);
        $this->tickets->transition($ticket, $request->input('to'), $request->user(), $request->input('note'));

        return back()->with('success', 'Ticket status updated.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorize('manageAssignment', $ticket);
        $request->validate(['assigned_to' => 'nullable|exists:users,id', 'team_id' => 'nullable|exists:teams,id']);

        if ($request->assigned_to) {
            $assignee = User::find($request->assigned_to);
            abort_unless($assignee && (int) $assignee->tenant_id === $this->tenantId() && $assignee->isStaff(), 422);
        }

        $this->tickets->assign($ticket, $request->input('assigned_to'), $request->input('team_id'), $request->user());

        return back()->with('success', 'Assignment updated.');
    }

    public function watch(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->watchers()->syncWithoutDetaching([auth()->id()]);

        return back()->with('success', 'You are now watching this ticket.');
    }

    public function unwatch(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->watchers()->detach(auth()->id());

        return back()->with('success', 'Stopped watching this ticket.');
    }

    public function merge(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $request->validate(['secondary_id' => 'required|exists:tickets,id']);
        $secondary = Ticket::where('tenant_id', $this->tenantId())->findOrFail($request->input('secondary_id'));
        $this->tickets->merge($ticket, $secondary, $request->user());

        return redirect()->route('tickets.show', $ticket)->with('success', "Merged {$secondary->number} into {$ticket->number}.");
    }

    public function rate(Request $request, Ticket $ticket)
    {
        // Customer satisfaction rating (portal or staff on behalf).
        $this->authorize('view', $ticket);
        $request->validate(['satisfaction_rating' => 'required|integer|min:1|max:5', 'satisfaction_feedback' => 'nullable|string|max:2000']);
        $ticket->satisfaction_rating = $request->input('satisfaction_rating');
        $ticket->satisfaction_feedback = $request->input('satisfaction_feedback');
        $ticket->save();

        return back()->with('success', 'Thanks for your feedback.');
    }

    public function bulk(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);
        $request->validate([
            'ids' => 'required|array|max:100', 'ids.*' => 'integer',
            'action' => 'required|in:assign,status,close,watch',
            'assigned_to' => 'nullable|exists:users,id', 'status' => 'nullable|string',
        ]);

        $tickets = Ticket::where('tenant_id', $this->tenantId())->whereIn('id', $request->input('ids'))->get();
        $count = 0;
        foreach ($tickets as $ticket) {
            try {
                match ($request->input('action')) {
                    'assign' => $request->user()->hasRole('owner', 'admin', 'manager')
                        ? $this->tickets->assign($ticket, $request->input('assigned_to'), $ticket->team_id, $request->user()) : null,
                    'status' => $this->tickets->transition($ticket, $request->input('status'), $request->user()),
                    'close' => $this->tickets->transition($ticket, 'closed', $request->user()),
                    'watch' => $ticket->watchers()->syncWithoutDetaching([$request->user()->id]),
                };
                $count++;
            } catch (\Throwable) {
                // Skip tickets whose transition is invalid; continue with the rest.
            }
        }

        return back()->with('success', "Bulk action applied to {$count} ticket(s).");
    }

    // ---- internals ----

    protected function validateTicket(Request $request, ?Ticket $ticket = null): array
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:20000',
            'customer_id' => 'nullable|exists:customers,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory' => 'nullable|string|max:255',
            'priority' => 'required|in:low,normal,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
            'asset_id' => 'nullable|exists:assets,id',
            'source' => 'nullable|in:portal,staff,email,api',
            'tags' => 'nullable|string|max:500',
            'due_at' => 'nullable|date',
            'custom_fields' => 'nullable|array',
        ]);

        // Cross-tenant reference protection: every linked record must belong to this tenant.
        foreach (['customer_id' => Customer::class, 'category_id' => Category::class, 'team_id' => Team::class, 'asset_id' => Asset::class] as $field => $model) {
            if (! empty($data[$field])) {
                abort_unless($model::where('tenant_id', $this->tenantId())->where('id', $data[$field])->exists(), 422, 'Invalid reference.');
            }
        }
        if (! empty($data['assigned_to'])) {
            abort_unless(User::where('tenant_id', $this->tenantId())->where('id', $data['assigned_to'])->whereIn('role', User::STAFF_ROLES)->exists(), 422, 'Invalid assignee.');
        }

        // Custom-field validation against tenant definitions.
        if (! empty($data['custom_fields'])) {
            $defs = CustomFieldDefinition::where('tenant_id', $this->tenantId())->where('is_active', true)->get()->keyBy('key');
            foreach ($data['custom_fields'] as $key => $value) {
                abort_unless($defs->has($key), 422, "Unknown custom field: {$key}.");
            }
            foreach ($defs as $key => $def) {
                if ($def->is_required && empty($data['custom_fields'][$key])) {
                    abort(response()->json(['message' => "Custom field {$def->label} is required."], 422));
                }
            }
        }

        return $data;
    }

    protected function formData(?Ticket $ticket = null): array
    {
        $tenantId = $this->tenantId();

        return [
            'customers' => Customer::where('tenant_id', $tenantId)->where('status', 'active')->orderBy('company_name')->get(),
            'categories' => Category::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('sort_order')->get(),
            'teams' => Team::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            'technicians' => User::where('tenant_id', $tenantId)->whereIn('role', User::STAFF_ROLES)->where('is_active', true)->orderBy('name')->get(),
            'assets' => $ticket?->customer_id
                ? Asset::where('tenant_id', $tenantId)->where('customer_id', $ticket->customer_id)->orderBy('name')->get()
                : Asset::where('tenant_id', $tenantId)->orderBy('name')->limit(200)->get(),
            'customFields' => CustomFieldDefinition::where('tenant_id', $tenantId)->where('is_active', true)->get(),
        ];
    }

    protected function storeAttachments(Request $request, Ticket $ticket, $attachable, string $visibility): void
    {
        $service = app(AttachmentService::class);
        foreach ((array) $request->file('attachments', []) as $file) {
            if ($file && $file->isValid()) {
                $service->store($file, $attachable, $ticket->tenant_id, $request->user()->id, $visibility);
            }
        }
    }

    protected function breachedTicketIds(): array
    {
        return \App\Models\SlaEvent::where('tenant_id', $this->tenantId())
            ->where('type', 'breached')->distinct()->pluck('ticket_id')->all();
    }
}
