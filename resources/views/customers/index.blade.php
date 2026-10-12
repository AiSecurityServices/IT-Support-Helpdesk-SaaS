@extends('layouts.app')
@section('title','Customers')
@section('heading','Customers')
@section('content')
<div class="mb-3 flex gap-2">
  <form method="GET" class="flex gap-2 text-sm"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search companies…" aria-label="Search customers" class="pf-input"><x-btn variant="secondary">Search</x-btn></form>
  <span class="ml-auto flex gap-2">
    <a href="{{ route('customers.export') }}" class="rounded-lg border px-3 py-1 text-sm">Export CSV</a>
    <a href="{{ route('customers.create') }}" class="rounded-lg bg-blue-600 px-4 py-1 text-sm text-white">+ New customer</a>
  </span>
</div>
<x-card>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th class="p-2">Company</th><th class="p-2">Contact</th><th class="p-2">Email</th><th class="p-2">Owner</th><th class="p-2">Open tickets</th><th class="p-2">Status</th></tr></thead>
<tbody>
@forelse($customers as $c)
<tr class="border-b hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/50">
<td class="p-2"><a href="{{ route('customers.show',$c) }}" class="font-medium text-blue-600">{{ $c->company_name }}</a></td>
<td class="p-2">{{ $c->primaryContact?->fullName() ?? '—' }}</td>
<td class="p-2">{{ $c->email ?? '—' }}</td>
<td class="p-2">{{ $c->owner?->name ?? '—' }}</td>
<td class="p-2">{{ $c->tickets()->whereIn('status',\App\Models\Ticket::OPEN_STATUSES)->count() }}</td>
<td class="p-2"><x-badge :color="$c->status==='active'?'green':'slate'">{{ ucfirst($c->status) }}</x-badge></td>
</tr>
@empty<tr><td colspan="6" class="p-6 text-center text-slate-500">No customers yet.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-3">{{ $customers->links() }}</div>
</x-card>
<x-card title="Import from CSV" class="mt-4">
<form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data" class="flex items-center gap-2 text-sm">@csrf
<input type="file" name="file" accept=".csv,.txt" required aria-label="CSV file to import">
<x-btn variant="secondary">Import</x-btn>
<span class="text-xs text-slate-500">Columns: company, email, phone, city, country, address, postcode. Duplicates are skipped, never overwritten.</span>
</form>
</x-card>
@endsection


