@extends('layouts.guest')
@section('title','Two-factor authentication')
@section('content')
<p class="pf-mono mb-3 text-[0.66rem] uppercase tracking-[0.22em] text-cyan-300/80">Second factor</p>
<h1 class="pf-display mb-2 text-2xl font-bold text-white">Check your authenticator app</h1>
<p class="mb-5 text-sm text-slate-400">Enter the 6-digit code to finish signing in.</p>
<form method="POST" action="{{ route('totp.verify') }}">@csrf
<x-field label="6-digit code"><input name="code" inputmode="numeric" pattern="[0-9]{6}" required autofocus class="pf-input"></x-field>
<x-btn class="w-full justify-center">Verify</x-btn>
</form>
@endsection
