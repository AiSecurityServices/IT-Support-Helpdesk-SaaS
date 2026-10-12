@extends('layouts.guest')
@section('title','Confirm password')
@section('content')
<h1 class="pf-display mb-2 text-2xl font-bold text-white">Confirm your password</h1>
<p class="mb-5 text-sm text-slate-400">Sensitive area — confirm it is really you.</p>
<form method="POST" action="{{ route('password.confirm') }}">@csrf
  <x-field label="Password"><input name="password" type="password" required autocomplete="current-password" class="pf-input"></x-field>
  <x-btn class="w-full justify-center">Confirm</x-btn>
</form>
@endsection
