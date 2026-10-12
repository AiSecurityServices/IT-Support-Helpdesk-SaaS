@extends('layouts.app')
@section('title','Reports')
@section('eyebrow','Analytics')
@section('heading','Reports')
@section('content')
<div class="grid gap-5 md:grid-cols-3">
@foreach([
['reports.tickets','ticket','Ticket volume','Volume, status, priority, category, customer, resolution and response times.'],
['reports.sla','clock','SLA compliance','Breaches, warnings, compliance rate and breach list.'],
['reports.workload','wrench','Technician workload','Open load, logged and billable time, resolutions and ratings.'],
['reports.financial','card','Financial','Invoiced, collected, outstanding, overdue and billable time.'],
['reports.assets','device','Assets','Lifecycle states, types and warranty expirations.'],
['reports.activity','file','Audit activity','Sensitive administrative and operational actions.'],
] as $r)
<a href="{{ route($r[0]) }}" class="pf-card pf-card-flat pf-card-pad group block transition hover:-translate-y-0.5"><span class="text-cyan-700 dark:text-cyan-300"><x-icon name="{{ $r[1] }}" class="pf-icon-lg" /></span><h2 class="pf-display mt-3 font-semibold">{{ $r[2] }} <span class="text-cyan-700 opacity-0 transition group-hover:opacity-100 dark:text-cyan-300">→</span></h2><p class="mt-1 text-sm text-slate-500">{{ $r[3] }}</p></a>
@endforeach
</div>
@endsection
