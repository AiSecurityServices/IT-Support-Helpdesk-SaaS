@extends('layouts.app')
@section('title', 'Invoice '.$invoice->number)
@section('heading', 'Invoice '.$invoice->number)
@section('content')
<div class="mb-3 flex flex-wrap gap-2 text-sm">
  <x-badge :color="$invoice->status==='paid'?'green':($invoice->status==='overdue'?'red':'blue')">{{ ucfirst(str_replace('_',' ',$invoice->status)) }}</x-badge>
  <span class="ml-auto flex gap-2">
    <a href="{{ route('invoices.pdf',$invoice) }}" class="rounded-lg border px-3 py-1">Download PDF</a>
    @if(in_array($invoice->status,['issued','partially_paid','overdue']))
    <form method="POST" action="{{ route('invoices.email',$invoice) }}" class="inline">@csrf<button class="rounded-lg border px-3 py-1">Email to customer</button></form>
    @endif
    @if($invoice->status==='draft')
    <form method="POST" action="{{ route('invoices.issue',$invoice) }}" class="inline">@csrf<button class="rounded-lg bg-blue-600 px-3 py-1 text-white">Issue invoice</button></form>
    @endif
    @if(!in_array($invoice->status,['paid','void']))
    <form method="POST" action="{{ route('invoices.void',$invoice) }}" onsubmit="return confirm('Void this invoice?')" class="inline">@csrf<button class="rounded-lg border border-red-300 px-3 py-1 text-red-600">Void</button></form>
    @endif
  </span>
</div>
<div class="grid gap-4 lg:grid-cols-3">
<x-card class="lg:col-span-2" title="Line items">
<table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th class="p-2">Description</th><th class="p-2 text-right">Qty</th><th class="p-2 text-right">Unit</th><th class="p-2 text-right">Total</th></tr></thead>
<tbody>
@foreach($invoice->items as $it)<tr class="border-b dark:border-slate-700"><td class="p-2">{{ $it->description }}</td><td class="p-2 text-right">{{ $it->quantity }}</td><td class="p-2 text-right">{{ money($it->unit_price,$invoice->currency) }}</td><td class="p-2 text-right">{{ money($it->line_total,$invoice->currency) }}</td></tr>@endforeach
</tbody></table>
<div class="ml-auto mt-2 w-64 text-sm">
<div class="flex justify-between"><span>Subtotal</span><span>{{ money($invoice->subtotal,$invoice->currency) }}</span></div>
<div class="flex justify-between"><span>Discount ({{ $invoice->discount_percent }}%)</span><span>−{{ money($invoice->discount_total,$invoice->currency) }}</span></div>
<div class="flex justify-between"><span>Tax ({{ $invoice->tax_percent }}%)</span><span>{{ money($invoice->tax_total,$invoice->currency) }}</span></div>
<div class="flex justify-between border-t pt-1 font-bold dark:border-slate-700"><span>Total</span><span>{{ money($invoice->total,$invoice->currency) }}</span></div>
<div class="flex justify-between"><span>Paid</span><span>{{ money($invoice->amount_paid,$invoice->currency) }}</span></div>
<div class="flex justify-between font-bold"><span>Balance</span><span>{{ money($invoice->balance(),$invoice->currency) }}</span></div>
</div>
<p class="mt-2 text-xs text-slate-500">Customer: {{ $invoice->customer?->company_name }} · Issued {{ $invoice->issue_date?->format('d M Y') }} · Due {{ $invoice->due_date?->format('d M Y') }}</p>
</x-card>
<div class="space-y-4">
<x-card title="Record payment">
@if(in_array($invoice->status,['issued','partially_paid','overdue']))
<form method="POST" action="{{ route('invoices.payments',$invoice) }}" class="space-y-2 text-sm">@csrf
<x-field label="Amount"><input type="number" step="0.01" name="amount" max="{{ $invoice->balance() }}" required class="pf-input"></x-field>
<x-field label="Method"><select name="method" class="pf-select"><option value="manual">Manual</option><option value="bank">Bank transfer</option><option value="cash">Cash</option><option value="stripe">Stripe</option><option value="other">Other</option></select></x-field>
<x-field label="Reference"><input name="reference" placeholder="Unique per invoice" class="pf-input"></x-field>
<x-btn>Record payment</x-btn>
</form>
@else<p class="text-sm text-slate-500">Payments can be recorded once the invoice is issued.</p>@endif
</x-card>
<x-card title="Payments ({{ $invoice->payments->count() }})">
@foreach($invoice->payments as $p)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700">{{ money($p->amount,$p->currency) }} · {{ ucfirst($p->method) }} · {{ $p->paid_on?->format('d M Y') }}<div class="text-xs text-slate-500">{{ $p->reference }}</div></div>@endforeach
</x-card>
</div>
</div>
@endsection


