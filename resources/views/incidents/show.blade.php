@extends('layouts.app')
@section('title', $incident->title)
@section('heading', $incident->title)
@section('content')
<div class="mb-3 flex items-center gap-2"><x-badge :color="$incident->status==='open'?'red':'slate'">{{ ucfirst($incident->status) }}</x-badge><span class="text-sm text-slate-500">Commander: {{ $incident->commander?->name ?? '—' }}</span>
<span class="ml-auto"><form method="POST" action="{{ route('incidents.transition',$incident) }}" class="flex gap-2 text-sm">@csrf
<select name="to" class="pf-select">@foreach(['open','mitigating','resolved','closed'] as $s)<option @selected($incident->status===$s)>{{ $s }}</option>@endforeach</select>
<x-btn variant="secondary">Move</x-btn></form></span></div>
<div class="grid gap-4 lg:grid-cols-3">
<div class="space-y-4 lg:col-span-2">
<x-card title="Situation updates">
@foreach($incident->updates as $u)<div class="mb-2 rounded border p-2 text-sm dark:border-slate-700"><div class="text-xs text-slate-500">{{ $u->author?->name }} · {{ $u->created_at->format('d M H:i') }} @if($u->notify_audience)<x-badge color="blue">Audience notified</x-badge>@endif</div><p class="mt-1 whitespace-pre-wrap">{{ $u->body }}</p></div>@endforeach
<form method="POST" action="{{ route('incidents.updates.store',$incident) }}" class="mt-2 space-y-2 text-sm">@csrf
<x-field label="New update *"><textarea name="body" rows="3" required class="pf-textarea"></textarea></x-field>
<label class="flex items-center gap-2"><input type="checkbox" name="notify_audience" value="1"> Notify affected customers (public-safe summary)</label>
<x-btn>Post update</x-btn>
</form>
</x-card>
</div>
<x-card title="Linked tickets ({{ $incident->tickets->count() }})">
@foreach($incident->tickets as $t)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700"><a class="text-blue-600" href="{{ route('tickets.show',$t) }}">{{ $t->number }}</a> {{ \Str::limit($t->subject,50) }}</div>@endforeach
<form method="POST" action="{{ route('incidents.tickets.link',$incident) }}" class="mt-2 flex gap-2 text-sm">@csrf
<input name="ticket_id" placeholder="Ticket ID" required class="pf-input">
<x-btn variant="secondary">Link</x-btn>
</form>
</x-card>
</div>
@endsection

