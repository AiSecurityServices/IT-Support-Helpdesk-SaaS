@extends('layouts.app')
@section('title','Financial report')
@section('heading','Financial report')
@section('content')
<x-card>
<form method="GET" class="mb-3 flex gap-2 text-sm"><input type="date" name="from" value="{{ $from }}" class="pf-input"><input type="date" name="to" value="{{ $to }}" class="pf-input"><x-btn variant="secondary">Apply</x-btn><button name="export" value="csv" class="rounded-lg border px-4 py-2 text-sm">Export CSV</button></form>
<div class="grid gap-4 text-sm md:grid-cols-4">
<x-card class="!p-3">Invoiced: <strong>{{ money($invoiced) }}</strong></x-card>
<x-card class="!p-3">Collected: <strong>{{ money($collected) }}</strong></x-card>
<x-card class="!p-3">Outstanding: <strong>{{ money($outstanding) }}</strong></x-card>
<x-card class="!p-3">Billable time: <strong>{{ round($billable_minutes/60,1) }}h</strong></x-card>
</div>
<h3 class="mb-2 mt-4 font-semibold">Overdue invoices</h3>
@forelse($overdue as $i)<div class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span><a class="text-blue-600" href="{{ route('invoices.show',$i) }}">{{ $i->number }}</a> {{ $i->customer?->company_name }}</span><span>{{ money($i->balance(),$i->currency) }} · due {{ $i->due_date?->format('d M Y') }}</span></div>
@empty<div class="pf-empty"><x-icon name="check" class="pf-icon-lg" /><p class="text-sm">Nothing overdue.</p></div>@endforelse
</x-card>
@endsection

