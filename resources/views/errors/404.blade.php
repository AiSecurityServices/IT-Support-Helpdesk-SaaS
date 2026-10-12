@extends('layouts.guest')
@section('title','Not found')
@section('content')
<h1 class="text-2xl font-bold">404 — Not found</h1>
<p class="mt-2 text-sm text-slate-500">The page or record doesn't exist, or belongs to another workspace.</p>
<p class="mt-4"><a class="text-blue-600" href="{{ route('dashboard') }}">← Back to dashboard</a></p>
@endsection
