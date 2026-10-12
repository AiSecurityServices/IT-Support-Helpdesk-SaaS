@extends('layouts.admin')
@section('title','Plans')
@section('heading','Subscription plans')
@section('content')
<div class="grid gap-4 lg:grid-cols-3">
@foreach($plans as $plan)
<x-card>
<h3 class="font-semibold">{{ $plan->name }} ({{ $plan->slug }})</h3>
<form method="POST" action="{{ route('admin.plans.update',$plan) }}" class="mt-2 grid grid-cols-2 gap-2 text-sm">@csrf @method('PUT')
<x-field label="Monthly £"><input type="number" step="0.01" name="price_monthly" value="{{ $plan->price_monthly }}" required class="pf-input"></x-field>
<x-field label="Annual £"><input type="number" step="0.01" name="price_annual" value="{{ $plan->price_annual }}" required class="pf-input"></x-field>
<x-field label="Currency"><input name="currency" value="{{ $plan->currency }}" maxlength="3" class="pf-input"></x-field>
<x-field label="Seats"><input type="number" name="technician_seats" value="{{ $plan->technician_seats }}" required class="pf-input"></x-field>
<x-field label="Assets"><input type="number" name="asset_limit" value="{{ $plan->asset_limit }}" required class="pf-input"></x-field>
<x-field label="Tickets/mo"><input type="number" name="ticket_limit_monthly" value="{{ $plan->ticket_limit_monthly }}" required class="pf-input"></x-field>
<x-field label="Storage MB"><input type="number" name="storage_mb" value="{{ $plan->storage_mb }}" required class="pf-input"></x-field>
<x-field label="Name"><input name="name" value="{{ $plan->name }}" required class="pf-input"></x-field>
<label class="flex items-center gap-1"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)> Active</label>
<label class="flex items-center gap-1"><input type="checkbox" name="trial_eligible" value="1" @checked($plan->trial_eligible)> Trial eligible</label>
<span class="col-span-2"><x-btn variant="secondary">Save plan</x-btn></span>
</form>
</x-card>
@endforeach
</div>
@endsection

