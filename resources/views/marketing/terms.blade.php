@extends('layouts.marketing')
@section('title', 'Terms of service')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'Legal', 'slot' => 'Terms of service.', 'lede' => 'The short, readable version. The lawyers keep a longer one.'])
<section class="bg-white pb-20 dark:bg-slate-950">
  <div class="pf-measure mx-auto px-4 sm:px-6">
    <ol class="list-decimal space-y-4 pl-5 text-[0.95rem] leading-relaxed text-slate-700 dark:text-slate-300">
      <li><strong class="text-slate-900 dark:text-white">Service.</strong> {{ config('branding.company_name') }} provides {{ config('branding.product_name') }} as a subscription service, starting with a 14-day trial unless configured otherwise.</li>
      <li><strong class="text-slate-900 dark:text-white">Your data.</strong> You retain ownership of everything you enter. On cancellation your workspace is retained for 90 days, then deleted on request.</li>
      <li><strong class="text-slate-900 dark:text-white">Acceptable use.</strong> Do not attempt to access other tenants' data, probe security controls, or store payment-card data in the application.</li>
      <li><strong class="text-slate-900 dark:text-white">Billing.</strong> Subscription fees follow your plan interval. Service invoicing between you and your customers is your responsibility.</li>
      <li><strong class="text-slate-900 dark:text-white">Liability.</strong> The service is provided “as is” to the maximum extent permitted by law.</li>
    </ol>
    <p class="mt-6 text-sm">Questions: <a class="font-semibold text-cyan-700 dark:text-cyan-300" href="mailto:{{ config('branding.support_email') }}">{{ config('branding.support_email') }}</a></p>
  </div>
</section>
@endsection
