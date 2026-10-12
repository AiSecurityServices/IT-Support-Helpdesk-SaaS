@extends('layouts.guest')
@section('title','Set up two-factor authentication')
@section('content')
<p class="pf-mono mb-3 text-[0.66rem] uppercase tracking-[0.22em] text-cyan-300/80">Second factor</p>
<h1 class="pf-display mb-3 text-2xl font-bold text-white">Set up authenticator app</h1>
<ol class="mb-4 list-decimal space-y-1.5 pl-5 text-sm text-slate-300">
<li>Open Google Authenticator, Authy or 1Password.</li>
<li>Add an account with this secret key: <code class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-[0.8rem] text-cyan-200">{{ $secret }}</code></li>
<li>Or open this link on your phone: <a class="break-all text-cyan-300 hover:text-cyan-200" href="{{ $otpauth }}">{{ $otpauth }}</a></li>
<li>Enter the 6-digit code below.</li>
</ol>
<form method="POST" action="{{ route('totp.enable') }}">@csrf
<x-field label="6-digit code"><input name="code" inputmode="numeric" pattern="[0-9]{6}" required autofocus class="pf-input"></x-field>
<x-btn class="w-full justify-center">Verify & enable</x-btn>
</form>
@endsection
