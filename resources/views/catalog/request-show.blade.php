@extends('layouts.app')
@section('title', $serviceRequest->number)
@section('heading', $serviceRequest->number.' — '.$serviceRequest->item?->name)
@section('content')
<div class="mb-3"><x-badge>{{ ucfirst(str_replace('_',' ',$serviceRequest->status)) }}</x-badge></div>
<div class="grid gap-4 lg:grid-cols-3">
<div class="space-y-4 lg:col-span-2">
<x-card title="Submitted data">
<dl class="text-sm">
<dt class="text-slate-500">Requester</dt><dd>{{ $serviceRequest->requester?->name }} · {{ $serviceRequest->customer?->company_name }}</dd>
@foreach($serviceRequest->form_data ?? [] as $k=>$v)
@if(!str_starts_with($k,'_'))<dt class="mt-2 text-slate-500">{{ ucfirst(str_replace('_',' ',$k)) }}</dt><dd>{{ is_scalar($v) ? $v : json_encode($v) }}</dd>@endif
@endforeach
@if($serviceRequest->completion_evidence)<dt class="mt-2 text-slate-500">Completion evidence</dt><dd class="whitespace-pre-wrap">{{ $serviceRequest->completion_evidence }}</dd>@endif
</dl>
@if($serviceRequest->ticket)<p class="mt-2 text-sm">Fulfilment ticket: <a class="text-blue-600" href="{{ route('tickets.show',$serviceRequest->ticket) }}">{{ $serviceRequest->ticket->number }}</a></p>@endif
</x-card>
<x-card title="Approval history">
@forelse($serviceRequest->approvals ?? [] as $a)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700"><strong>{{ ucfirst($a['decision']) }}</strong> by {{ $a['name'] }} · {{ $a['at'] }}@if(!empty($a['note']))<br><span class="text-slate-500">{{ $a['note'] }}</span>@endif</div>
@empty<p class="text-sm text-slate-500">{{ $serviceRequest->item?->requires_approval ? 'Awaiting decision.' : 'No approval required for this service.' }}</p>@endforelse
@if($serviceRequest->status==='pending_approval')
<form method="POST" action="{{ route('catalog.requests.decide',$serviceRequest) }}" class="mt-2 flex gap-2 text-sm">@csrf
<select name="decision" class="pf-select"><option value="approved">Approve</option><option value="rejected">Reject</option></select>
<input name="note" placeholder="Note (optional)" class="pf-input flex-1">
<x-btn variant="secondary">Decide</x-btn>
</form>
@endif
</x-card>
</div>
<div class="space-y-4">
<x-card title="Fulfilment tasks">
@foreach($serviceRequest->tasks as $t)
<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700">
<x-badge :color="$t->status==='done'?'green':'slate'">{{ ucfirst(str_replace('_',' ',$t->status)) }}</x-badge> {{ $t->title }} <span class="text-slate-500">· {{ $t->owner?->name }}</span>
@if(!in_array($t->status,['done','skipped']))
<form method="POST" action="{{ route('catalog.tasks.complete',$t) }}" class="mt-1 flex gap-1">@csrf
<input name="evidence" placeholder="Evidence (optional)" class="pf-input flex-1">
<select name="status" class="pf-select"><option value="done">Done</option><option value="skipped">Skipped</option></select>
<button class="text-xs text-blue-600">Save</button>
</form>
@endif
</div>
@endforeach
<form method="POST" action="{{ route('catalog.requests.tasks.store',$serviceRequest) }}" class="mt-2 flex gap-2 text-sm">@csrf
<input name="title" placeholder="New task" required class="pf-input flex-1">
<x-btn variant="secondary">Add</x-btn>
</form>
</x-card>
<x-card title="Fulfil">
@if(in_array($serviceRequest->status,['approved','in_progress']))
<form method="POST" action="{{ route('catalog.requests.fulfil',$serviceRequest) }}">@csrf
<x-field label="Completion evidence *"><textarea name="completion_evidence" rows="3" required class="pf-textarea"></textarea></x-field>
<x-btn>Mark fulfilled</x-btn>
</form>
@else<p class="text-sm text-slate-500">Status: {{ $serviceRequest->status }}{{ $serviceRequest->customer_confirmed ? ' · customer confirmed' : '' }}</p>@endif
</x-card>
</div>
</div>
@endsection

