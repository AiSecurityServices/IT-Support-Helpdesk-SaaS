@extends('layouts.app')
@section('title','Dashboard')
@section('eyebrow','Operations overview')
@section('heading')Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}@endsection
@section('actions')
<a href="{{ route('tickets.create') }}" class="pf-btn pf-btn-primary pf-btn-sm"><x-icon name="plus" /> New ticket</a>
@endsection
@section('content')
@if($subscription && $subscription->onTrial())
<div class="pf-alert pf-alert-ok mb-4" role="status">Free trial ends {{ $subscription->trial_ends_at->format('d M Y') }} — <a class="font-semibold underline" href="{{ route('billing.index') }}">choose a plan</a> to keep going.</div>
@endif
@if(!empty($onboarding) && !$onboarding['complete'])
<x-card title="Commissioning checklist ({{ $onboarding['done'] }} of {{ $onboarding['total'] }})" class="mb-5">
<ul class="grid gap-1.5 text-sm sm:grid-cols-2">
@foreach($onboarding['steps'] as $step)
<li class="flex items-center gap-2">@if($step['done'])<span class="text-emerald-600 dark:text-emerald-400"><x-icon name="check" /></span><span>{{ $step['label'] }}</span>@else<span class="text-slate-400"><x-icon name="clock" /></span><a class="font-medium text-cyan-700 hover:underline dark:text-cyan-300" href="{{ $step['url'] }}">{{ $step['label'] }} →</a>@endif</li>
@endforeach
</ul>
</x-card>
@endif

{{-- Key indicators: hierarchy first, no card soup --}}
<div class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
  <a href="{{ route('tickets.index') }}" class="pf-stat is-accent"><div class="pf-stat-num">{{ $stats['open'] }}</div><div class="pf-stat-label">Open tickets</div></a>
  <a href="{{ route('tickets.index',['queue'=>'unassigned']) }}" class="pf-stat"><div class="pf-stat-num">{{ $stats['unassigned'] }}</div><div class="pf-stat-label">Unassigned</div></a>
  <a href="{{ route('tickets.index',['queue'=>'urgent']) }}" class="pf-stat {{ $stats['urgent']>0?'is-alert':'' }}"><div class="pf-stat-num">{{ $stats['urgent'] }}</div><div class="pf-stat-label">Urgent</div></a>
  <a href="{{ route('tickets.index',['queue'=>'overdue']) }}" class="pf-stat {{ $stats['overdue']>0?'is-alert':'' }}"><div class="pf-stat-num">{{ $stats['overdue'] }}</div><div class="pf-stat-label">Overdue</div></a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
  <x-card title="Ticket volume · last 14 days" class="lg:col-span-2">
    @if($volume->isEmpty())
      <div class="pf-empty"><x-icon name="chart" class="pf-icon-lg" /><p class="text-sm">No tickets yet in this period.</p></div>
    @else
    <div class="flex h-36 items-end gap-1.5" role="img" aria-label="Bar chart of new tickets per day for the last 14 days. Highest day: {{ $volume->max('c') }} tickets.">
      @php $max = $volume->max('c') ?: 1; @endphp
      @foreach($volume as $v)<div class="flex-1 rounded-t bg-gradient-to-t from-cyan-700 to-cyan-400" style="height:{{ round($v->c/$max*100) }}%" title="{{ $v->d }}: {{ $v->c }} tickets"></div>@endforeach
    </div>
    <div class="mt-1.5 flex justify-between font-mono text-[0.68rem] text-slate-500"><span>{{ $volume->first()->d }}</span><span>{{ $volume->last()->d }}</span></div>
    @endif
    <div class="mt-4 flex flex-wrap gap-1.5">
      @foreach($byStatus as $st=>$c)<x-badge>{{ ucfirst(str_replace('_',' ',$st)) }}: {{ $c }}</x-badge>@endforeach
      @foreach($byPriority as $p=>$c)<x-badge color="{{ $p==='urgent'?'red':($p==='high'?'yellow':'blue') }}">{{ ucfirst($p) }}: {{ $c }}</x-badge>@endforeach
    </div>
  </x-card>
  <x-card title="Assigned to me ({{ $myTickets->count() }})">
    @forelse($myTickets as $t)
    <a href="{{ route('tickets.show',$t) }}" class="block border-b border-slate-200 py-2.5 last:border-0 dark:border-slate-700">
      <div class="text-sm font-semibold">{{ $t->number }} — {{ \Str::limit($t->subject,50) }}</div>
      <div class="mt-0.5 text-xs text-slate-500">{{ $t->customer?->company_name }} · due {{ $t->due_at?->format('d M') ?? '—' }}</div>
    </a>
    @empty<div class="pf-empty"><x-icon name="check" class="pf-icon-lg" /><p class="text-sm">Nothing assigned. Enjoy the calm.</p></div>@endforelse
  </x-card>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
  <x-card title="Latest activity" class="lg:col-span-2">
    @forelse($recent as $t)
    <a href="{{ route('tickets.show',$t) }}" class="flex items-center justify-between gap-3 border-b border-slate-200 py-2.5 text-sm last:border-0 dark:border-slate-700">
      <span class="min-w-0 truncate"><span class="pf-mono font-medium">{{ $t->number }}</span> {{ \Str::limit($t->subject,60) }} <span class="text-slate-500">· {{ $t->customer?->company_name }}</span></span>
      <span class="flex shrink-0 gap-1">@include('tickets.partials.badges')</span>
    </a>
    @empty<div class="pf-empty"><x-icon name="ticket" class="pf-icon-lg" /><p class="text-sm">No tickets yet. <a class="font-semibold text-cyan-700 dark:text-cyan-300" href="{{ route('tickets.create') }}">Create the first one</a>.</p></div>@endforelse
  </x-card>
  <div class="space-y-6">
    <x-card title="Warranty watch">
      @forelse($warrantyAlerts as $a)<div class="border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-700"><a class="font-medium text-cyan-700 hover:underline dark:text-cyan-300" href="{{ route('assets.show',$a) }}">{{ $a->name }}</a><div class="text-xs text-slate-500">expires {{ $a->warranty_expires_at->format('d M Y') }}</div></div>
      @empty<p class="text-sm text-slate-500">No warranties expiring in 30 days.</p>@endforelse
    </x-card>
    <x-card title="Billing snapshot">
      <p class="text-sm">Outstanding: <strong class="pf-display">{{ money($invoiceStats['outstanding']) }}</strong></p>
      <p class="mt-1 text-sm">Overdue invoices: <strong>{{ $invoiceStats['overdue_count'] }}</strong></p>
      <a class="mt-2 inline-block text-sm font-semibold text-cyan-700 hover:underline dark:text-cyan-300" href="{{ route('invoices.index') }}">Open invoicing →</a>
    </x-card>
  </div>
</div>
@endsection
