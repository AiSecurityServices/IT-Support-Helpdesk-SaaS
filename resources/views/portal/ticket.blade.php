@extends('layouts.portal')
@section('title', $ticket->number)
@section('heading', $ticket->number.' — '.$ticket->subject)
@section('content')
<div class="mb-3"><x-badge>{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</x-badge> <x-badge :color="$ticket->priority==='urgent'?'red':'blue'">{{ ucfirst($ticket->priority) }}</x-badge></div>
<x-card title="Details"><p class="whitespace-pre-wrap text-sm">{{ $ticket->description }}</p></x-card>
<x-card title="Conversation" class="mt-4">
@forelse($ticket->publicMessages as $m)<div class="mb-2 rounded border p-3 text-sm dark:border-slate-700"><div class="text-xs text-slate-500"><strong>{{ $m->author?->name }}</strong> · {{ $m->created_at->format('d M Y H:i') }}</div><p class="mt-1 whitespace-pre-wrap">{{ $m->body }}</p></div>
@empty<p class="text-sm text-slate-500">No replies yet.</p>@endforelse
@if(!in_array($ticket->status,['closed']))
<form method="POST" action="{{ route('portal.tickets.reply',$ticket) }}" class="mt-3">@csrf
<x-field label="Reply to support"><textarea name="body" rows="3" required class="pf-textarea"></textarea></x-field>
<x-btn>Send reply</x-btn>
</form>
@endif
@if($ticket->status==='resolved')
<form method="POST" action="{{ route('tickets.rate',$ticket) }}" class="mt-3 border-t pt-3">@csrf
<x-field label="How was the support? (1–5)"><select name="satisfaction_rating" class="pf-select"><option value="5">5 — Excellent</option><option value="4">4 — Good</option><option value="3">3 — OK</option><option value="2">2 — Poor</option><option value="1">1 — Bad</option></select></x-field>
<x-btn variant="secondary">Submit rating</x-btn>
</form>
@endif
</x-card>
@endsection

