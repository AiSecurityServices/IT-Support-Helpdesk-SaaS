@extends('layouts.app')
@section('title','Search')
@section('heading','Results for “{{ $q }}”')
@section('content')
<div class="grid gap-4 md:grid-cols-3">
<x-card title="Tickets ({{ $tickets->count() }})">@forelse($tickets as $t)<a href="{{ route('tickets.show',$t) }}" class="block border-b py-1 text-sm last:border-0 dark:border-slate-700"><strong>{{ $t->number }}</strong> {{ \Str::limit($t->subject,60) }}</a>@empty<p class="text-sm text-slate-500">None.</p>@endforelse</x-card>
<x-card title="Customers ({{ $customers->count() }})">@forelse($customers as $c)<a href="{{ route('customers.show',$c) }}" class="block border-b py-1 text-sm last:border-0 dark:border-slate-700">{{ $c->company_name }}</a>@empty<p class="text-sm text-slate-500">None.</p>@endforelse</x-card>
<x-card title="Assets ({{ $assets->count() }})">@forelse($assets as $a)<a href="{{ route('assets.show',$a) }}" class="block border-b py-1 text-sm last:border-0 dark:border-slate-700">{{ $a->name }} <span class="text-slate-500">{{ $a->asset_tag }}</span></a>@empty<p class="text-sm text-slate-500">None.</p>@endforelse</x-card>
</div>
@endsection
