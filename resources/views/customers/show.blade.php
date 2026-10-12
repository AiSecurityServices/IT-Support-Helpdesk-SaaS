@extends('layouts.app')
@section('title', $customer->company_name)
@section('heading', $customer->company_name)
@section('content')
<div class="mb-3 flex gap-2 text-sm">
  <x-badge :color="$customer->status==='active'?'green':'slate'">{{ ucfirst($customer->status) }}</x-badge>
  <span class="ml-auto flex gap-2">
    <a href="{{ route('customers.edit',$customer) }}" class="rounded-lg border px-3 py-1">Edit</a>
    @if($customer->trashed())
    <form method="POST" action="{{ route('customers.restore',$customer->id) }}">@csrf<button class="rounded-lg border px-3 py-1">Restore</button></form>
    @else
    <form method="POST" action="{{ route('customers.destroy',$customer) }}" onsubmit="return confirm('Archive this customer? History is retained.')">@csrf @method('DELETE')<button class="rounded-lg border border-red-300 px-3 py-1 text-red-600">Archive</button></form>
    @endif
  </span>
</div>
<div class="grid gap-4 lg:grid-cols-3">
<div class="space-y-4 lg:col-span-2">
  <x-card title="Contacts">
    @forelse($customer->contacts as $ct)
    <div class="flex items-center justify-between border-b py-2 text-sm last:border-0 dark:border-slate-700">
      <span><strong>{{ $ct->fullName() }}</strong> {{ $ct->job_title ? '· '.$ct->job_title : '' }}<br><span class="text-slate-500">{{ $ct->email }} {{ $ct->phone ? '· '.$ct->phone : '' }}</span>
      @if($ct->is_primary) <x-badge color="blue">Primary</x-badge>@endif</span>
      <form method="POST" action="{{ route('customers.contacts.destroy',[$customer,$ct]) }}" onsubmit="return confirm('Remove contact?')">@csrf @method('DELETE')<button class="text-xs text-red-600">Remove</button></form>
    </div>
    @empty<p class="text-sm text-slate-500">No contacts yet.</p>@endforelse
    <form method="POST" action="{{ route('customers.contacts.store',$customer) }}" class="mt-3 grid gap-2 border-t pt-3 text-sm md:grid-cols-3 dark:border-slate-700">@csrf
      <input name="first_name" placeholder="First name *" required class="pf-input">
      <input name="last_name" placeholder="Last name" class="pf-input">
      <input name="email" type="email" placeholder="Email" class="pf-input">
      <input name="phone" placeholder="Phone" class="pf-input">
      <input name="job_title" placeholder="Job title" class="pf-input">
      <label class="flex items-center gap-1"><input type="checkbox" name="is_primary" value="1"> Primary</label>
      <label class="flex items-center gap-1"><input type="checkbox" name="create_portal_user" value="1"> Create portal login</label>
      <span><x-btn variant="secondary">Add contact</x-btn></span>
    </form>
  </x-card>
  <x-card title="Recent tickets">
    @forelse($customer->tickets as $t)<a href="{{ route('tickets.show',$t) }}" class="flex justify-between border-b py-1 text-sm last:border-0 dark:border-slate-700"><span><strong>{{ $t->number }}</strong> {{ \Str::limit($t->subject,60) }}</span>@include('tickets.partials.badges')</a>
    @empty<p class="text-sm text-slate-500">No tickets yet.</p>@endforelse
  </x-card>
</div>
<div class="space-y-4">
  <x-card title="Details">
    <dl class="text-sm">
      <dt class="text-slate-500">Email</dt><dd>{{ $customer->email ?? '—' }}</dd>
      <dt class="mt-2 text-slate-500">Phone</dt><dd>{{ $customer->phone ?? '—' }}</dd>
      <dt class="mt-2 text-slate-500">Address</dt><dd>{{ $customer->address }} {{ $customer->city }} {{ $customer->postcode }} {{ $customer->country }}</dd>
      <dt class="mt-2 text-slate-500">Owner</dt><dd>{{ $customer->owner?->name ?? '—' }}</dd>
      <dt class="mt-2 text-slate-500">SLA policy</dt><dd>{{ $customer->slaPolicy?->name ?? 'Tenant default' }}</dd>
      <dt class="mt-2 text-slate-500">Notes</dt><dd class="whitespace-pre-wrap">{{ $customer->notes ?? '—' }}</dd>
    </dl>
  </x-card>
  <x-card title="Assets ({{ $customer->assets->count() }})">
    @foreach($customer->assets as $a)<div class="text-sm"><a class="text-blue-600" href="{{ route('assets.show',$a) }}">{{ $a->name }}</a> <span class="text-slate-500">{{ $a->status }}</span></div>@endforeach
  </x-card>
  <x-card title="Invoices">
    @foreach($customer->invoices as $i)<div class="flex justify-between text-sm"><a class="text-blue-600" href="{{ route('invoices.show',$i) }}">{{ $i->number }}</a><span>{{ money($i->total,$i->currency) }} · {{ $i->status }}</span></div>@endforeach
  </x-card>
</div>
</div>
@endsection

