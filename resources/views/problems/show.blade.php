@extends('layouts.app')
@section('title', $problem->title)
@section('heading', $problem->title)
@section('content')
<div class="mb-3"><x-badge>{{ ucfirst(str_replace('_',' ',$problem->status)) }}</x-badge> @if($problem->known_error)<x-badge color="yellow">Known error</x-badge>@endif</div>
<div class="grid gap-4 lg:grid-cols-3">
<div class="space-y-4 lg:col-span-2">
<x-card title="Investigation">
<form method="POST" action="{{ route('problems.update',$problem) }}" class="space-y-2 text-sm">@csrf @method('PUT')
<x-field label="Status"><select name="status" class="pf-select">@foreach(['open','investigating','known_error','resolved','closed'] as $s)<option @selected($problem->status===$s)>{{ $s }}</option>@endforeach</select></x-field>
<x-field label="Root cause"><textarea name="root_cause" rows="3" class="pf-textarea">{{ $problem->root_cause }}</textarea></x-field>
<x-field label="Workaround"><textarea name="workaround" rows="3" class="pf-textarea">{{ $problem->workaround }}</textarea></x-field>
<label class="flex items-center gap-2"><input type="checkbox" name="known_error" value="1" @checked($problem->known_error)> Known error</label>
<x-btn variant="secondary">Save</x-btn>
</form>
</x-card>
<x-card title="Linked incidents ({{ $problem->tickets->count() }})">
@foreach($problem->tickets as $t)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700"><a class="text-blue-600" href="{{ route('tickets.show',$t) }}">{{ $t->number }}</a> {{ \Str::limit($t->subject,60) }}</div>@endforeach
<form method="POST" action="{{ route('problems.tickets.link',$problem) }}" class="mt-2 flex gap-2 text-sm">@csrf
<input name="ticket_id" placeholder="Ticket ID" required class="pf-input">
<x-btn variant="secondary">Link ticket</x-btn>
</form>
</x-card>
</div>
<x-card title="Description"><p class="whitespace-pre-wrap text-sm">{{ $problem->description ?? '—' }}</p></x-card>
</div>
@endsection

