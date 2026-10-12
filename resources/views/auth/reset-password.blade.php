@extends('layouts.guest')
@section('title','Set new password')
@section('content')
<h1 class="pf-display mb-5 text-2xl font-bold text-white">Choose a new password</h1>
<form method="POST" action="{{ route('password.update') }}">@csrf
  <input type="hidden" name="token" value="{{ $request->route('token') }}">
  <x-field label="Email"><input name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="email" class="pf-input"></x-field>
  <x-field label="New password"><input name="password" type="password" required autocomplete="new-password" class="pf-input"></x-field>
  <x-field label="Confirm password"><input name="password_confirmation" type="password" required autocomplete="new-password" class="pf-input"></x-field>
  <x-btn class="w-full justify-center">Reset password</x-btn>
</form>
@endsection
