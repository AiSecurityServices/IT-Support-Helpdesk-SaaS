<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SlaEvent;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected function range(Request $request): array
    {
        $request->validate([
            'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from',
            'customer_id' => 'nullable|exists:customers,id', 'technician_id' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        return [
            now()->parse($request->input('from', now()->subDays(30)->toDateString()))->startOfDay(),
            now()->parse($request->input('to', now()->toDateString()))->endOfDay(),
            $request->input('customer_id'), $request->input('technician_id'), $request->input('team_id'),
        ];
    }

    protected function scopedTickets(Request $request, $from, $to, $customerId, $technicianId, $teamId)
    {
        // Table-qualified: these queries join customers/categories which share column names.
        $q = Ticket::where('tickets.tenant_id', $this->tenantId())->whereBetween('tickets.created_at', [$from, $to]);
        if ($customerId) {
            $q->where('tickets.customer_id', $customerId);
        }
        if ($technicianId) {
            $q->where('tickets.assigned_to', $technicianId);
        }
        if ($teamId) {
            $q->where('tickets.team_id', $teamId);
        }
        if ($request->user()->hasRole('technician')) {
            $teamIds = $request->user()->teams()->pluck('teams.id');
            $q->where(fn ($w) => $w->where('tickets.assigned_to', $request->user()->id)->orWhereIn('tickets.team_id', $teamIds));
        }

        return $q;
    }

    public function index()
    {
        abort_unless(request()->user()->canManage('reports'), 403);

        return view('reports.index');
    }

    public function tickets(Request $request)
    {
        abort_unless($request->user()->canManage('reports'), 403);
        [$from, $to, $customerId, $technicianId, $teamId] = $this->range($request);
        $base = $this->scopedTickets($request, $from, $to, $customerId, $technicianId, $teamId);

        $data = [
            'total' => (clone $base)->count(),
            'by_status' => (clone $base)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
            'by_priority' => (clone $base)->selectRaw('priority, COUNT(*) c')->groupBy('priority')->pluck('c', 'priority'),
            'by_category' => (clone $base)->leftJoin('categories', 'tickets.category_id', '=', 'categories.id')
                ->selectRaw('COALESCE(categories.name, "Uncategorised") n, COUNT(*) c')->groupBy('n')->pluck('c', 'n'),
            'by_customer' => (clone $base)->leftJoin('customers', 'tickets.customer_id', '=', 'customers.id')
                ->selectRaw('COALESCE(customers.company_name, "—") n, COUNT(*) c')->groupBy('n')->orderByDesc('c')->limit(10)->pluck('c', 'n'),
            'by_day' => (clone $base)->selectRaw('DATE(tickets.created_at) d, COUNT(*) c')->groupBy('d')->orderBy('d')->get(),
            // True averages of per-ticket durations, computed portably in PHP
            // (works on MySQL, MariaDB and SQLite; date-bounded cursor only).
            'avg_resolution_min' => $this->averageMinutes((clone $base)->whereNotNull('tickets.resolved_at'), 'resolved_at'),
            'avg_first_response_min' => $this->averageMinutes((clone $base)->whereNotNull('tickets.first_response_at'), 'first_response_at'),
        ];

        if ($request->input('export') === 'csv') {
            $rows = (clone $base)->with(['customer', 'assignee'])->orderBy('created_at')->cursor()
                ->map(fn ($t) => [$t->number, $t->subject, $t->customer?->company_name, $t->assignee?->name,
                    $t->status, $t->priority, $t->created_at->format('Y-m-d H:i'), $t->resolved_at?->format('Y-m-d H:i')]);

            return $this->csvDownload('ticket-report.csv',
                ['Number', 'Subject', 'Customer', 'Assignee', 'Status', 'Priority', 'Created', 'Resolved'], $rows);
        }

        return view('reports.tickets', array_merge($data, $this->filterLists(), [
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'customer_id' => $customerId, 'technician_id' => $technicianId, 'team_id' => $teamId,
        ]));
    }

    public function sla(Request $request)
    {
        abort_unless($request->user()->canManage('reports'), 403);
        [$from, $to, $customerId, $technicianId, $teamId] = $this->range($request);
        $ticketIds = $this->scopedTickets($request, $from, $to, $customerId, $technicianId, $teamId)->pluck('id');

        $events = SlaEvent::where('tenant_id', $this->tenantId())->whereIn('ticket_id', $ticketIds);
        $data = [
            'breaches' => (clone $events)->where('type', 'breached')->count(),
            'warnings' => (clone $events)->where('type', 'warned')->count(),
            'met' => (clone $events)->where('type', 'met')->count(),
            'breach_list' => SlaEvent::with('ticket')->where('tenant_id', $this->tenantId())
                ->whereIn('ticket_id', $ticketIds)->where('type', 'breached')->latest()->limit(50)->get(),
        ];
        $total = $data['met'] + $data['breaches'];
        $data['compliance_pct'] = $total ? round($data['met'] / $total * 100, 1) : null;

        if ($request->input('export') === 'csv') {
            $rows = $data['breach_list']->map(fn ($e) => [
                $e->ticket?->number, $e->target, $e->deadline_at?->format('Y-m-d H:i'), $e->created_at->format('Y-m-d H:i'),
            ]);

            return $this->csvDownload('sla-breaches.csv', ['Ticket', 'Target', 'Deadline', 'Recorded'], $rows);
        }

        return view('reports.sla', array_merge($data, $this->filterLists(), [
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'customer_id' => $customerId, 'technician_id' => $technicianId, 'team_id' => $teamId,
        ]));
    }

    public function workload(Request $request)
    {
        abort_unless($request->user()->canManage('reports'), 403);
        [$from, $to] = $this->range($request);

        $rows = User::where('tenant_id', $this->tenantId())->whereIn('role', User::STAFF_ROLES)
            ->withCount(['assignedTickets as open_tickets' => fn ($q) => $q->whereIn('status', Ticket::OPEN_STATUSES)])
            ->get()->map(function ($u) use ($from, $to) {
                $entries = TimeEntry::where('tenant_id', $this->tenantId())->where('technician_id', $u->id)
                    ->whereBetween('created_at', [$from, $to]);
                $resolved = Ticket::where('tenant_id', $this->tenantId())->where('assigned_to', $u->id)
                    ->whereBetween('resolved_at', [$from, $to])->count();

                return [
                    'name' => $u->name, 'open' => $u->open_tickets,
                    'minutes' => (clone $entries)->sum('minutes'),
                    'billable' => (clone $entries)->where('billable', true)->sum('minutes'),
                    'resolved' => $resolved,
                    'avg_rating' => Ticket::where('tenant_id', $this->tenantId())->where('assigned_to', $u->id)
                        ->whereBetween('created_at', [$from, $to])->whereNotNull('satisfaction_rating')->avg('satisfaction_rating'),
                ];
            });

        if ($request->input('export') === 'csv') {
            return $this->csvDownload('workload.csv',
                ['Technician', 'Open tickets', 'Minutes logged', 'Billable minutes', 'Resolved', 'Avg rating'],
                $rows->map(fn ($r) => array_values($r)));
        }

        return view('reports.workload', ['rows' => $rows, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    public function financial(Request $request)
    {
        abort_unless($request->user()->canManage('invoices'), 403);
        [$from, $to] = $this->range($request);
        $invoices = Invoice::where('tenant_id', $this->tenantId())->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]);

        $data = [
            'invoiced' => (clone $invoices)->whereNotIn('status', ['draft', 'void'])->sum('total'),
            'collected' => (clone $invoices)->sum('amount_paid'),
            'outstanding' => Invoice::where('tenant_id', $this->tenantId())->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('total'),
            'overdue' => Invoice::with('customer')->where('tenant_id', $this->tenantId())->where('status', 'overdue')->orderBy('due_date')->get(),
            'billable_minutes' => TimeEntry::where('tenant_id', $this->tenantId())->where('billable', true)
                ->whereBetween('created_at', [$from, $to])->sum('minutes'),
        ];

        if ($request->input('export') === 'csv') {
            $rows = (clone $invoices)->with('customer')->orderBy('issue_date')->cursor()
                ->map(fn ($i) => [$i->number, $i->customer?->company_name, $i->status, $i->total, $i->amount_paid, $i->issue_date?->format('Y-m-d'), $i->due_date?->format('Y-m-d')]);

            return $this->csvDownload('invoice-report.csv',
                ['Number', 'Customer', 'Status', 'Total', 'Paid', 'Issued', 'Due'], $rows);
        }

        return view('reports.financial', array_merge($data, ['from' => $from->toDateString(), 'to' => $to->toDateString()]));
    }

    public function assets(Request $request)
    {
        abort_unless($request->user()->canManage('reports'), 403);
        $data = [
            'by_status' => Asset::where('tenant_id', $this->tenantId())->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
            'by_type' => Asset::where('tenant_id', $this->tenantId())->selectRaw('type, COUNT(*) c')->groupBy('type')->pluck('c', 'type'),
            'expiring' => Asset::with('customer')->where('tenant_id', $this->tenantId())
                ->whereNotNull('warranty_expires_at')->whereDate('warranty_expires_at', '<=', now()->addDays(90))
                ->orderBy('warranty_expires_at')->get(),
        ];

        return view('reports.assets', $data);
    }

    public function activity(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);

        return view('reports.activity', [
            'logs' => AuditLog::where('tenant_id', $this->tenantId())->latest()->paginate(30),
        ]);
    }

    protected function filterLists(): array
    {
        return [
            'customers' => Customer::where('tenant_id', $this->tenantId())->orderBy('company_name')->get(),
            'technicians' => User::where('tenant_id', $this->tenantId())->whereIn('role', User::STAFF_ROLES)->orderBy('name')->get(),
            'teams' => \App\Models\Team::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ];
    }

    /** True average of per-ticket minute durations (portable across DB drivers). */
    protected function averageMinutes($query, string $endColumn): ?float
    {
        $total = 0;
        $count = 0;
        foreach ($query->select(['tickets.created_at', "tickets.{$endColumn}"])->cursor() as $row) {
            $total += $row->created_at->diffInMinutes($row->{$endColumn});
            $count++;
        }

        return $count ? $total / $count : null;
    }
}
