@extends('layouts.marketing')
@section('title', 'Privacy notice')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'Legal', 'slot' => 'Privacy notice.', 'lede' => 'What we hold, why we hold it, and how you take it back.'])
<section class="bg-white pb-20 dark:bg-slate-950">
  <div class="pf-measure mx-auto px-4 sm:px-6">
    <p class="text-sm text-slate-500">Last updated: {{ date('F Y') }}. {{ config('branding.company_name') }} processes account, ticket, asset and billing data to operate {{ config('branding.product_name') }}. Each tenant workspace is isolated; customer data is never mixed between workspaces.</p>
    <div class="mt-8 space-y-7 text-[0.95rem] leading-relaxed text-slate-700 dark:text-slate-300">
      <div><h2 class="pf-display text-lg font-semibold text-slate-900 dark:text-white">Data we hold</h2><p class="mt-1">Contact details, support correspondence, device records, invoices and audit events — only what the service needs to function.</p></div>
      <div><h2 class="pf-display text-lg font-semibold text-slate-900 dark:text-white">Your rights</h2><p class="mt-1">Export or delete your personal data at any time via {{ config('branding.support_email') }}. Deletion respects lawful retention, for example invoicing records.</p></div>
      <div><h2 class="pf-display text-lg font-semibold text-slate-900 dark:text-white">Retention</h2><p class="mt-1">Workspaces removed on request after the documented retention window. Backups age out on a fixed schedule — never kept indefinitely.</p></div>
      <p class="border-l-2 border-cyan-500 pl-4 text-xs text-slate-500">This notice describes product behaviour; it is not legal advice and does not certify compliance.</p>
    </div>
  </div>
</section>
@endsection
