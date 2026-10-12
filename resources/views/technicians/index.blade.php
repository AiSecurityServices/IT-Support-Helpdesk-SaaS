@extends('layouts.app')
@section('title','Technicians')
@section('heading','Technicians & workload')
@section('content')
<x-card title="Invite staff">
<form method="POST" action="{{ route('technicians.invite') }}" class="flex flex-wrap items-end gap-2 text-sm">@csrf
<x-field label="Email"><input type="email" name="email" required class="pf-input"></x-field>
<x-field label="Role"><select name="role" class="pf-select"><option value="technician">Technician</option><option value="manager">Manager</option><option value="admin">Admin</option></select></x-field>
<x-btn>Send invitation</x-btn>
</form>
</x-card>
<x-card class="mt-4">
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th class="p-2">Name</th><th class="p-2">Role</th><th class="p-2">Teams</th><th class="p-2">Open tickets</th><th class="p-2">Hours (month)</th><th class="p-2">Status</th><th></th></tr></thead>
<tbody>
@foreach($technicians as $t)
<tr class="border-b dark:border-slate-700">
<td class="p-2"><a class="text-blue-600" href="{{ route('technicians.show',$t) }}">{{ $t->name }}</a><div class="text-xs text-slate-500">{{ $t->email }}</div></td>
<td class="p-2">{{ ucfirst($t->role) }}</td>
<td class="p-2">{{ $t->teams->pluck('name')->join(', ') }}</td>
<td class="p-2">{{ $workload[$t->id] ?? 0 }}</td>
<td class="p-2">{{ round(($minutes[$t->id] ?? 0)/60,1) }}h</td>
<td class="p-2"><x-badge :color="$t->is_active?'green':'slate'">{{ $t->is_active?'Active':'Inactive' }}</x-badge></td>
<td class="p-2">@if($t->is_active && $t->id!==auth()->id())<form method="POST" action="{{ route('technicians.deactivate',$t) }}" onsubmit="return confirm('Deactivate? Open tickets must be reassigned first.')">@csrf<button class="text-xs text-red-600">Deactivate</button></form>@endif</td>
</tr>
@endforeach
</tbody></table></div>
<div class="mt-2">{{ $technicians->links() }}</div>
</x-card>
@endsection

