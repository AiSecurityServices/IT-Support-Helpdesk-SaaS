@extends('layouts.app')
@section('title','SLA report')
@section('heading','SLA compliance')
@section('content')
<x-card>
<form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
<input type="date" name="from" value="{{ $from }}" class="pf-input"><input type="date" name="to" value="{{ $to }}" class="pf-input">
<x-btn variant="secondary">Apply</x-btn>
<button name="export" value="csv" class="rounded-lg border px-4 py-2 text-sm">Export CSV</button>
</form>
<div class="grid gap-4 text-sm md:grid-cols-4">
<x-card class="!p-3">Met: <strong>{{ $met }}</strong></x-card>
<x-card class="!p-3">Warnings: <strong>{{ $warnings }}</strong></x-card>
<x-card class="!p-3">Breaches: <strong class="text-red-600">{{ $breaches }}</strong></x-card>
<x-card class="!p-3">Compliance: <strong>{{ $compliance_pct!==null ? $compliance_pct.'%' : '—' }}</strong></x-card>
</div>
<h3 class="mb-2 mt-4 font-semibold">Recent breaches</h3>
@forelse($breach_list as $e)<div class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span>@if($e->ticket)<a class="text-blue-600" href="{{ route('tickets.show',$e->ticket) }}">{{ $e->ticket->number }}</a>@endif {{ str_replace('_',' ',$e->target) }}</span><span class="text-slate-500">{{ $e->created_at->format('d M Y H:i') }}</span></div>
@empty<div class="pf-empty"><x-icon name="check" class="pf-icon-lg" /><p class="text-sm">No breaches in range.</p></div>@endforelse
</x-card>
@endsection

