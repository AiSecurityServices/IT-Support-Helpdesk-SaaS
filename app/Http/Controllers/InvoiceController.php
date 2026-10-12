<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Notifications\InvoiceIssuedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);
        $query = Invoice::with('customer')->where('tenant_id', $this->tenantId())->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        return view('invoices.index', [
            'invoices' => $query->paginate(15)->withQueryString(),
            'customers' => Customer::where('tenant_id', $this->tenantId())->orderBy('company_name')->get(),
            'outstanding' => Invoice::where('tenant_id', $this->tenantId())->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('total'),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->canManage('invoices'), 403);

        // Optional: pre-fill from a ticket's uninvoiced billable time.
        $fromTicket = null;
        $suggestedItems = [];
        if ($request->filled('ticket_id')) {
            $fromTicket = Ticket::where('tenant_id', $this->tenantId())->findOrFail($request->input('ticket_id'));
            $entries = $fromTicket->timeEntries()->where('billable', true)->where('status', '!=', 'invoiced')->get();
            foreach ($entries as $entry) {
                $rate = $entry->hourly_rate ?? tenant_setting('invoice.default_hourly_rate', 75);
                $suggestedItems[] = [
                    'description' => 'Support work — '.$fromTicket->number.($entry->description ? ': '.$entry->description : ''),
                    'quantity' => round($entry->minutes / 60, 2),
                    'unit_price' => $rate,
                    'time_entry_id' => $entry->id, 'ticket_id' => $fromTicket->id,
                ];
            }
        }

        return view('invoices.create', [
            'customers' => Customer::where('tenant_id', $this->tenantId())->where('status', 'active')->orderBy('company_name')->get(),
            'fromTicket' => $fromTicket, 'suggestedItems' => $suggestedItems,
            'defaults' => [
                'currency' => tenant_setting('invoice.currency', 'GBP'),
                'tax_percent' => tenant_setting('invoice.tax_percent', 20),
                'payment_terms_days' => tenant_setting('invoice.payment_terms_days', 14),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManage('invoices'), 403);
        $data = $this->validateInvoice($request);

        return DB::transaction(function () use ($data) {
            $customer = Customer::where('tenant_id', $this->tenantId())->findOrFail($data['customer_id']);
            $invoice = Invoice::create([
                'tenant_id' => $this->tenantId(), 'customer_id' => $customer->id,
                'contact_id' => $data['contact_id'] ?? null,
                'currency' => $data['currency'] ?? tenant_setting('invoice.currency', 'GBP'),
                'discount_percent' => $data['discount_percent'] ?? 0,
                'tax_percent' => $data['tax_percent'] ?? tenant_setting('invoice.tax_percent', 20),
                'payment_terms_days' => $data['payment_terms_days'] ?? 14,
                'notes' => $data['notes'] ?? null,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays($data['payment_terms_days'] ?? 14)->toDateString(),
                'status' => 'draft',
            ]);
            foreach ($data['items'] as $item) {
                $invoice->items()->create([
                    'ticket_id' => $item['ticket_id'] ?? null,
                    'time_entry_id' => $item['time_entry_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'],
                ]);
            }
            $invoice->load('items');
            $invoice->recalculate();
            audit('invoice.created', $invoice, ['number' => $invoice->number]);

            return redirect()->route('invoices.show', $invoice)->with('success', "Draft invoice {$invoice->number} created.");
        });
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'items', 'payments']);

        return view('invoices.show', compact('invoice'));
    }

    public function issue(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->canManage('invoices'), 403);
        abort_unless($invoice->status === 'draft', 422, 'Only draft invoices can be issued.');
        abort_if($invoice->items()->count() === 0, 422, 'Add at least one line item before issuing.');

        return DB::transaction(function () use ($invoice) {
            $invoice->status = 'issued';
            $invoice->issue_date = $invoice->issue_date ?? now()->toDateString();
            $invoice->due_date = $invoice->due_date ?? now()->addDays($invoice->payment_terms_days)->toDateString();
            $invoice->save();
            $invoice->refreshStatus();

            // Mark linked time entries invoiced.
            TimeEntry::whereIn('id', $invoice->items()->whereNotNull('time_entry_id')->pluck('time_entry_id'))
                ->update(['status' => 'invoiced', 'invoice_id' => $invoice->id]);

            // Notify customer portal users.
            foreach ($invoice->customer->portalUsers as $portalUser) {
                $portalUser->notify(new InvoiceIssuedNotification($invoice));
            }
            audit('invoice.issued', $invoice, ['number' => $invoice->number]);

            return back()->with('success', "Invoice {$invoice->number} issued.");
        });
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->canManage('invoices'), 403);
        abort_unless(! in_array($invoice->status, ['draft', 'void', 'paid'], true), 422, 'Payments cannot be recorded in this state.');
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:1000000',
            'method' => 'required|in:manual,stripe,bank,cash,other',
            'reference' => 'nullable|string|max:255',
            'paid_on' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($request, $invoice, $data) {
            abort_if(round($data['amount'], 2) > $invoice->balance() + 0.001, 422, 'Payment exceeds the outstanding balance.');

            // Idempotency: same reference for this invoice is rejected.
            if (! empty($data['reference']) && Payment::where('tenant_id', $this->tenantId())
                ->where('invoice_id', $invoice->id)->where('reference', $data['reference'])->exists()) {
                abort(422, 'A payment with this reference already exists for this invoice.');
            }

            $payment = $invoice->payments()->create([
                'tenant_id' => $invoice->tenant_id, 'amount' => round($data['amount'], 2),
                'currency' => $invoice->currency, 'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_on' => $data['paid_on'] ?? now()->toDateString(),
                'recorded_by' => $request->user()->id, 'notes' => $data['notes'] ?? null,
            ]);
            $invoice->amount_paid = round((float) $invoice->amount_paid + (float) $payment->amount, 2);
            $invoice->save();
            $invoice->refreshStatus();
            audit('payment.recorded', $payment, ['invoice' => $invoice->number, 'amount' => $payment->amount]);

            return back()->with('success', 'Payment recorded. New balance: '.money($invoice->fresh()->balance(), $invoice->currency));
        });
    }

    public function void(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->canManage('invoices'), 403);
        abort_if((float) $invoice->amount_paid > 0, 422, 'Invoices with payments cannot be voided. Issue a credit adjustment instead.');
        $invoice->status = 'void';
        $invoice->save();
        audit('invoice.voided', $invoice);

        return back()->with('success', 'Invoice voided.');
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'items', 'payments']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download("{$invoice->number}.pdf");
    }

    public function email(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->tenant_id === $this->tenantId(), 404);
        abort_unless($request->user()->canManage('invoices'), 403);
        abort_unless(in_array($invoice->status, ['issued', 'partially_paid', 'overdue'], true), 422);
        foreach ($invoice->customer->portalUsers as $portalUser) {
            $portalUser->notify(new InvoiceIssuedNotification($invoice));
        }

        return back()->with('success', 'Invoice emailed to customer contacts.');
    }

    // ---- internals ----

    protected function validateInvoice(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'currency' => 'nullable|string|size:3',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'issue_date' => 'nullable|date', 'due_date' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:100',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01|max:100000',
            'items.*.unit_price' => 'required|numeric|min:0|max:1000000',
            'items.*.ticket_id' => 'nullable|exists:tickets,id',
            'items.*.time_entry_id' => 'nullable|exists:time_entries,id',
        ]);

        return $data;
    }
}
