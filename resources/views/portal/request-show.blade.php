@extends('layouts.portal')
@section('title', $serviceRequest->number)
@section('heading', $serviceRequest->number.' — '.$serviceRequest->item?->name)
@section('content')
<div class="mb-3"><x-badge>{{ ucfirst(str_replace('_',' ',$serviceRequest->status)) }}</x-badge></div>
<x-card title="Details">
<dl class="text-sm">
<dt class="text-slate-500">Submitted</dt><dd>{{ $serviceRequest->created_at->format('d M Y H:i') }}</dd>
<dt class="mt-2 text-slate-500">Answers</dt>
@foreach($serviceRequest->form_data ?? [] as $k=>$v)
@if(!str_starts_with($k,'_'))<dd>{{ ucfirst(str_replace('_',' ',$k)) }}: {{ is_scalar($v) ? $v : json_encode($v) }}</dd>@endif
@endforeach
@if($serviceRequest->completion_evidence)<dt class="mt-2 text-slate-500">Completion evidence</dt><dd class="whitespace-pre-wrap">{{ $serviceRequest->completion_evidence }}</dd>@endif
</dl>
@if($serviceRequest->ticket)<p class="mt-2 text-sm">Linked ticket: <a class="text-blue-600" href="{{ route('portal.tickets.show',$serviceRequest->ticket) }}">{{ $serviceRequest->ticket->number }}</a></p>@endif
</x-card>
<x-card title="Fulfilment tasks" class="mt-4">
@foreach($serviceRequest->tasks as $t)<div class="border-b py-1 text-sm last:border-0"><x-badge :color="$t->status==='done'?'green':'slate'">{{ ucfirst(str_replace('_',' ',$t->status)) }}</x-badge> {{ $t->title }}@if($t->evidence)<br><span class="text-xs text-slate-500">{{ $t->evidence }}</span>@endif</div>@endforeach
</x-card>
@if($serviceRequest->status==='fulfilled' && !$serviceRequest->customer_confirmed)
<form method="POST" action="{{ route('portal.requests.confirm',$serviceRequest) }}" class="mt-4">@csrf<x-btn>Confirm completion</x-btn></form>
@endif
@endsection
