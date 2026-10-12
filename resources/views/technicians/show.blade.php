@extends('layouts.app')
@section('title', $technician->name)
@section('heading', $technician->name)
@section('content')
<div class="grid gap-4 md:grid-cols-3">
<x-card title="Performance (last 90 days / month)">
<p class="text-sm">Open tickets: <strong>{{ $openTickets->count() }}</strong></p>
<p class="text-sm">Time logged (month): <strong>{{ round($monthMinutes/60,1) }}h</strong> ({{ round($monthBillable/60,1) }}h billable)</p>
<p class="text-sm">Avg resolution: <strong>{{ $avgResolution ? round($avgResolution/60,1).'h' : '—' }}</strong></p>
<p class="mt-2 text-sm">Skills: @foreach($technician->skills as $s)<x-badge>{{ $s->name }}</x-badge>@endforeach</p>
</x-card>
<x-card title="Open tickets" class="md:col-span-2">
@forelse($openTickets as $t)<a href="{{ route('tickets.show',$t) }}" class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span><strong>{{ $t->number }}</strong> {{ \Str::limit($t->subject,60) }}</span>@include('tickets.partials.badges')</a>
@empty<p class="text-sm text-slate-500">No open tickets.</p>@endforelse
</x-card>
</div>
<x-card title="Edit member" class="mt-4">
<form method="POST" action="{{ route('technicians.update',$technician) }}" class="grid gap-3 text-sm md:grid-cols-3">@csrf @method('PUT')
<x-field label="Name"><input name="name" value="{{ $technician->name }}" required class="pf-input"></x-field>
<x-field label="Role"><select name="role" class="pf-select">@foreach(['owner','admin','manager','technician'] as $r)<option @selected($technician->role===$r)>{{ $r }}</option>@endforeach</select></x-field>
<x-field label="Job title"><input name="job_title" value="{{ $technician->job_title }}" class="pf-input"></x-field>
<x-field label="Teams (IDs)"><input name="team_ids" value="{{ $technician->teams->pluck('id')->join(',') }}" placeholder="e.g. 1,2 — leave blank to keep" class="pf-input"></x-field>
<div class="md:col-span-3"><x-btn>Save</x-btn></div>
</form>
</x-card>
@endsection


