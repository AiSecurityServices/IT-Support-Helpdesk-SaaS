@extends('layouts.marketing')
@section('title', 'Contact an engineer')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'Contact', 'slot' => 'Talk to someone who runs helpdesks.', 'lede' => 'Migration questions, workflow advice, or a security review pack — answered by engineers, not a drip campaign.'])
<section class="bg-white pb-20 dark:bg-slate-950">
  <div class="mx-auto grid max-w-5xl gap-8 px-4 sm:px-6 lg:grid-cols-5">
    <div class="pf-card pf-card-pad lg:col-span-3">
      @if(session('success'))<div class="pf-alert pf-alert-ok mb-4" role="status">{{ session('success') }}</div>@endif
      <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">@csrf
        <div class="grid gap-4 sm:grid-cols-2">
          <x-field label="Name"><input name="name" required autocomplete="name" class="pf-input"></x-field>
          <x-field label="Email"><input name="email" type="email" required autocomplete="email" class="pf-input"></x-field>
        </div>
        <x-field label="Company"><input name="company" autocomplete="organization" class="pf-input"></x-field>
        <x-field label="Message"><textarea name="message" rows="5" required class="pf-input"></textarea></x-field>
        <x-btn>Send message</x-btn>
      </form>
    </div>
    <aside class="lg:col-span-2">
      <div class="pf-card pf-card-flat pf-card-pad">
        <h2 class="pf-card-title">Studio desk</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">{{ config('branding.company_address') }}</p>
        <p class="mt-2 text-sm"><a class="font-semibold text-cyan-700 dark:text-cyan-300" href="mailto:{{ config('branding.support_email') }}">{{ config('branding.support_email') }}</a></p>
        <p class="text-sm text-slate-500">{{ config('branding.company_phone') }}</p>
      </div>
      <div class="pf-card pf-card-flat pf-card-pad mt-5">
        <p class="pf-mono text-[0.66rem] uppercase tracking-[0.2em] text-slate-500">Response standard</p>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">One business day. If it is urgent, say so in the first line.</p>
      </div>
    </aside>
  </div>
</section>
@endsection
