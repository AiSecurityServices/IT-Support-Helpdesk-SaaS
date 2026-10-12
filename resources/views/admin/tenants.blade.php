@extends('layouts.admin')
@section('title','Tenants')
@section('heading','Tenants')
@section('content')
<x-card>
<form method="GET" class="mb-3 flex gap-2 text-sm"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search…" aria-label="Search workspaces" class="pf-input"><select name="status" aria-label="Filter by status" class="pf-select"><option value="">All</option>@foreach(['trial','active','past_due','suspended','cancelled'] as $s)<option @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select><x-btn variant="secondary">Filter</x-btn></form>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b"><th class="p-2">Workspace</th><th class="p-2">Plan</th><th class="p-2">Status</th><th class="p-2">Trial ends</th><th class="p-2">Created</th></tr></thead>
<tbody>@foreach($tenants as $t)<tr class="border-b"><td class="p-2"><a class="text-blue-600" href="{{ route('admin.tenants.show',$t) }}">{{ $t->name }}</a></td><td class="p-2">{{ $t->activeSubscription?->plan?->name ?? '—' }}</td><td class="p-2"><x-badge>{{ $t->status }}</x-badge></td><td class="p-2">{{ $t->trial_ends_at?->format('d M Y') ?? '—' }}</td><td class="p-2">{{ $t->created_at->format('d M Y') }}</td></tr>@endforeach</tbody>
</table></div>
<div class="mt-2">{{ $tenants->links() }}</div>
</x-card>
@endsection

