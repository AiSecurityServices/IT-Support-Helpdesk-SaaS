@extends('layouts.admin')
@section('title','Audit log')
@section('heading','Security audit events')
@section('content')
<x-card>
@forelse($logs as $l)<div class="border-b py-2 text-sm last:border-0"><strong>{{ $l->action }}</strong> <span class="text-slate-500">tenant #{{ $l->tenant_id ?? '—' }} · actor #{{ $l->actor_id ?? '—' }} · {{ $l->created_at->format('d M Y H:i') }} · {{ $l->ip_address }}</span>@if($l->meta)<pre class="text-xs text-slate-500">{{ json_encode($l->meta) }}</pre>@endif</div>
@empty<p class="text-sm text-slate-500">No events.</p>@endforelse
<div class="mt-2">{{ $logs->links() }}</div>
</x-card>
@endsection
