@extends('layouts.admin')
@section('title','System health')
@section('heading','System health')
@section('content')
<div class="grid gap-4 lg:grid-cols-2">
<x-card title="Queue: {{ $queue }}">Failed jobs: {{ $failed_jobs->count() }} (latest shown)
@foreach($failed_jobs as $j)<div class="border-b py-1 text-xs last:border-0">{{ $j->connection }} / {{ $j->queue }} · {{ $j->failed_at }}<br><span class="text-slate-500">{{ \Str::limit($j->exception,200) }}</span></div>@endforeach
</x-card>
<x-card title="Failed webhook deliveries">Queue a retry by re-saving the endpoint.
@foreach($webhook_failures as $w)<div class="border-b py-1 text-xs last:border-0">{{ $w->event }} · endpoint #{{ $w->webhook_endpoint_id }} · attempts {{ $w->attempts }} · {{ $w->created_at->diffForHumans() }}</div>@endforeach
</x-card>
</div>
@endsection
