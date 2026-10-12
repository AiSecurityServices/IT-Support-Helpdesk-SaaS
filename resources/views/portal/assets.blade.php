@extends('layouts.portal')
@section('title','My devices')
@section('heading','My devices')
@section('content')
<x-card>
<div class="grid gap-3 md:grid-cols-2">
@foreach($assets as $a)<div class="rounded border p-3 dark:border-slate-700"><strong>{{ $a->name }}</strong><div class="text-sm text-slate-500">{{ $a->manufacturer }} {{ $a->model }} · {{ ucfirst(str_replace('_',' ',$a->status)) }}@if($a->warranty_expires_at) · warranty until {{ $a->warranty_expires_at->format('d M Y') }}@endif</div></div>@endforeach
</div>
<div class="mt-2">{{ $assets->links() }}</div>
</x-card>
@endsection
