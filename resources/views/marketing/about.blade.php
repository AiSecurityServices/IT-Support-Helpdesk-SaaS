@extends('layouts.marketing')
@section('title', 'About the studio behind the instrument')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'About', 'slot' => 'Built by people who have carried the pager.', 'lede' => config('branding.product_name').' is made by '.config('branding.company_name').' — a team of support engineers who got tired of fighting their own tooling.'])
<section class="pf-section bg-white dark:bg-slate-950">
  <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-5">
    <div class="pf-reveal lg:col-span-3">
      <h2 class="pf-display text-2xl font-bold">Boring reliability, on purpose.</h2>
      <div class="mt-4 space-y-4 leading-relaxed text-slate-600 dark:text-slate-300">
        <p>We measure ourselves the way a service desk measures itself: tenant isolation that is tested rather than asserted, SLA mathematics that respects working hours and holidays, money handled with decimal-safe arithmetic, and exports that never leak across customers.</p>
        <p>Every feature ships with automated tests and an audit trail, because the people who answer for outages at 3 a.m. deserve tools that answer for themselves.</p>
      </div>
      <dl class="mt-8 grid grid-cols-3 gap-6 border-t border-slate-200 pt-6 dark:border-slate-800">
        <div><dt class="pf-mono text-[0.66rem] uppercase tracking-[0.18em] text-slate-500">Test suite</dt><dd class="pf-display mt-1 text-2xl font-bold">148 checks</dd></div>
        <div><dt class="pf-mono text-[0.66rem] uppercase tracking-[0.18em] text-slate-500">Isolation</dt><dd class="pf-display mt-1 text-2xl font-bold">Enforced</dd></div>
        <div><dt class="pf-mono text-[0.66rem] uppercase tracking-[0.18em] text-slate-500">Uptime theatre</dt><dd class="pf-display mt-1 text-2xl font-bold">Zero</dd></div>
      </dl>
    </div>
    <aside class="pf-card pf-card-flat pf-card-pad pf-reveal lg:col-span-2">
      <h2 class="pf-card-title">Contact</h2>
      <p class="text-sm text-slate-600 dark:text-slate-300">{{ config('branding.company_address') }}</p>
      <p class="mt-2 text-sm"><a class="font-semibold text-cyan-700 dark:text-cyan-300" href="mailto:{{ config('branding.support_email') }}">{{ config('branding.support_email') }}</a><br><span class="text-slate-500">{{ config('branding.company_phone') }}</span></p>
      <p class="mt-4"><a href="{{ route('contact') }}" class="pf-btn pf-btn-primary pf-btn-sm">Send a message</a></p>
    </aside>
  </div>
</section>
@endsection
