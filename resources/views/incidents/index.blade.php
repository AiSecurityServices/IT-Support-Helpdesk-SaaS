@extends('layouts.app')
@section('title','Major incidents')
@section('heading','Major incidents')
@section('content')
<x-card>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th scope="col" class="p-2">Title</th><th scope="col" class="p-2">Commander</th><th scope="col" class="p-2">Status</th><th scope="col" class="p-2">Tickets</th><th scope="col" class="p-2">Opened</th></tr></thead>
<tbody>
@forelse($incidents as $i)
<tr class="border-b dark:border-slate-700">
<td class="p-2"><a class="font-medium text-blue-600" href="{{ route('incidents.show',$i) }}">{{ $i->title }}</a></td>
<td class="p-2">{{ $i->commander?->name ?? '—' }}</td>
<td class="p-2"><x-badge :color="$i->status==='open'?'red':'slate'">{{ ucfirst($i->status) }}</x-badge></td>
<td class="p-2">{{ $i->tickets_count }}</td>
<td class="p-2">{{ $i->created_at->format('d M H:i') }}</td>
</tr>
@empty<tr><td colspan="5" class="p-6 text-center text-slate-500">No major incidents. Long may it last.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-2">{{ $incidents->links() }}</div>
</x-card>
<x-card title="Declare major incident" class="mt-4">
<form method="POST" action="{{ route('incidents.store') }}" class="grid gap-2 text-sm md:grid-cols-2">@csrf
<x-field label="Title *"><input name="title" required class="pf-input"></x-field>
<x-field label="Summary"><input name="summary" class="pf-input"></x-field>
<span class="md:col-span-2"><x-btn variant="danger">Declare incident</x-btn></span>
</form>
</x-card>
@endsection

