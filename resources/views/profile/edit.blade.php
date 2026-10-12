@extends('layouts.app')
@section('title','Profile')
@section('heading','Your profile')
@section('content')
<div class="grid gap-4 md:grid-cols-2">
<x-card title="Profile details">
<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PATCH')
  <div class="mb-3 flex items-center gap-3"><img src="{{ $user->avatarUrl() }}" alt="Avatar" class="h-14 w-14 rounded-full"></div>
  <x-field label="Name"><input name="name" value="{{ old('name',$user->name) }}" required class="pf-input"></x-field>
  <x-field label="Email"><input value="{{ $user->email }}" disabled class="pf-input"></x-field>
  <x-field label="Phone"><input name="phone" value="{{ old('phone',$user->phone) }}" class="pf-input"></x-field>
  <x-field label="Job title"><input name="job_title" value="{{ old('job_title',$user->job_title) }}" class="pf-input"></x-field>
  <x-field label="Language"><select name="locale" class="pf-select"><option value="">Workspace default</option><option value="en" @selected(old('locale',$user->locale)==='en')>English (UK)</option><option value="es" @selected(old('locale',$user->locale)==='es')>Español</option></select></x-field>
  <x-field label="Avatar" hint="JPG/PNG/WebP, max 2MB."><input type="file" name="avatar" accept="image/*"></x-field>
  <x-btn>Save profile</x-btn>
</form>
</x-card>
<x-card title="Change password">
<form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
  <x-field label="Current password"><input type="password" name="current_password" required class="pf-input"></x-field>
  <x-field label="New password" hint="Min 10 chars, mixed case + numbers."><input type="password" name="password" required class="pf-input"></x-field>
  <x-field label="Confirm new password"><input type="password" name="password_confirmation" required class="pf-input"></x-field>
  <x-btn>Change password</x-btn>
</form>
</x-card>
<x-card title="Two-factor authentication">
@if($user->totp_enabled_at)
<p class="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400"><x-icon name="check" /> Enabled since {{ $user->totp_enabled_at->format('d M Y') }}.</p>
<form method="POST" action="{{ route('totp.disable') }}" class="mt-2" onsubmit="return confirm('Disable two-factor authentication?')">@csrf @method('DELETE')
<x-field label="Confirm with password"><input type="password" name="password" required class="pf-input"></x-field>
<x-btn variant="danger">Disable 2FA</x-btn>
</form>
@else
<p class="text-sm text-slate-500">Add TOTP-based two-factor authentication with any authenticator app.</p>
<a href="{{ route('totp.setup') }}" class="mt-2 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm text-white">Set up 2FA</a>
@endif
</x-card>
</div>
@endsection


