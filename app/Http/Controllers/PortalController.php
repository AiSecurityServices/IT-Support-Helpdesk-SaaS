<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Services\AttachmentService;
use App\Services\TicketService;
use Illuminate\Http\Request;

/**
 * Customer portal — strictly scoped to the authenticated customer's
 * own organisation. Internal notes, costs and admin areas are unreachable.
 */
class PortalController extends Controller
{
    protected function ensureCustomer(Request $request): void
    {
        abort_unless($request->user()->isCustomer(), 403, 'Customer portal access only.');
        abort_unless($request->user()->customer_id, 403, 'No customer organisation linked to your account.');
    }

    public function dashboard(Request $request)
    {
        $this->ensureCustomer($request);
        $customerId = $request->user()->customer_id;

        $tickets = Ticket::where('tenant_id', $this->tenantId())->where('customer_id', $customerId);
        $stats = [
            'open' => (clone $tickets)->whereIn('status', Ticket::OPEN_STATUSES)->count(),
            'resolved' => (clone $tickets)->where('status', 'resolved')->count(),
            'closed' => (clone $tickets)->where('status', 'closed')->count(),
        ];

        return view('portal.dashboard', [
            'stats' => $stats,
            'tickets' => (clone $tickets)->latest()->limit(10)->get(),
            'assets' => Asset::where('tenant_id', $this->tenantId())->where('customer_id', $customerId)->limit(6)->get(),
            'invoices' => Invoice::where('tenant_id', $this->tenantId())->where('customer_id', $customerId)
                ->whereIn('status', ['issued', 'partially_paid', 'overdue'])->orderBy('due_date')->limit(5)->get(),
        ]);
    }

    public function tickets(Request $request)
    {
        $this->ensureCustomer($request);
        $query = Ticket::with('category')->where('tenant_id', $this->tenantId())
            ->where('customer_id', $request->user()->customer_id)->latest();
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('subject', 'like', $term)->orWhere('number', 'like', $term));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('portal.tickets', ['tickets' => $query->paginate(12)->withQueryString()]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->ensureCustomer($request);
        abort_unless((int) $ticket->tenant_id === $this->tenantId()
            && (int) $ticket->customer_id === (int) $request->user()->customer_id, 404);
        // Only public messages + customer-safe fields — internal notes never loaded.
        $ticket->load(['category', 'asset', 'publicMessages.author']);

        return view('portal.ticket', compact('ticket'));
    }

    public function create(Request $request)
    {
        $this->ensureCustomer($request);

        return view('portal.create', [
            'categories' => Category::where('tenant_id', $this->tenantId())->where('is_active', true)->orderBy('name')->get(),
            'assets' => Asset::where('tenant_id', $this->tenantId())->where('customer_id', $request->user()->customer_id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, TicketService $service)
    {
        $this->ensureCustomer($request);
        $data = $request->validate([
            'subject' => 'required|string|max:255', 'description' => 'required|string|max:10000',
            'category_id' => 'nullable|exists:categories,id', 'priority' => 'nullable|in:low,normal,high,urgent',
            'asset_id' => 'nullable|exists:assets,id',
        ]);
        if (! empty($data['asset_id'])) {
            abort_unless(Asset::where('tenant_id', $this->tenantId())
                ->where('id', $data['asset_id'])->where('customer_id', $request->user()->customer_id)->exists(), 422);
        }
        $ticket = $service->create([
            'subject' => $data['subject'], 'description' => $data['description'],
            'category_id' => $data['category_id'] ?? null, 'asset_id' => $data['asset_id'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'customer_id' => $request->user()->customer_id, 'source' => 'portal',
        ], $request->user());

        if ($request->hasFile('attachments')) {
            $svc = app(AttachmentService::class);
            foreach ((array) $request->file('attachments', []) as $file) {
                if ($file && $file->isValid()) {
                    $msg = $ticket->messages()->create(['tenant_id' => $ticket->tenant_id,
                        'author_id' => $request->user()->id, 'visibility' => 'public', 'body' => 'Attachment uploaded.']);
                    $svc->store($file, $msg, $ticket->tenant_id, $request->user()->id, 'public');
                    break;
                }
            }
        }

        return redirect()->route('portal.tickets.show', $ticket)->with('success', "Request {$ticket->number} submitted.");
    }

    public function reply(Request $request, Ticket $ticket, TicketService $service)
    {
        $this->ensureCustomer($request);
        abort_unless((int) $ticket->tenant_id === $this->tenantId()
            && (int) $ticket->customer_id === (int) $request->user()->customer_id, 404);
        $request->validate(['body' => 'required|string|max:10000']);
        $service->addMessage($ticket, $request->user(), $request->input('body'), 'public');

        return back()->with('success', 'Reply sent to support.');
    }

    public function assets(Request $request)
    {
        $this->ensureCustomer($request);

        return view('portal.assets', [
            'assets' => Asset::where('tenant_id', $this->tenantId())
                ->where('customer_id', $request->user()->customer_id)->paginate(12),
        ]);
    }

    public function invoices(Request $request)
    {
        $this->ensureCustomer($request);

        return view('portal.invoices', [
            'invoices' => Invoice::where('tenant_id', $this->tenantId())
                ->where('customer_id', $request->user()->customer_id)
                ->whereNotIn('status', ['draft', 'void'])->latest()->paginate(12),
        ]);
    }

    public function invoiceShow(Request $request, Invoice $invoice)
    {
        $this->ensureCustomer($request);
        abort_unless((int) $invoice->tenant_id === $this->tenantId()
            && (int) $invoice->customer_id === (int) $request->user()->customer_id, 404);
        abort_unless(! in_array($invoice->status, ['draft', 'void'], true), 404);
        $invoice->load(['items', 'payments']);

        return view('portal.invoice', compact('invoice'));
    }

    public function profile(Request $request)
    {
        $this->ensureCustomer($request);

        return view('portal.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $this->ensureCustomer($request);
        $data = $request->validate(['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:50']);
        $request->user()->fill($data)->save();

        return back()->with('success', 'Profile updated.');
    }
}
