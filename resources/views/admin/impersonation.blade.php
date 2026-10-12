@extends('layouts.admin')
@section('title','Support impersonation')
@section('heading','Support impersonation')
@section('content')
<div class="mb-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
Strictly audited. Grants are single-target, expire after at most 30 minutes, cannot target platform admins, and every action taken while impersonating is attributed to you. Misuse is a disciplinary matter.
</div>
<x-card title="Create grant">
<form method="POST" action="{{ route('admin.impersonation.grant') }}" class="grid gap-2 text-sm md:grid-cols-4">@csrf
<x-field label="Tenant ID *"><input name="tenant_id" required class="pf-input"></x-field>
<x-field label="Target user ID *"><input name="target_user_id" required class="pf-input"></x-field>
<x-field label="Duration (5–30 min)"><input type="number" name="minutes" value="15" min="5" max="30" class="pf-input"></x-field>
<x-field label="Reason (min 10 chars) *"><input name="reason" required class="pf-input"></x-field>
<span class="md:col-span-4"><x-btn variant="danger">Create grant</x-btn></span>
</form>
</x-card>
<x-card title="Grants" class="mt-4">
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b"><th class="p-2">Target</th><th class="p-2">By</th><th class="p-2">Reason</th><th class="p-2">Expires</th><th class="p-2">State</th><th class="p-2"></th></tr></thead>
<tbody>
@foreach($grants as $g)
<tr class="border-b">
<td class="p-2">{{ $g->target?->email }} (#{{ $g->target_user_id }}, tenant #{{ $g->tenant_id }})</td>
<td class="p-2">{{ $g->granter?->email }}</td>
<td class="p-2">{{ \Str::limit($g->reason,60) }}</td>
<td class="p-2 text-xs">{{ $g->expires_at->format('d M H:i') }}</td>
<td class="p-2"><x-badge :color="$g->isUsable()?'green':'slate'">{{ $g->isUsable()?'Usable':'Closed' }}</x-badge></td>
<td class="p-2 text-right">
@if($g->isUsable())
<form method="POST" action="{{ route('admin.impersonation.assume',$g) }}" class="inline" onsubmit="return confirm('Assume this user session? This is fully audited.')">@csrf<button class="text-xs text-blue-600">Assume</button></form>
<form method="POST" action="{{ route('admin.impersonation.revoke',$g) }}" class="inline">@csrf<button class="ml-2 text-xs text-red-600">Revoke</button></form>
@endif
</td>
</tr>
@endforeach
</tbody></table></div>
</x-card>
@endsection

