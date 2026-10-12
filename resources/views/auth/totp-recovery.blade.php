@extends('layouts.guest')
@section('title','Recovery codes')
@section('content')
<h1 class="mb-2 text-xl font-semibold dark:text-white">Save your recovery codes</h1>
<p class="mb-3 text-sm text-slate-500">Shown once. Each code works a single time if you lose your authenticator.</p>
@if(! empty($codes))
<ul class="mb-4 grid grid-cols-2 gap-1 font-mono text-sm">
@foreach($codes as $c)<li class="rounded bg-slate-100 px-2 py-1 dark:bg-slate-700">{{ $c }}</li>@endforeach
</ul>
@else
<p class="mb-4 text-sm text-slate-500">Codes were already displayed. Regenerate new ones from your profile to see them again.</p>
@endif
<a href="{{ route('profile.edit') }}" class="block rounded-lg bg-blue-600 px-4 py-2 text-center text-white">Continue to profile</a>
@endsection
