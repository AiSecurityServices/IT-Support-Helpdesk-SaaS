@extends('layouts.app')
@section('title', isset($asset) ? 'Edit asset' : 'New asset')
@section('heading', isset($asset) ? 'Edit '.$asset->name : 'New asset')
@section('content')
<x-card>
@php $a = $asset ?? null; @endphp
<form method="POST" action="{{ $a ? route('assets.update',$a) : route('assets.store') }}">@csrf @if($a) @method('PUT') @endif
<div class="grid gap-4 md:grid-cols-3">
<x-field label="Name *"><input name="name" value="{{ old('name',$a->name ?? '') }}" required class="pf-input"></x-field>
<x-field label="Type"><select name="type" class="pf-select">@foreach(['computer','laptop','server','printer','network','mobile','software','other'] as $t)<option @selected(old('type',$a->type ?? 'computer')===$t)>{{ $t }}</option>@endforeach</select></x-field>
<x-field label="Status"><select name="status" class="pf-select">@foreach(config('supportflow.asset_statuses') as $k=>$v)<option value="{{ $k }}" @selected(old('status',$a->status ?? 'in_stock')===$k)>{{ $v }}</option>@endforeach</select></x-field>
<x-field label="Manufacturer"><input name="manufacturer" value="{{ old('manufacturer',$a->manufacturer ?? '') }}" class="pf-input"></x-field>
<x-field label="Model"><input name="model" value="{{ old('model',$a->model ?? '') }}" class="pf-input"></x-field>
<x-field label="Serial number"><input name="serial_number" value="{{ old('serial_number',$a->serial_number ?? '') }}" class="pf-input"></x-field>
<x-field label="Computer name"><input name="computer_name" value="{{ old('computer_name',$a->computer_name ?? '') }}" class="pf-input"></x-field>
<x-field label="Operating system"><input name="operating_system" value="{{ old('operating_system',$a->operating_system ?? '') }}" class="pf-input"></x-field>
<x-field label="IP address"><input name="ip_address" value="{{ old('ip_address',$a->ip_address ?? '') }}" class="pf-input"></x-field>
<x-field label="Customer"><select name="customer_id" class="pf-select"><option value="">—</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id',$a->customer_id ?? '')==$c->id)>{{ $c->company_name }}</option>@endforeach</select></x-field>
<x-field label="Assigned user"><select name="assigned_to_user_id" class="pf-select"><option value="">—</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('assigned_to_user_id',$a->assigned_to_user_id ?? '')==$u->id)>{{ $u->name }}</option>@endforeach</select></x-field>
<x-field label="Purchase date"><input type="date" name="purchase_date" value="{{ old('purchase_date',$a?->purchase_date?->format('Y-m-d')) }}" class="pf-input"></x-field>
<x-field label="Supplier"><input name="supplier" value="{{ old('supplier',$a->supplier ?? '') }}" class="pf-input"></x-field>
<x-field label="Purchase cost"><input type="number" step="0.01" name="purchase_cost" value="{{ old('purchase_cost',$a->purchase_cost ?? '') }}" class="pf-input"></x-field>
<x-field label="Warranty expires"><input type="date" name="warranty_expires_at" value="{{ old('warranty_expires_at',$a?->warranty_expires_at?->format('Y-m-d')) }}" class="pf-input"></x-field>
</div>
<x-field label="Notes"><textarea name="notes" rows="3" class="pf-textarea">{{ old('notes',$a->notes ?? '') }}</textarea></x-field>
<x-btn>{{ $a ? 'Save changes' : 'Create asset' }}</x-btn>
</form>
</x-card>
@endsection


