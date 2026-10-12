@extends('layouts.marketing')
@section('title', 'Security model: tenant isolation, access control, audit')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'Security', 'slot' => 'Trust, stated plainly.', 'lede' => 'What protects your data, how it is verified, and what we do not claim. No borrowed badges, no fine-print surprises.'])
<section class="pf-section bg-white dark:bg-slate-950">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="grid gap-5 md:grid-cols-2">
      @foreach([
        ['shield','Tenant isolation, enforced and tested','Every database query is scoped to your workspace at the framework level. Automated attack tests attempt cross-tenant access through URLs, APIs, exports and downloads on every release.'],
        ['lock','Least-privilege access','Owners, managers, technicians and customers each receive the minimum capability their job needs — verified by permission tests, not by hidden menus.'],
        ['eye','Audit history you can rely on','Status changes, assignments, payments, logins and exports leave a permanent, tamper-evident trail your auditors can follow.'],
        ['users','Secure customer access','Isolated portals, authorised downloads, email verification and optional two-factor authentication for staff and customers.'],
        ['bell','Operational honesty','Failed jobs, delivery failures and breach alerts surface in the platform health screen instead of failing silently.'],
        ['file','Your data stays yours','CSV and JSON exports on demand, documented retention, and deletion workflows that respect financial record-keeping duties.'],
      ] as $c)
      <div class="pf-card pf-card-flat pf-card-pad"><span class="text-cyan-700 dark:text-cyan-300"><x-icon name="{{ $c[0] }}" class="pf-icon-lg" /></span><h2 class="pf-display mt-3 font-semibold">{{ $c[1] }}</h2><p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $c[2] }}</p></div>
      @endforeach
    </div>
    <div class="pf-card pf-card-pad mt-8 border-l-2 !border-l-cyan-500">
      <h2 class="pf-display font-semibold">What we do not claim</h2>
      <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">SupportFlow Cloud is not presented as OWASP-certified, GDPR-certified, or independently penetration-tested unless a dated assessment report says so. Security documentation describes implemented controls and their test evidence — ask us for the current reports before you sign anything that depends on them.</p>
      <p class="mt-4 flex flex-wrap gap-3"><a href="{{ route('contact') }}" class="pf-btn pf-btn-primary pf-btn-sm">Request security documentation</a><a href="{{ route('privacy') }}" class="pf-btn pf-btn-ghost pf-btn-sm">Privacy notice</a></p>
    </div>
  </div>
</section>
@endsection
