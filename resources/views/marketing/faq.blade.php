@extends('layouts.marketing')
@section('title', 'Answers before you ask')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'FAQ', 'slot' => 'Answers before you ask.', 'lede' => 'Trials, seats, migrations, portals and billing — directly answered.'])
<section class="bg-white pb-20 dark:bg-slate-950">
  <div class="mx-auto max-w-3xl px-4 sm:px-6">
    <div class="space-y-3">
    @foreach([
    ['Is there a free trial?','Yes — 14 days on any plan, no credit card required. Your workspace, your real tickets.'],
    ['Can I manage multiple clients?','Yes. Each client is a customer organisation with isolated tickets, assets, SLAs and invoices inside your workspace.'],
    ['Is my data isolated from other companies?','Yes. Every workspace is strictly tenant-isolated server-side, backed by automated attack tests that run on every release.'],
    ['Can customers raise tickets themselves?','Yes, through the branded customer portal with public replies, device views and invoice downloads. Internal notes stay internal.'],
    ['How do SLAs work?','You configure targets, working hours and holidays; the engine computes deadlines, sends warnings and breaches, and escalates automatically.'],
    ['Can I take payments online?','Yes — connect Stripe for checkout and webhooks, or record manual and bank payments without any provider.'],
    ['Can I rebrand the platform?','Workspace branding (name, logo, accent, support contact) is configurable per tenant in Settings.'],
    ['What happens to my data if I leave?','Export everything to CSV or JSON at any time. After cancellation your data is retained for the documented window, then removed on request.'],
    ] as $f)
    <details class="pf-card pf-card-flat pf-card-pad group">
      <summary class="pf-display cursor-pointer list-none font-semibold [&::-webkit-details-marker]:hidden"><span class="mr-2 inline-block text-cyan-700 transition group-open:rotate-45 dark:text-cyan-300">+</span>{{ $f[0] }}</summary>
      <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $f[1] }}</p>
    </details>
    @endforeach
    </div>
  </div>
</section>
@endsection
