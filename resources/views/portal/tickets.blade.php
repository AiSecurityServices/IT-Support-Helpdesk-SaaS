@extends('layouts.portal')
@section('title','My requests')
@section('heading','My support requests')
@section('actions')
<a href="{{ route('portal.tickets.create') }}" class="pf-btn pf-btn-primary pf-btn-sm"><x-icon name="plus" /> New request</a>
@endsection
@section('content')
<x-card>
<form method="GET" class="mb-4 flex gap-2" role="search" aria-label="Search your requests"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search…" aria-label="Search your requests" class="pf-input max-w-xs"><x-btn variant="secondary">Search</x-btn></form>
@forelse($tickets as $t)<a href="{{ route('portal.tickets.show',$t) }}" class="flex items-center justify-between gap-2 border-b border-slate-200 py-2.5 text-sm last:border-0 dark:border-slate-700"><span><span class="pf-mono font-semibold">{{ $t->number }}</span> {{ $t->subject }}<br><span class="text-xs text-slate-500">{{ $t->category?->name }} · {{ $t->updated_at->diffForHumans() }}</span></span><x-badge>{{ ucfirst(str_replace('_',' ',$t->status)) }}</x-badge></a>
@empty<div class="pf-empty"><x-icon name="ticket" class="pf-icon-lg" /><p class="text-sm">No requests found.</p></div>@endforelse
<div class="mt-3">{{ $tickets->links() }}</div>
</x-card>
@endsection
