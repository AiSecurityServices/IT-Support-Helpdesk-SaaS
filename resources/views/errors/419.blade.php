@extends('layouts.guest')
@section('title','Session expired')
@section('content')
<h1 class="text-2xl font-bold">419 — Session expired</h1>
<p class="mt-2 text-sm text-slate-500">Please refresh and try again.</p>
<p class="mt-4"><a class="text-blue-600" href="{{ url()->previous() }}">← Go back</a></p>
@endsection
