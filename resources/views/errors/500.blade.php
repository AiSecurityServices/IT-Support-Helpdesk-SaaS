@extends('layouts.guest')
@section('title','Something went wrong')
@section('content')
<h1 class="text-2xl font-bold">500 — Something went wrong</h1>
<p class="mt-2 text-sm text-slate-500">Our team has been notified. Please try again, or contact {{ config('branding.support_email') }}.</p>
<p class="mt-4"><a class="text-blue-600" href="{{ route('dashboard') }}">← Back to dashboard</a></p>
@endsection
