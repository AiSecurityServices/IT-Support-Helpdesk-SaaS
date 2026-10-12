@extends('layouts.app')
@section('title','New invoice')
@section('heading','New invoice')
@section('content')
<x-card>
@if($fromTicket)<p class="mb-3 rounded bg-blue-50 p-2 text-sm text-blue-800">Pre-filled from billable time on {{ $fromTicket->number }}.</p>@endif
<form method="POST" action="{{ route('invoices.store') }}">@csrf
<div class="grid gap-4 md:grid-cols-3">
<x-field label="Customer *"><select name="customer_id" required class="pf-select"><option value="">—</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id',$fromTicket?->customer_id)==$c->id)>{{ $c->company_name }}</option>@endforeach</select></x-field>
<x-field label="Currency"><input name="currency" value="{{ old('currency',$defaults['currency']) }}" maxlength="3" class="pf-input"></x-field>
<x-field label="Payment terms (days)"><input type="number" name="payment_terms_days" value="{{ old('payment_terms_days',$defaults['payment_terms_days']) }}" class="pf-input"></x-field>
<x-field label="Discount %"><input type="number" step="0.01" name="discount_percent" value="{{ old('discount_percent',0) }}" class="pf-input"></x-field>
<x-field label="Tax/VAT %"><input type="number" step="0.01" name="tax_percent" value="{{ old('tax_percent',$defaults['tax_percent']) }}" class="pf-input"></x-field>
<x-field label="Issue date"><input type="date" name="issue_date" value="{{ old('issue_date',date('Y-m-d')) }}" class="pf-input"></x-field>
</div>
<h3 class="mb-2 mt-4 font-semibold">Line items</h3>
<div id="items" class="space-y-2">
@php $items = old('items', $suggestedItems ?: [['description'=>'','quantity'=>1,'unit_price'=>'']]); @endphp
@foreach($items as $i=>$it)
<div class="grid grid-cols-12 gap-2 text-sm">
<input name="items[{{ $i }}][description]" value="{{ $it['description'] ?? '' }}" placeholder="Description *" required class="pf-input">
<input type="number" step="0.01" name="items[{{ $i }}][quantity]" value="{{ $it['quantity'] ?? 1 }}" placeholder="Qty" required class="pf-input">
<input type="number" step="0.01" name="items[{{ $i }}][unit_price]" value="{{ $it['unit_price'] ?? '' }}" placeholder="Unit price" required class="pf-input">
@if(isset($it['ticket_id']))<input type="hidden" name="items[{{ $i }}][ticket_id]" value="{{ $it['ticket_id'] }}">@endif
@if(isset($it['time_entry_id']))<input type="hidden" name="items[{{ $i }}][time_entry_id]" value="{{ $it['time_entry_id'] }}">@endif
</div>
@endforeach
</div>
<x-field label="Notes"><textarea name="notes" rows="2" class="pf-textarea">{{ old('notes') }}</textarea></x-field>
<x-btn>Create draft invoice</x-btn>
</form>
</x-card>
@endsection

