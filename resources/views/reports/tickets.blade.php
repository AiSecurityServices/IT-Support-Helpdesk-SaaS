@extends('layouts.app')
@section('title','Ticket report')
@section('heading','Ticket report')
@section('content')
<x-card>
<form method="GET" class="mb-4 grid gap-2 text-sm md:grid-cols-6">
<input type="date" name="from" value="{{ $from }}" class="pf-input"><input type="date" name="to" value="{{ $to }}" class="pf-input">
<select name="customer_id" class="pf-select"><option value="">All customers</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected($customer_id==$c->id)>{{ $c->company_name }}</option>@endforeach</select>
<select name="technician_id" class="pf-select"><option value="">All technicians</option>@foreach($technicians as $t)<option value="{{ $t->id }}" @selected($technician_id==$t->id)>{{ $t->name }}</option>@endforeach</select>
<x-btn variant="secondary">Apply</x-btn>
<button name="export" value="csv" class="rounded-lg border px-4 py-2 text-sm">Export CSV</button>
</form>
<div class="grid gap-4 md:grid-cols-4 text-sm">
<x-card class="!p-3">Total: <strong>{{ $total }}</strong></x-card>
<x-card class="!p-3">Avg resolution: <strong>{{ $avg_resolution_min!==null ? round($avg_resolution_min/60,1).'h' : '—' }}</strong></x-card>
<x-card class="!p-3">Avg first response: <strong>{{ $avg_first_response_min!==null ? round($avg_first_response_min/60,1).'h' : '—' }}</strong></x-card>
</div>
<div class="mt-4 grid gap-4 md:grid-cols-2 text-sm">
<div><h3 class="font-semibold">By status</h3>@foreach($by_status as $k=>$v)<div class="flex justify-between border-b py-1 dark:border-slate-700"><span>{{ ucfirst(str_replace('_',' ',$k)) }}</span><span>{{ $v }}</span></div>@endforeach</div>
<div><h3 class="font-semibold">By priority</h3>@foreach($by_priority as $k=>$v)<div class="flex justify-between border-b py-1 dark:border-slate-700"><span>{{ ucfirst($k) }}</span><span>{{ $v }}</span></div>@endforeach</div>
<div><h3 class="font-semibold">By category</h3>@foreach($by_category as $k=>$v)<div class="flex justify-between border-b py-1 dark:border-slate-700"><span>{{ $k }}</span><span>{{ $v }}</span></div>@endforeach</div>
<div><h3 class="font-semibold">Top customers</h3>@foreach($by_customer as $k=>$v)<div class="flex justify-between border-b py-1 dark:border-slate-700"><span>{{ $k }}</span><span>{{ $v }}</span></div>@endforeach</div>
</div>
</x-card>
@endsection

