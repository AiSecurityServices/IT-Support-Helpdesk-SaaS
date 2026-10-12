@extends('layouts.app')
@section('title','Workload report')
@section('heading','Technician workload')
@section('content')
<x-card>
<form method="GET" class="mb-3 flex gap-2 text-sm"><input type="date" name="from" value="{{ $from }}" class="pf-input"><input type="date" name="to" value="{{ $to }}" class="pf-input"><x-btn variant="secondary">Apply</x-btn><button name="export" value="csv" class="rounded-lg border px-4 py-2 text-sm">Export CSV</button></form>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th class="p-2">Technician</th><th class="p-2">Open</th><th class="p-2">Hours logged</th><th class="p-2">Billable</th><th class="p-2">Resolved</th><th class="p-2">Avg rating</th></tr></thead>
<tbody>@foreach($rows as $r)<tr class="border-b dark:border-slate-700"><td class="p-2 font-medium">{{ $r['name'] }}</td><td class="p-2">{{ $r['open'] }}</td><td class="p-2">{{ round($r['minutes']/60,1) }}h</td><td class="p-2">{{ round($r['billable']/60,1) }}h</td><td class="p-2">{{ $r['resolved'] }}</td><td class="p-2">{{ $r['avg_rating'] ? round($r['avg_rating'],1).'/5' : '—' }}</td></tr>@endforeach</tbody>
</table></div>
</x-card>
@endsection

