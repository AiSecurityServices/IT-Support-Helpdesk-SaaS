@extends('layouts.app')
@section('title','Tickets')
@section('eyebrow','Service desk')
@section('heading','Tickets')
@section('actions')
<a href="{{ route('tickets.create') }}" class="pf-btn pf-btn-primary pf-btn-sm"><x-icon name="plus" /> New ticket</a>
@endsection
@section('content')
@php
$queues = ['all'=>'All','mine'=>'Mine','unassigned'=>'Unassigned','team'=>'Team','urgent'=>'Urgent','overdue'=>'Overdue','awaiting_customer'=>'Awaiting customer','awaiting_internal'=>'Awaiting internal','resolved'=>'Resolved','closed'=>'Closed'];
@endphp
<div class="mb-4 flex flex-wrap gap-1.5" role="tablist" aria-label="Ticket queues">
  @foreach($queues as $k=>$label)
  <a role="tab" aria-selected="{{ $queue===$k ? 'true' : 'false' }}" href="{{ route('tickets.index',['queue'=>$k]) }}" class="{{ $queue===$k ? 'pf-btn pf-btn-primary pf-btn-sm' : 'pf-btn pf-btn-ghost pf-btn-sm' }}">{{ $label }}</a>
  @endforeach
</div>
<x-card>
<form method="GET" class="mb-4 grid gap-2.5 md:grid-cols-4" role="search" aria-label="Filter tickets">
  <x-field label="Search"><input type="search" name="q" value="{{ request('q') }}" placeholder="Subject, number, tags…" class="pf-input"></x-field>
  <x-field label="Status"><select name="status" class="pf-select"><option value="">All statuses</option>@foreach(config('supportflow.ticket_statuses') as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select></x-field>
  <x-field label="Priority"><select name="priority" class="pf-select"><option value="">All priorities</option>@foreach(config('supportflow.ticket_priorities') as $k=>$v)<option value="{{ $k }}" @selected(request('priority')===$k)>{{ $v }}</option>@endforeach</select></x-field>
  <x-field label="Customer"><select name="customer_id" class="pf-select"><option value="">All customers</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->company_name }}</option>@endforeach</select></x-field>
  <div class="flex gap-2 md:col-span-4"><x-btn>Filter</x-btn><a href="{{ route('tickets.index',['queue'=>$queue]) }}" class="pf-btn pf-btn-ghost pf-btn-sm">Reset</a></div>
</form>
<form method="POST" action="{{ route('tickets.bulk') }}">@csrf
<div class="pf-tablewrap">
<table class="pf-table">
<thead><tr><th scope="col"><input type="checkbox" class="pf-check" onclick="document.querySelectorAll('.tick').forEach(c=>c.checked=this.checked)" aria-label="Select all"></th><th scope="col">Ticket</th><th scope="col">Customer</th><th scope="col">Assignee</th><th scope="col">Status</th><th scope="col">Updated</th></tr></thead>
<tbody>
@forelse($tickets as $t)
<tr>
  <td data-label="Select"><input type="checkbox" name="ids[]" value="{{ $t->id }}" class="tick pf-check" aria-label="Select ticket {{ $t->number }}"></td>
  <td data-label="Ticket"><a href="{{ route('tickets.show',$t) }}" class="font-semibold text-cyan-700 hover:underline dark:text-cyan-300"><span class="pf-mono">{{ $t->number }}</span></a> {{ \Str::limit($t->subject,60) }}</td>
  <td data-label="Customer">{{ $t->customer?->company_name ?? '—' }}</td>
  <td data-label="Assignee">{{ $t->assignee?->name ?? 'Unassigned' }}</td>
  <td data-label="Status">@include('tickets.partials.badges',['ticket'=>$t])</td>
  <td data-label="Updated" class="text-slate-500">{{ $t->updated_at->diffForHumans() }}</td>
</tr>
@empty<tr><td colspan="6"><div class="pf-empty"><x-icon name="ticket" class="pf-icon-lg" /><p class="text-sm">No tickets in this queue.</p></div></td></tr>@endforelse
</tbody>
</table>
</div>
<div class="mt-3 flex flex-wrap items-center gap-2">
  <x-field label="Bulk action"><select name="action" class="pf-select"><option value="watch">Watch</option><option value="close">Close</option><option value="status">Set status…</option><option value="assign">Assign…</option></select></x-field>
  <x-field label="Value"><input name="status" placeholder="open / resolved…" class="pf-input"></x-field>
  <x-field label="Assignee"><select name="assigned_to" class="pf-select"><option value="">— assignee —</option>@foreach($technicians as $tech)<option value="{{ $tech->id }}">{{ $tech->name }}</option>@endforeach</select></x-field>
  <x-btn variant="secondary">Apply to selected</x-btn>
  <span class="ml-auto">{{ $tickets->links() }}</span>
</div>
</form>
</x-card>
@endsection
