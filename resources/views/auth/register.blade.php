@extends('layouts.guest')
@section('title','Start free trial')
@section('content')
<p class="pf-mono mb-3 text-[0.66rem] uppercase tracking-[0.22em] text-cyan-300/80">{{ isset($invitation) ? 'Invitation' : 'New workspace' }}</p>
<h1 class="pf-display mb-1 text-2xl font-bold text-white">{{ isset($invitation) ? 'Accept your invitation' : 'Start your 14-day free trial' }}</h1>
<p class="mb-5 text-sm text-slate-400">{{ isset($invitation) ? 'Join '.$invitation->tenant->name.' as '.$invitation->role.'.' : 'No credit card required.' }}</p>
<form method="POST" action="{{ route('register') }}">@csrf
  @if(isset($invitation))<input type="hidden" name="invitation" value="{{ $invitation->token }}"><input type="hidden" name="company" value="{{ $invitation->tenant->name }}">@endif
  <x-field label="Full name"><input name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="pf-input"></x-field>
  <x-field label="Work email"><input name="email" type="email" value="{{ old('email', $invitation->email ?? '') }}" required autocomplete="email" class="pf-input"></x-field>
  @if(!isset($invitation))
  <x-field label="Company / workspace name" hint="A dedicated, isolated workspace is created for this company."><input name="company" value="{{ old('company') }}" required autocomplete="organization" class="pf-input"></x-field>
  @endif
  <x-field label="Password" hint="Minimum 10 characters, mixed case and numbers."><input name="password" type="password" required autocomplete="new-password" class="pf-input"></x-field>
  <x-field label="Confirm password"><input name="password_confirmation" type="password" required autocomplete="new-password" class="pf-input"></x-field>
  <x-btn class="w-full justify-center">{{ isset($invitation) ? 'Join workspace' : 'Create workspace' }}</x-btn>
  <p class="mt-4 text-center text-sm text-slate-400"><a class="text-cyan-300 hover:text-cyan-200" href="{{ route('login') }}">Already have an account? Sign in</a></p>
</form>
@endsection
