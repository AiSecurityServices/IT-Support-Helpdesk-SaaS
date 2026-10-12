@extends('layouts.app')
@section('title','Asset report')
@section('heading','Asset report')
@section('content')
<div class="grid gap-4 md:grid-cols-2">
<x-card title="By status">@foreach($by_status as $k=>$v)<div class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span>{{ ucfirst(str_replace('_',' ',$k)) }}</span><span>{{ $v }}</span></div>@endforeach</x-card>
<x-card title="By type">@foreach($by_type as $k=>$v)<div class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span>{{ ucfirst($k) }}</span><span>{{ $v }}</span></div>@endforeach</x-card>
</div>
<x-card title="Warranty expirations (90 days)" class="mt-4">
@forelse($expiring as $a)<div class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span><a class="text-blue-600" href="{{ route('assets.show',$a) }}">{{ $a->name }}</a> {{ $a->customer?->company_name }}</span><span>{{ $a->warranty_expires_at->format('d M Y') }}</span></div>
@empty<p class="text-sm text-slate-500">No upcoming expirations.</p>@endforelse
</x-card>
@endsection
