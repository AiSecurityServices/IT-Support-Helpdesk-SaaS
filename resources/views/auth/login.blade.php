@extends('layouts.guest')
@section('title','Sign in')
@section('content')
<p class="pf-mono mb-3 text-[0.66rem] uppercase tracking-[0.22em] text-cyan-300/80">Operator access</p>
<h1 class="pf-display mb-1 text-2xl font-bold text-white">Sign in to your workspace</h1>
<p class="mb-5 text-sm text-slate-400">Tickets, SLAs and assets — where you left them.</p>
<form method="POST" action="{{ route('login') }}">@csrf
  <x-field label="Email"><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="pf-input"></x-field>
  <x-field label="Password"><input id="password" type="password" name="password" required autocomplete="current-password" class="pf-input"></x-field>
  <label class="mb-4 flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" name="remember" class="pf-check"> Remember me</label>
  <x-btn class="w-full justify-center">Sign in</x-btn>
  <div class="mt-4 flex justify-between text-sm">
    <a class="text-cyan-300 hover:text-cyan-200" href="{{ route('password.request') }}">Forgot password?</a>
    <a class="text-cyan-300 hover:text-cyan-200" href="{{ route('register') }}">Start free trial</a>
  </div>
</form>
@endsection
