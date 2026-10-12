@extends('layouts.app')
@section('title','Teams')
@section('heading','Teams & departments')
@section('content')
<div class="grid gap-4 lg:grid-cols-2">
@foreach($teams as $team)
<x-card>
  <div class="flex items-center justify-between">
    <h3 class="font-semibold">{{ $team->name }} <span class="text-xs text-slate-500">{{ $team->department?->name }} · Lead: {{ $team->lead?->name ?? '—' }}</span></h3>
    <form method="POST" action="{{ route('teams.destroy',$team) }}" onsubmit="return confirm('Delete team?')">@csrf @method('DELETE')<button class="text-xs text-red-600">Delete</button></form>
  </div>
  <div class="mt-1 flex flex-wrap gap-1">@foreach($team->members as $m)<x-badge>{{ $m->name }}</x-badge>@endforeach</div>
  <form method="POST" action="{{ route('teams.members',$team) }}" class="mt-2 flex gap-2 text-sm">@csrf
    <input name="user_ids" placeholder="Member user IDs, comma separated" class="pf-input flex-1">
    <x-btn variant="secondary">Note: manage below</x-btn>
  </form>
  <p class="mt-1 text-xs text-slate-500">Tip: open a technician below to assign teams precisely.</p>
</x-card>
@endforeach
</div>
<div class="mt-4 grid gap-4 md:grid-cols-2">
<x-card title="New team">
<form method="POST" action="{{ route('teams.store') }}">@csrf
<x-field label="Name"><input name="name" required class="pf-input"></x-field>
<x-field label="Department"><select name="department_id" class="pf-select"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></x-field>
<x-btn>Create team</x-btn>
</form>
</x-card>
<x-card title="Departments">
<form method="POST" action="{{ route('settings.departments.store') }}" class="flex gap-2 text-sm">@csrf<input name="name" placeholder="New department" required class="pf-input flex-1"><x-btn variant="secondary">Add</x-btn></form>
<ul class="mt-2 text-sm">@foreach($departments as $d)<li class="border-b py-1 last:border-0 dark:border-slate-700">{{ $d->name }} <span class="text-slate-500">({{ $d->teams->count() }} teams)</span></li>@endforeach</ul>
</x-card>
</div>
@endsection

