@extends('layouts.app')
@section('title','Impact — '.$label)
@section('heading','Impact: '.$label)
@section('content')
<div class="grid gap-4 lg:grid-cols-2">
<x-card title="Depends on / uses">
@forelse($outgoing as $r)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700">{{ str_replace('_',' ',$r->relation) }} → <a class="text-blue-600" href="{{ route('cmdb.impact',[$r->to_type,$r->to_id]) }}">{{ \App\Models\CiRelationship::label($r->to_type,$r->to_id) }}</a></div>
@empty<p class="text-sm text-slate-500">No outgoing relationships.</p>@endforelse
</x-card>
<x-card title="Used by / depended on">
@forelse($incoming as $r)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700"><a class="text-blue-600" href="{{ route('cmdb.impact',[$r->from_type,$r->from_id]) }}">{{ \App\Models\CiRelationship::label($r->from_type,$r->from_id) }}</a> → {{ str_replace('_',' ',$r->relation) }}</div>
@empty<p class="text-sm text-slate-500">No incoming relationships.</p>@endforelse
</x-card>
</div>
<x-card title="Open tickets in blast radius ({{ $tickets->count() }})" class="mt-4">
@forelse($tickets as $t)<a href="{{ route('tickets.show',$t) }}" class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span><strong>{{ $t->number }}</strong> {{ \Str::limit($t->subject,60) }} <span class="text-slate-500">· {{ $t->customer?->company_name }}</span></span>@include('tickets.partials.badges')</a>
@empty<p class="text-sm text-slate-500">No open tickets touch this item or its neighbours.</p>@endforelse
</x-card>
@endsection
