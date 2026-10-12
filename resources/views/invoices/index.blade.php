@extends('layouts.app')
@section('title','Invoices')
@section('heading','Service invoices')
@section('content')
<div class="mb-3 flex gap-2 text-sm">
  <x-card class="!p-3">Outstanding: <strong>{{ money($outstanding) }}</strong></x-card>
  <span class="ml-auto"><a href="{{ route('invoices.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-white">+ New invoice</a></span>
</div>
<x-card>
<form method="GET" class="mb-3 flex gap-2 text-sm">
<select name="status" class="pf-select"><option value="">All statuses</option>@foreach(config('supportflow.invoice_statuses') as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select>
<select name="customer_id" class="pf-select"><option value="">All customers</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->company_name }}</option>@endforeach</select>
<x-btn variant="secondary">Filter</x-btn>
</form>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th class="p-2">Number</th><th class="p-2">Customer</th><th class="p-2">Total</th><th class="p-2">Paid</th><th class="p-2">Due</th><th class="p-2">Status</th></tr></thead>
<tbody>
@forelse($invoices as $i)
<tr class="border-b dark:border-slate-700">
<td class="p-2"><a class="font-medium text-blue-600" href="{{ route('invoices.show',$i) }}">{{ $i->number }}</a></td>
<td class="p-2">{{ $i->customer?->company_name }}</td>
<td class="p-2">{{ money($i->total,$i->currency) }}</td>
<td class="p-2">{{ money($i->amount_paid,$i->currency) }}</td>
<td class="p-2">{{ $i->due_date?->format('d M Y') ?? '—' }}</td>
<td class="p-2"><x-badge :color="$i->status==='paid'?'green':($i->status==='overdue'?'red':($i->status==='draft'?'slate':'blue'))">{{ ucfirst(str_replace('_',' ',$i->status)) }}</x-badge></td>
</tr>
@empty<tr><td colspan="6" class="p-6 text-center text-slate-500">No invoices yet.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-2">{{ $invoices->links() }}</div>
</x-card>
@endsection

