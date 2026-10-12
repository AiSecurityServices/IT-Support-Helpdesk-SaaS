@extends('layouts.app')
@section('title', $asset->name)
@section('heading', $asset->asset_tag.' — '.$asset->name)
@section('content')
<div class="mb-3 flex gap-2"><x-badge>{{ ucfirst(str_replace('_',' ',$asset->status)) }}</x-badge><span class="ml-auto flex gap-2 text-sm"><a href="{{ route('assets.edit',$asset) }}" class="rounded-lg border px-3 py-1">Edit</a></span></div>
<div class="grid gap-4 lg:grid-cols-3">
<div class="space-y-4 lg:col-span-2">
<x-card title="Details">
<dl class="grid grid-cols-2 gap-2 text-sm md:grid-cols-3">
<dt class="text-slate-500">Type</dt><dd>{{ ucfirst($asset->type) }}</dd>
<dt class="text-slate-500">Manufacturer</dt><dd>{{ $asset->manufacturer ?? '—' }}</dd>
<dt class="text-slate-500">Model</dt><dd>{{ $asset->model ?? '—' }}</dd>
<dt class="text-slate-500">Serial</dt><dd>{{ $asset->serial_number ?? '—' }}</dd>
<dt class="text-slate-500">Computer</dt><dd>{{ $asset->computer_name ?? '—' }}</dd>
<dt class="text-slate-500">OS</dt><dd>{{ $asset->operating_system ?? '—' }} {{ $asset->os_version }}</dd>
<dt class="text-slate-500">IP</dt><dd>{{ $asset->ip_address ?? '—' }}</dd>
<dt class="text-slate-500">Customer</dt><dd>{{ $asset->customer?->company_name ?? '—' }}</dd>
<dt class="text-slate-500">Assigned to</dt><dd>{{ $asset->assignee?->name ?? '—' }}</dd>
<dt class="text-slate-500">Purchased</dt><dd>{{ $asset->purchase_date?->format('d M Y') ?? '—' }} ({{ $asset->supplier ?? '—' }})</dd>
<dt class="text-slate-500">Warranty</dt><dd>{{ $asset->warranty_expires_at?->format('d M Y') ?? '—' }}</dd>
</dl>
<p class="mt-2 whitespace-pre-wrap text-sm">{{ $asset->notes }}</p>
</x-card>
<x-card title="Linked tickets">
@forelse($asset->tickets as $t)<a href="{{ route('tickets.show',$t) }}" class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span><strong>{{ $t->number }}</strong> {{ \Str::limit($t->subject,60) }}</span>@include('tickets.partials.badges')</a>
@empty<p class="text-sm text-slate-500">No linked tickets.</p>@endforelse
</x-card>
</div>
<div class="space-y-4">
<x-card title="Add maintenance note">
<form method="POST" action="{{ route('assets.note',$asset) }}">@csrf
<x-field label="Details"><textarea name="details" rows="3" required class="pf-textarea"></textarea></x-field>
<x-btn variant="secondary">Save record</x-btn>
</form>
</x-card>
<x-card title="History">
@foreach($asset->history as $h)<div class="border-b py-1 text-xs last:border-0 dark:border-slate-700"><strong>{{ str_replace('_',' ',$h->action) }}</strong> · {{ $h->created_at->diffForHumans() }}<br><span class="text-slate-500">{{ \Str::limit($h->details,120) }}</span></div>@endforeach
</x-card>
</div>
</div>
@endsection

