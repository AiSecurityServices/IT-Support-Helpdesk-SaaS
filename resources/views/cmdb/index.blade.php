@extends('layouts.app')
@section('title','CMDB')
@section('heading','Configuration relationships')
@section('content')
<div class="grid gap-4 lg:grid-cols-3">
<x-card class="lg:col-span-2">
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th scope="col" class="p-2">From</th><th scope="col" class="p-2">Relation</th><th scope="col" class="p-2">To</th><th scope="col" class="p-2"></th></tr></thead>
<tbody>
@forelse($relationships as $r)
<tr class="border-b dark:border-slate-700">
<td class="p-2"><a class="text-blue-600" href="{{ route('cmdb.impact',[$r->from_type,$r->from_id]) }}">{{ \App\Models\CiRelationship::label($r->from_type,$r->from_id) }}</a></td>
<td class="p-2 text-xs">{{ str_replace('_',' ',$r->relation) }}</td>
<td class="p-2"><a class="text-blue-600" href="{{ route('cmdb.impact',[$r->to_type,$r->to_id]) }}">{{ \App\Models\CiRelationship::label($r->to_type,$r->to_id) }}</a></td>
<td class="p-2"><form method="POST" action="{{ route('cmdb.destroy',$r) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="text-xs text-red-600">Remove</button></form></td>
</tr>
@empty<tr><td colspan="4" class="p-6 text-center text-slate-500">No relationships yet.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-2">{{ $relationships->links() }}</div>
</x-card>
<x-card title="New relationship">
<form method="POST" action="{{ route('cmdb.store') }}" class="space-y-2 text-sm">@csrf
<div class="grid grid-cols-2 gap-2">
<x-field label="From type"><select name="from_type" class="pf-select"><option value="asset">Asset</option><option value="customer">Customer</option><option value="location">Location</option><option value="user">User</option></select></x-field>
<x-field label="From ID"><input name="from_id" required class="pf-input"></x-field>
<x-field label="To type"><select name="to_type" class="pf-select"><option value="asset">Asset</option><option value="customer">Customer</option><option value="location">Location</option><option value="user">User</option></select></x-field>
<x-field label="To ID"><input name="to_id" required class="pf-input"></x-field>
</div>
<x-field label="Relation"><select name="relation" class="pf-select"><option value="depends_on">depends on</option><option value="hosted_on">hosted on</option><option value="used_by">used by</option><option value="located_at">located at</option><option value="manages">manages</option><option value="member_of">member of</option></select></x-field>
<x-btn>Save relationship</x-btn>
</form>
</x-card>
</div>
@endsection

