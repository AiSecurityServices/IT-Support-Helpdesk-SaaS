@extends('layouts.app')
@section('title','Mail templates')
@section('heading','Mail templates & delivery test')
@section('content')
<x-card title="Template preview (test mail)">
<div class="rounded border p-3 text-sm dark:border-slate-700">{!! $html !!}</div>
<p class="mt-1 text-xs text-slate-500">Business notifications reuse these templates and tenant branding. Plain-text parts are generated automatically by the mailer.</p>
</x-card>
<x-card title="Send a safe test email" class="mt-4">
<form method="POST" action="{{ route('settings.mail.send') }}" class="flex items-center gap-2 text-sm">@csrf
<input type="email" name="email" value="{{ auth()->user()->email }}" aria-label="Recipient email" class="pf-input">
<x-btn variant="secondary">Send test</x-btn>
</form>
<p class="mt-1 text-xs text-slate-500">Fixed subject/body — no business data leaves the workspace. Failures surface here and in delivery monitoring.</p>
</x-card>
@endsection

