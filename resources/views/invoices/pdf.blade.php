<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Invoice {{ $invoice->number }}</title>
<style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;color:#111}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:6px;text-align:left}th{background:#f3f4f6}.totals{width:300px;margin-left:auto}.header{margin-bottom:20px}</style>
</head><body>
<div class="header">
<h1>{{ config('branding.company_name') }}</h1>
<p>{{ config('branding.company_address') }}<br>{{ config('branding.company_email') }} · {{ config('branding.company_phone') }}</p>
<h2>Invoice {{ $invoice->number }}</h2>
<p>Bill to: <strong>{{ $invoice->customer?->company_name }}</strong><br>
{{ $invoice->customer?->address }} {{ $invoice->customer?->city }} {{ $invoice->customer?->postcode }}<br>
Issued: {{ $invoice->issue_date?->format('d M Y') }} · Due: {{ $invoice->due_date?->format('d M Y') }} · Status: {{ ucfirst($invoice->status) }}</p>
</div>
<table><thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead><tbody>
@foreach($invoice->items as $it)<tr><td>{{ $it->description }}</td><td>{{ $it->quantity }}</td><td>{{ money($it->unit_price,$invoice->currency) }}</td><td>{{ money($it->line_total,$invoice->currency) }}</td></tr>@endforeach
</tbody></table>
<table class="totals">
<tr><td>Subtotal</td><td>{{ money($invoice->subtotal,$invoice->currency) }}</td></tr>
<tr><td>Discount ({{ $invoice->discount_percent }}%)</td><td>{{ money($invoice->discount_total,$invoice->currency) }}</td></tr>
<tr><td>Tax ({{ $invoice->tax_percent }}%)</td><td>{{ money($invoice->tax_total,$invoice->currency) }}</td></tr>
<tr><td><strong>Total</strong></td><td><strong>{{ money($invoice->total,$invoice->currency) }}</strong></td></tr>
<tr><td>Paid</td><td>{{ money($invoice->amount_paid,$invoice->currency) }}</td></tr>
<tr><td><strong>Balance due</strong></td><td><strong>{{ money($invoice->balance(),$invoice->currency) }}</strong></td></tr>
</table>
@if($invoice->notes)<p>Notes: {{ $invoice->notes }}</p>@endif
</body></html>
