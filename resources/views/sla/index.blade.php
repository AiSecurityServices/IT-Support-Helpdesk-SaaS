@extends('layouts.app')
@section('title','SLA policies')
@section('heading','SLA policies & calendar')
@section('content')
<div class="grid gap-4 lg:grid-cols-2">
@foreach($policies as $p)
<x-card>
<h3 class="font-semibold">{{ $p->name }} @if($p->is_default)<x-badge color="blue">Default</x-badge>@endif @unless($p->is_active)<x-badge color="slate">Inactive</x-badge>@endunless</h3>
<p class="text-xs text-slate-500">{{ $p->description }}</p>
<table class="mt-2 w-full text-xs"><thead><tr><th class="text-left">Priority</th><th>First response</th><th>Resolution</th></tr></thead><tbody>
@foreach(config('supportflow.ticket_priorities') as $k=>$v)<tr class="border-t dark:border-slate-700"><td>{{ $v }}</td><td class="text-center">{{ $p->targets[$k]['first_response'] ?? '—' }} min</td><td class="text-center">{{ $p->targets[$k]['resolution'] ?? '—' }} min</td></tr>@endforeach
</tbody></table>
<p class="mt-1 text-xs text-slate-500">Working: days {{ implode(',', $p->working_hours['days'] ?? []) }} {{ $p->working_hours['start'] ?? '' }}–{{ $p->working_hours['end'] ?? '' }} · Pause on waiting: {{ $p->pause_on_pending_customer ? 'yes' : 'no' }}</p>
<details class="mt-2 text-sm"><summary class="cursor-pointer text-blue-600">Edit policy</summary>
<form method="POST" action="{{ route('sla.update',$p) }}" class="mt-2 space-y-2">@csrf @method('PUT')
<x-field label="Name"><input name="name" value="{{ $p->name }}" required class="pf-input"></x-field>
<div class="grid grid-cols-2 gap-2">
<x-field label="Working days (1=Mon…7=Sun, comma)"><input name="working_days" value="{{ implode(',',$p->working_hours['days'] ?? [1,2,3,4,5]) }}" required class="pf-input"></x-field>
</div>
<div class="grid grid-cols-2 gap-2">
<x-field label="Day start"><input type="time" name="work_start" value="{{ $p->working_hours['start'] ?? '09:00' }}" class="pf-input"></x-field>
<x-field label="Day end"><input type="time" name="work_end" value="{{ $p->working_hours['end'] ?? '17:30' }}" class="pf-input"></x-field>
</div>
@foreach(config('supportflow.ticket_priorities') as $k=>$v)
<div class="grid grid-cols-3 items-center gap-2 text-sm"><span>{{ $v }}</span><input type="number" name="targets[{{ $k }}][first_response]" value="{{ $p->targets[$k]['first_response'] ?? '' }}" placeholder="response min" class="pf-input"><input type="number" name="targets[{{ $k }}][resolution]" value="{{ $p->targets[$k]['resolution'] ?? '' }}" placeholder="resolution min" class="pf-input"></div>
@endforeach
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="pause_on_pending_customer" value="1" @checked($p->pause_on_pending_customer)> Pause timers while awaiting customer</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($p->is_active)> Active</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked($p->is_default)> Default policy</label>
<x-btn variant="secondary">Save policy</x-btn>
</form>
</details>
</x-card>
@endforeach
</div>
<div class="mt-4 grid gap-4 md:grid-cols-2">
<x-card title="New SLA policy">
<form method="POST" action="{{ route('sla.store') }}" class="space-y-2 text-sm">@csrf
<x-field label="Name"><input name="name" required class="pf-input"></x-field>
@foreach(config('supportflow.ticket_priorities') as $k=>$v)
<div class="grid grid-cols-3 items-center gap-2"><span>{{ $v }}</span><input type="number" name="targets[{{ $k }}][first_response]" required placeholder="response min" class="pf-input"><input type="number" name="targets[{{ $k }}][resolution]" required placeholder="resolution min" class="pf-input"></div>
@endforeach
<x-field label="Working days (1=Mon…7=Sun, comma)"><input name="working_days" value="1,2,3,4,5" required class="pf-input"></x-field>
<div class="grid grid-cols-2 gap-2"><x-field label="Day start"><input type="time" name="work_start" value="09:00" class="pf-input"></x-field><x-field label="Day end"><input type="time" name="work_end" value="17:30" class="pf-input"></x-field></div>
<label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1"> Default policy</label>
<x-btn>Create policy</x-btn>
</form>
</x-card>
<x-card title="Public holidays (excluded from SLA math)">
<form method="POST" action="{{ route('sla.holidays.store') }}" class="flex flex-wrap gap-2 text-sm">@csrf
<input name="name" placeholder="Holiday name" required class="pf-input">
<input type="date" name="date" required class="pf-input">
<x-btn variant="secondary">Add</x-btn>
</form>
<ul class="mt-2 text-sm">@forelse($holidays as $h)<li class="flex justify-between border-b py-1 last:border-0 dark:border-slate-700"><span>{{ $h->name }} — {{ $h->date->format('d M Y') }}</span><form method="POST" action="{{ route('sla.holidays.destroy',$h) }}">@csrf @method('DELETE')<button class="text-xs text-red-600">Remove</button></form></li>@empty<li class="text-slate-500">No holidays configured.</li>@endforelse</ul>
</x-card>
</div>
@endsection


