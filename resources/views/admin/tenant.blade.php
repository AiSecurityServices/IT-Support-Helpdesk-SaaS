@extends('layouts.admin')
@section('title', $tenant->name)
@section('heading', $tenant->name)
@section('content')
<div class="mb-3 flex gap-2 text-sm">
<x-badge>{{ $tenant->status }}</x-badge>
<span class="ml-auto flex gap-2">
@if($tenant->status==='suspended')
<form method="POST" action="{{ route('admin.tenants.reactivate',$tenant) }}">@csrf<button class="rounded-lg bg-green-600 px-3 py-1 text-white">Reactivate</button></form>
@else
<form method="POST" action="{{ route('admin.tenants.suspend',$tenant) }}" class="flex gap-1" onsubmit="return confirm('Suspend this tenant?')">@csrf<input name="reason" placeholder="Reason (required)" required class="pf-input"><button class="rounded-lg border border-red-300 px-3 py-1 text-red-600">Suspend</button></form>
@endif
</span>
</div>
<div class="grid gap-4 lg:grid-cols-2">
<x-card title="Workspace">
<dl class="text-sm"><dt class="text-slate-500">Email</dt><dd>{{ $tenant->email }}</dd><dt class="mt-2 text-slate-500">Plan</dt><dd>{{ $tenant->activeSubscription?->plan?->name }} ({{ $tenant->activeSubscription?->status }})</dd><dt class="mt-2 text-slate-500">Usage</dt><dd>{{ $usage['users'] }} users · {{ $usage['tickets'] }} tickets</dd><dt class="mt-2 text-slate-500">Members</dt><dd>@foreach($tenant->users as $u)<x-badge>{{ $u->name }} ({{ $u->pivot->role }})</x-badge> @endforeach</dd></dl>
</x-card>
<x-card title="Billing events">
@foreach($events as $e)<div class="border-b py-1 text-sm last:border-0">{{ str_replace('_',' ',$e->type) }} <span class="text-slate-500">{{ $e->created_at->format('d M Y H:i') }}</span></div>@endforeach
</x-card>
</div>
<p class="mt-3 text-xs text-slate-500">Support access to tenant records requires explicit authorisation and is audit-logged. This screen intentionally shows aggregates only.</p>
@endsection

