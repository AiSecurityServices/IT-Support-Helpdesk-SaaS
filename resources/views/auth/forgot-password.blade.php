@extends('layouts.guest')
@section('title','Forgot password')
@section('content')
<h1 class="pf-display mb-2 text-2xl font-bold text-white">Reset your password</h1>
<p class="mb-5 text-sm text-slate-400">Enter your email and we’ll send a reset link.</p>
<form method="POST" action="{{ route('password.email') }}">@csrf
  <x-field label="Email"><input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="pf-input"></x-field>
  <x-btn class="w-full justify-center">Email reset link</x-btn>
</form>
@endsection
