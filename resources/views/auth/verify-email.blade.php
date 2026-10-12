@extends('layouts.guest')
@section('title','Verify email')
@section('content')
<h1 class="pf-display mb-2 text-2xl font-bold text-white">Verify your email</h1>
<p class="mb-5 text-sm text-slate-400">We sent a verification link to your inbox. Click it to activate your account.</p>
<form method="POST" action="{{ route('verification.send') }}">@csrf<x-btn class="w-full justify-center">Resend verification email</x-btn></form>
<form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">@csrf<button class="text-sm text-slate-400 underline hover:text-slate-200">Log out</button></form>
@endsection
