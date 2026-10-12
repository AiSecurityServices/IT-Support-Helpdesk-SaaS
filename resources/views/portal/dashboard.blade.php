@extends('layouts.portal')
@section('title','Support portal')
@section('heading')Hi, {{ auth()->user()->name }}@endsection
@section('actions')
<a href="{{ route('portal.tickets.create') }}" class="pf-btn pf-btn-primary pf-btn-sm"><x-icon name="plus" /> New support request</a>
@endsection
@section('content')
<div class="grid grid-cols-3 gap-x-6 gap-y-5">
  <div class="pf-stat is-accent"><div class="pf-stat-num">{{ $stats['open'] }}</div><div class="pf-stat-label">Open requests</div></div>
  <div class="pf-stat"><div class="pf-stat-num">{{ $stats['resolved'] }}</div><div class="pf-stat-label">Resolved</div></div>
  <div class="pf-stat"><div class="pf-stat-num">{{ $stats['closed'] }}</div><div class="pf-stat-label">Closed</div></div>
</div>
<div class="mt-7 grid gap-6 lg:grid-cols-2">
<x-card title="Recent requests">
@forelse($tickets as $t)<a href="{{ route('portal.tickets.show',$t) }}" class="flex items-center justify-between gap-2 border-b border-slate-200 py-2.5 text-sm last:border-0 dark:border-slate-700"><span><span class="pf-mono font-semibold">{{ $t->number }}</span> {{ \Str::limit($t->subject,50) }}</span><x-badge>{{ ucfirst(str_replace('_',' ',$t->status)) }}</x-badge></a>
@empty<div class="pf-empty"><x-icon name="ticket" class="pf-icon-lg" /><p class="text-sm">No requests yet — tell us what you need.</p></div>@endforelse
</x-card>
<div class="space-y-6">
<x-card title="My devices">
@foreach($assets as $a)<div class="border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-700">{{ $a->name }} <span class="text-slate-500">· {{ $a->status }}</span></div>@endforeach
<a class="mt-2 inline-block text-sm font-semibold text-cyan-700 hover:underline dark:text-cyan-300" href="{{ route('portal.assets') }}">View all →</a>
</x-card>
<x-card title="Invoices due">
@forelse($invoices as $i)<div class="flex justify-between border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-700"><a class="font-semibold text-cyan-700 hover:underline dark:text-cyan-300" href="{{ route('portal.invoices.show',$i) }}">{{ $i->number }}</a><span>{{ money($i->balance(),$i->currency) }} due {{ $i->due_date?->format('d M') }}</span></div>
@empty<p class="text-sm text-slate-500">Nothing due. All clear.</p>@endforelse
</x-card>
</div>
</div>
@endsection
