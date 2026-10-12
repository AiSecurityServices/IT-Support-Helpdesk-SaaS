@extends('layouts.app')
@section('title', isset($customer) ? 'Edit customer' : 'New customer')
@section('heading', isset($customer) ? 'Edit '.$customer->company_name : 'New customer')
@section('content')
<x-card>
@php $c = $customer ?? null; @endphp
<form method="POST" action="{{ $c ? route('customers.update',$c) : route('customers.store') }}">@csrf @if($c) @method('PUT') @endif
<div class="grid gap-4 md:grid-cols-2">
<x-field label="Company name *"><input name="company_name" value="{{ old('company_name',$c->company_name ?? '') }}" required class="pf-input"></x-field>
<x-field label="Account code"><input name="account_code" value="{{ old('account_code',$c->account_code ?? '') }}" class="pf-input"></x-field>
<x-field label="Email"><input name="email" type="email" value="{{ old('email',$c->email ?? '') }}" class="pf-input"></x-field>
<x-field label="Phone"><input name="phone" value="{{ old('phone',$c->phone ?? '') }}" class="pf-input"></x-field>
<x-field label="Website"><input name="website" value="{{ old('website',$c->website ?? '') }}" class="pf-input"></x-field>
<x-field label="Account owner"><select name="owner_id" class="pf-select"><option value="">—</option>@foreach($owners as $o)<option value="{{ $o->id }}" @selected(old('owner_id',$c->owner_id ?? '')==$o->id)>{{ $o->name }}</option>@endforeach</select></x-field>
</div>
<x-field label="Address"><textarea name="address" rows="2" class="pf-textarea">{{ old('address',$c->address ?? '') }}</textarea></x-field>
<div class="grid gap-4 md:grid-cols-3">
<x-field label="City"><input name="city" value="{{ old('city',$c->city ?? '') }}" class="pf-input"></x-field>
<x-field label="Postcode"><input name="postcode" value="{{ old('postcode',$c->postcode ?? '') }}" class="pf-input"></x-field>
<x-field label="Country"><input name="country" value="{{ old('country',$c->country ?? '') }}" class="pf-input"></x-field>
</div>
<div class="grid gap-4 md:grid-cols-2">
<x-field label="SLA policy"><select name="sla_policy_id" class="pf-select"><option value="">Tenant default</option>@foreach($policies as $p)<option value="{{ $p->id }}" @selected(old('sla_policy_id',$c->sla_policy_id ?? '')==$p->id)>{{ $p->name }}</option>@endforeach</select></x-field>
</div>
<x-field label="Internal notes"><textarea name="notes" rows="3" class="pf-textarea">{{ old('notes',$c->notes ?? '') }}</textarea></x-field>
<x-btn>{{ $c ? 'Save changes' : 'Create customer' }}</x-btn>
</form>
</x-card>
@endsection


