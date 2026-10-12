@extends('layouts.app')
@section('title','Subscription')
@section('heading','Subscription & plan')
@section('content')
@if($subscription)
<x-card title="Current plan: {{ $subscription->plan?->name ?? '—' }}">
<p class="text-sm">Status: <x-badge :color="$subscription->isActive()?'green':'red'">{{ ucfirst($subscription->status) }}</x-badge> · Billed {{ $subscription->interval }}</p>
@if($subscription->onTrial())<p class="text-sm">Trial ends {{ $subscription->trial_ends_at->format('d M Y') }}.</p>@endif
@if($subscription->current_period_end)<p class="text-sm">Current period ends {{ $subscription->current_period_end->format('d M Y') }}.</p>@endif
@if($subscription->cancel_at_period_end)<p class="text-sm text-amber-700">Cancellation scheduled — access continues until {{ $subscription->current_period_end?->format('d M Y') }}.</p>@endif
<div class="mt-2 grid gap-2 text-sm md:grid-cols-3">
<div>Technicians: <strong>{{ $usage['technicians'] }}</strong> / {{ $subscription->plan?->technician_seats ?? '—' }}</div>
<div>Assets: <strong>{{ $usage['assets'] }}</strong> / {{ $subscription->plan?->asset_limit ?? '—' }}</div>
<div>Tickets this month: <strong>{{ $usage['tickets_month'] }}</strong> / {{ $subscription->plan?->ticket_limit_monthly ?? '—' }}</div>
</div>
<div class="mt-3 flex gap-2">
@if($stripe_configured)<a href="{{ route('billing.portal') }}" class="rounded-lg border px-3 py-1 text-sm">Manage payment method →</a>@endif
@if(!$subscription->cancel_at_period_end)
<form method="POST" action="{{ route('billing.cancel') }}" class="flex items-center gap-2" onsubmit="return confirm('Cancel subscription? Data is retained 90 days.')">@csrf<label class="flex items-center gap-1 text-sm"><input type="checkbox" name="at_period_end" value="1" checked> At period end</label><button class="rounded-lg border border-red-300 px-3 py-1 text-sm text-red-600">Cancel subscription</button></form>
@endif
</div>
</x-card>
@endif
<h2 class="mb-2 mt-6 text-lg font-semibold">Plans</h2>
<div class="grid gap-4 md:grid-cols-3">
@foreach($plans as $plan)
<x-card>
<h3 class="font-semibold">{{ $plan->name }}</h3>
<p class="text-2xl font-bold">£{{ number_format($plan->price_monthly,0) }}<span class="text-sm font-normal">/mo</span> <span class="text-sm font-normal text-slate-500">· £{{ number_format($plan->price_annual,0) }}/yr</span></p>
<ul class="mt-2 text-sm text-slate-600 dark:text-slate-300">
<li>{{ $plan->technician_seats }} seats · {{ number_format($plan->asset_limit) }} assets</li>
@foreach($plan->features ?? [] as $f)<li>✓ {{ ucfirst(str_replace('_',' ',$f)) }}</li>@endforeach
</ul>
<div class="mt-3 flex gap-2">
<form method="POST" action="{{ route('billing.checkout',['plan'=>$plan,'interval'=>'monthly']) }}">@csrf<button class="rounded-lg bg-blue-600 px-3 py-1 text-sm text-white">Monthly</button></form>
<form method="POST" action="{{ route('billing.checkout',['plan'=>$plan,'interval'=>'annual']) }}">@csrf<button class="rounded-lg border px-3 py-1 text-sm">Annual</button></form>
</div>
@if(!$stripe_configured)<p class="mt-1 text-xs text-slate-500">Online payment not configured — plan changes are recorded for manual invoicing.</p>@endif
</x-card>
@endforeach
</div>
<x-card title="Billing history" class="mt-4">
@forelse($events as $e)<div class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span>{{ str_replace('_',' ',$e->type) }}</span><span class="text-slate-500">{{ $e->amount ? money($e->amount,$e->currency) : '' }} {{ $e->created_at->format('d M Y') }}</span></div>
@empty<p class="text-sm text-slate-500">No billing events yet.</p>@endforelse
</x-card>
<p class="mt-3 text-sm"><a class="text-blue-600" href="{{ route('invoices.index') }}">Looking for customer service invoices? They live under Invoices →</a></p>
@endsection
