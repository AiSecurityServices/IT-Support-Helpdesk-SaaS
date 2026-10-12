@extends('layouts.app')
@section('title', $change->title)
@section('heading', $change->title)
@section('content')
<div class="mb-3 flex gap-2"><x-badge>{{ ucfirst(str_replace('_',' ',$change->status)) }}</x-badge><x-badge>{{ ucfirst($change->type) }}</x-badge></div>
<div class="grid gap-4 lg:grid-cols-3">
<div class="space-y-4 lg:col-span-2">
<x-card title="Plans">
<form method="POST" action="{{ route('changes.update',$change) }}" class="space-y-2 text-sm">@csrf @method('PUT')
<x-field label="Impact"><textarea name="impact" rows="2" class="pf-textarea">{{ $change->impact }}</textarea></x-field>
<x-field label="Implementation plan *"><textarea name="implementation_plan" rows="3" class="pf-textarea">{{ $change->implementation_plan }}</textarea></x-field>
<x-field label="Test plan"><textarea name="test_plan" rows="2" class="pf-textarea">{{ $change->test_plan }}</textarea></x-field>
<x-field label="Rollback plan *"><textarea name="rollback_plan" rows="3" class="pf-textarea">{{ $change->rollback_plan }}</textarea></x-field>
<div class="grid grid-cols-2 gap-2">
<x-field label="Window start"><input type="datetime-local" name="window_start" value="{{ $change->window_start?->format('Y-m-d\TH:i') }}" class="pf-input"></x-field>
<x-field label="Window end"><input type="datetime-local" name="window_end" value="{{ $change->window_end?->format('Y-m-d\TH:i') }}" class="pf-input"></x-field>
</div>
<x-btn variant="secondary">Save plans</x-btn>
</form>
@if($change->status==='draft')
<form method="POST" action="{{ route('changes.submit',$change) }}" class="mt-2">@csrf<x-btn>Submit for approval</x-btn></form>
@endif
</x-card>
<x-card title="Approvals (audited)">
@forelse($change->approvals ?? [] as $a)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700"><strong>{{ ucfirst($a['decision']) }}</strong> by {{ $a['name'] }} · {{ $a['at'] }}@if(!empty($a['note']))<br><span class="text-slate-500">{{ $a['note'] }}</span>@endif</div>
@empty<p class="text-sm text-slate-500">No decisions yet.</p>@endforelse
@if($change->status==='pending_approval')
<form method="POST" action="{{ route('changes.decide',$change) }}" class="mt-2 flex gap-2 text-sm">@csrf
<select name="decision" class="pf-select"><option value="approved">Approve</option><option value="rejected">Reject</option></select>
<input name="note" placeholder="Note" class="pf-input flex-1">
<x-btn variant="secondary">Decide</x-btn>
</form>
@endif
</x-card>
@if(in_array($change->status,['approved','scheduled']))
<x-card title="Record implementation">
<form method="POST" action="{{ route('changes.implement',$change) }}" class="space-y-2 text-sm">@csrf
<x-field label="Outcome *"><textarea name="outcome" rows="3" required class="pf-textarea"></textarea></x-field>
<x-field label="Result"><select name="result" class="pf-select"><option value="implemented">Implemented</option><option value="failed">Failed (rollback used)</option></select></x-field>
<x-btn>Record outcome</x-btn>
</form>
</x-card>
@endif
@if($change->outcome)<x-card title="Outcome"><p class="whitespace-pre-wrap text-sm">{{ $change->outcome }}</p></x-card>@endif
</div>
<x-card title="Linked tickets">
@foreach($change->tickets as $t)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700"><a class="text-blue-600" href="{{ route('tickets.show',$t) }}">{{ $t->number }}</a></div>@endforeach
<form method="POST" action="{{ route('changes.tickets.link',$change) }}" class="mt-2 flex gap-2 text-sm">@csrf
<input name="ticket_id" placeholder="Ticket ID" required class="pf-input">
<x-btn variant="secondary">Link</x-btn>
</form>
</x-card>
</div>
@endsection


