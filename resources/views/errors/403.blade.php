@extends('layouts.guest')
@section('title','Forbidden')
@section('content')
<h1 class="text-2xl font-bold">403 — Not allowed</h1>
<p class="mt-2 text-sm text-slate-500">You don't have permission to view this. If you believe this is wrong, contact your workspace administrator.</p>
<p class="mt-4"><a class="text-blue-600" href="{{ route('dashboard') }}">← Back to dashboard</a></p>
@endsection
