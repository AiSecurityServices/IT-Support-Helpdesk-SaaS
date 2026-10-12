@extends('layouts.marketing')
@section('title', 'Pricing that scales with your team')
@section('content')
@include('marketing.partials.pagehead', ['eyebrow' => 'Pricing', 'slot' => 'Priced like an instrument, not a lottery.', 'lede' => 'Every plan starts with a 14-day free trial. Monthly or annual billing, limits enforced in-app, no sales call required.'])
<section class="bg-white pb-20 dark:bg-slate-950">
  <div class="mx-auto max-w-7xl px-4 sm:px-6">
    <div class="grid items-stretch gap-5 lg:grid-cols-3">
      @foreach($plans as $plan)
      <div class="pf-card pf-card-pad flex flex-col {{ $loop->index === 1 ? '!border-cyan-600/60' : '' }}">
        @if($loop->index === 1)<p class="pf-mono mb-2 text-[0.66rem] uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300">Most deployed</p>@endif
        <h2 class="pf-display text-xl font-bold">{{ $plan->name }}</h2>
        <p class="pf-display mt-3 text-5xl font-bold">£{{ number_format($plan->price_monthly, 0) }}<span class="text-base font-medium text-slate-500">/mo</span></p>
        <p class="mt-1 text-sm text-slate-500">£{{ number_format($plan->price_annual, 0) }}/yr · billed {{ $plan->currency }}</p>
        <ul class="mt-5 flex-1 space-y-2.5 text-sm">
          <li class="flex gap-2"><x-icon name="check" /> <strong>{{ $plan->technician_seats }}</strong>&nbsp;technician seats</li>
          <li class="flex gap-2"><x-icon name="check" /> <strong>{{ number_format($plan->ticket_limit_monthly) }}</strong>&nbsp;tickets / month</li>
          <li class="flex gap-2"><x-icon name="check" /> <strong>{{ number_format($plan->asset_limit) }}</strong>&nbsp;assets</li>
          @foreach($plan->features ?? [] as $f)<li class="flex gap-2"><x-icon name="check" /> {{ ucfirst(str_replace('_',' ',$f)) }}</li>@endforeach
        </ul>
        <a href="{{ route('register') }}" class="pf-btn {{ $loop->index === 1 ? 'pf-btn-primary' : 'pf-btn-ghost' }} mt-6 justify-center">Start free trial</a>
      </div>
      @endforeach
    </div>
    <div class="pf-card pf-card-flat pf-card-pad mt-8 grid gap-6 md:grid-cols-3">
      <div><h3 class="pf-display font-semibold">Who is Starter for?</h3><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Solo technicians and small teams running their first real queue.</p></div>
      <div><h3 class="pf-display font-semibold">Who is Growth for?</h3><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">MSPs and internal IT teams with SLAs to keep and clients to impress.</p></div>
      <div><h3 class="pf-display font-semibold">Who is Scale for?</h3><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">High-volume operations needing automation, API access and headroom.</p></div>
    </div>
    <p class="mt-6 text-center text-sm text-slate-500">Prices exclude VAT where applicable. These are the live subscription plans — the billing engine charges exactly what you see here.</p>
  </div>
</section>
@endsection
