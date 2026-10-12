@extends('layouts.app')
@section('title','Assets')
@section('heading','Assets')
@section('content')
<div class="mb-3 flex flex-wrap gap-2 text-sm">
  <form method="GET" class="flex flex-wrap gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, tag, serial…" aria-label="Search assets" class="pf-input">
    <select name="status" aria-label="Filter by status" class="pf-select"><option value="">All statuses</option>@foreach(config('supportflow.asset_statuses') as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select>
    <select name="customer_id" aria-label="Filter by customer" class="pf-select"><option value="">All customers</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->company_name }}</option>@endforeach</select>
    <label class="flex items-center gap-1"><input type="checkbox" name="warranty" value="expiring" @checked(request('warranty')==='expiring')> Expiring ≤30d</label>
    <x-btn variant="secondary">Filter</x-btn>
  </form>
  <span class="ml-auto flex gap-2">
    <a href="{{ route('assets.export') }}" class="rounded-lg border px-3 py-1">Export CSV</a>
    <a href="{{ route('assets.create') }}" class="rounded-lg bg-blue-600 px-4 py-1 text-white">+ New asset</a>
  </span>
</div>
<x-card>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th class="p-2">Tag</th><th class="p-2">Name</th><th class="p-2">Type</th><th class="p-2">Customer</th><th class="p-2">Assigned</th><th class="p-2">Warranty</th><th class="p-2">Status</th></tr></thead>
<tbody>
@forelse($assets as $a)
<tr class="border-b hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/50">
<td class="p-2 text-slate-500">{{ $a->asset_tag }}</td>
<td class="p-2"><a class="font-medium text-blue-600" href="{{ route('assets.show',$a) }}">{{ $a->name }}</a></td>
<td class="p-2">{{ ucfirst($a->type) }}</td>
<td class="p-2">{{ $a->customer?->company_name ?? '—' }}</td>
<td class="p-2">{{ $a->assignee?->name ?? '—' }}</td>
<td class="p-2">@if($a->warranty_expires_at)<span class="{{ $a->warrantyDaysLeft()<=30?'text-red-600 font-medium':'' }}">{{ $a->warranty_expires_at->format('d M Y') }}</span>@else — @endif</td>
<td class="p-2"><x-badge>{{ ucfirst(str_replace('_',' ',$a->status)) }}</x-badge></td>
</tr>
@empty<tr><td colspan="7" class="p-6 text-center text-slate-500">No assets found.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-2">{{ $assets->links() }}</div>
</x-card>
<x-card title="Import from CSV" class="mt-4">
<form method="POST" action="{{ route('assets.import') }}" enctype="multipart/form-data" class="flex items-center gap-2 text-sm">@csrf
<input type="file" name="file" accept=".csv,.txt" required aria-label="CSV file to import"><x-btn variant="secondary">Import</x-btn>
<span class="text-xs text-slate-500">Columns: name, type, manufacturer, model, serial_number, status. Duplicate serials are skipped.</span>
</form>
</x-card>
@endsection

