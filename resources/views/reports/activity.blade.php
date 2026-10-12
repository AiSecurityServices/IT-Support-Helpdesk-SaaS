@extends('layouts.app')
@section('title','Audit activity')
@section('heading','Audit activity')
@section('content')
<x-card>
@forelse($logs as $l)<div class="border-b py-2 text-sm last:border-0 dark:border-slate-700"><strong>{{ $l->action }}</strong> <span class="text-slate-500">{{ $l->subject_type ? class_basename($l->subject_type).'#'.$l->subject_id : '' }} · {{ $l->created_at->format('d M Y H:i') }} · {{ $l->ip_address }}</span>@if($l->meta)<pre class="mt-1 whitespace-pre-wrap text-xs text-slate-500">{{ json_encode($l->meta, JSON_PRETTY_PRINT) }}</pre>@endif</div>
@empty<p class="text-sm text-slate-500">No audit events yet.</p>@endforelse
<div class="mt-2">{{ $logs->links() }}</div>
</x-card>
@endsection
