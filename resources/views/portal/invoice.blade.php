@extends('layouts.portal')
@section('title','Invoice '.$invoice->number)
@section('heading','Invoice '.$invoice->number)
@section('content')
<x-card>
<table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-2">Description</th><th class="p-2 text-right">Qty</th><th class="p-2 text-right">Unit</th><th class="p-2 text-right">Total</th></tr></thead>
<tbody>@foreach($invoice->items as $it)<tr class="border-b"><td class="p-2">{{ $it->description }}</td><td class="p-2 text-right">{{ $it->quantity }}</td><td class="p-2 text-right">{{ money($it->unit_price,$invoice->currency) }}</td><td class="p-2 text-right">{{ money($it->line_total,$invoice->currency) }}</td></tr>@endforeach</tbody></table>
<div class="ml-auto mt-2 w-64 text-sm">
<div class="flex justify-between"><span>Subtotal</span><span>{{ money($invoice->subtotal,$invoice->currency) }}</span></div>
<div class="flex justify-between"><span>Tax</span><span>{{ money($invoice->tax_total,$invoice->currency) }}</span></div>
<div class="flex justify-between font-bold"><span>Balance due</span><span>{{ money($invoice->balance(),$invoice->currency) }}</span></div>
</div>
<p class="mt-2 text-xs text-slate-500">Due {{ $invoice->due_date?->format('d M Y') }} · Status: {{ $invoice->status }}</p>
</x-card>
@endsection
