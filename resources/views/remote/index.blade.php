@extends('layouts.app')
@section('title','Remote support')
@section('heading','Remote support sessions')
@section('content')
<div class="grid gap-4 lg:grid-cols-3">
<div class="lg:col-span-2">
<x-card title="Sessions">
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th class="p-2">Ticket</th><th class="p-2">Provider</th><th class="p-2">Technician</th><th class="p-2">Status</th><th class="p-2">Consent</th><th></th></tr></thead>
<tbody>
@forelse($sessions as $s)
<tr class="border-b dark:border-slate-700">
<td class="p-2">@if($s->ticket)<a class="text-blue-600" href="{{ route('tickets.show',$s->ticket) }}">{{ $s->ticket->number }}</a>@else — @endif</td>
<td class="p-2">{{ ucfirst($s->provider) }}<div class="text-xs text-slate-500">{{ \Str::limit($s->purpose,50) }}</div></td>
<td class="p-2">{{ $s->technician?->name }}</td>
<td class="p-2"><x-badge>{{ ucfirst($s->status) }}</x-badge></td>
<td class="p-2">@if($s->consent_confirmed)<x-badge color="green">Consent confirmed</x-badge>@else — @endif</td>
<td class="p-2 text-right">
@if(!$s->consent_confirmed)<form method="POST" action="{{ route('remote.consent',$s) }}" class="inline">@csrf<input type="hidden" name="consent_confirmed" value="1"><button class="text-xs text-blue-600">Record consent</button></form>@endif
@if(!in_array($s->status,['completed','cancelled']))<form method="POST" action="{{ route('remote.complete',$s) }}" class="inline">@csrf<button class="ml-2 text-xs text-green-600">Complete</button></form>@endif
</td>
</tr>
@empty<tr><td colspan="6" class="p-6 text-center text-slate-500">No sessions yet.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-2">{{ $sessions->links() }}</div>
</x-card>
</div>
<x-card title="New session link">
<p class="mb-2 text-xs text-slate-500">Save the approved tool's join link here. Session links are sensitive — share only via the ticket, never in reports.</p>
<form method="POST" action="{{ route('remote.store') }}" class="space-y-2 text-sm">@csrf
<x-field label="Ticket ID"><input name="ticket_id" class="pf-input"></x-field>
<x-field label="Provider"><select name="provider" class="pf-select"><option value="teamviewer">TeamViewer</option><option value="anydesk">AnyDesk</option><option value="rdp">Remote Desktop</option><option value="teams">Teams</option><option value="zoom">Zoom</option><option value="other">Other</option></select></x-field>
<x-field label="Join URL"><input name="join_url" type="url" class="pf-input"></x-field>
<x-field label="Purpose"><textarea name="purpose" rows="2" class="pf-textarea"></textarea></x-field>
<x-field label="Customer instructions"><textarea name="customer_instructions" rows="2" class="pf-textarea"></textarea></x-field>
<x-btn>Save session</x-btn>
</form>
</x-card>
</div>
@endsection

