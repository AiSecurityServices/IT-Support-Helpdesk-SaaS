@extends('layouts.admin')
@section('title','Platform overview')
@section('heading','Platform overview')
@section('content')
<div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
@foreach([['Tenants',$metrics['tenants_total']],['Trial',$metrics['tenants_trial']],['Active',$metrics['tenants_active']],['Past due',$metrics['tenants_past_due']],['Suspended',$metrics['tenants_suspended']]] as $m)
<div class="rounded-xl border bg-white p-4"><div class="text-2xl font-bold">{{ $m[1] }}</div><div class="text-sm text-slate-500">{{ $m[0] }}</div></div>
@endforeach
</div>
<div class="mt-4 grid gap-4 lg:grid-cols-3">
<x-card title="Revenue (MRR, recorded billing data)">£{{ number_format($metrics['mrr_pence'],2) }}/mo</x-card>
<x-card title="Tickets platform-wide">{{ number_format($metrics['tickets_total']) }}</x-card>
<x-card title="Users">{{ number_format($metrics['users_total']) }}</x-card>
</div>
<div class="mt-4 grid gap-4 lg:grid-cols-2">
<x-card title="Recent tenants">@foreach($recent_tenants as $t)<div class="flex justify-between border-b py-1 text-sm last:border-0"><a class="text-blue-600" href="{{ route('admin.tenants.show',$t) }}">{{ $t->name }}</a><span class="text-slate-500">{{ $t->status }} · {{ $t->created_at->diffForHumans() }}</span></div>@endforeach</x-card>
<x-card title="Recent subscription events">@foreach($recent_events as $e)<div class="border-b py-1 text-sm last:border-0">{{ str_replace('_',' ',$e->type) }} <span class="text-slate-500">· tenant #{{ $e->tenant_id }} · {{ $e->created_at->diffForHumans() }}</span></div>@endforeach</x-card>
</div>
@endsection
