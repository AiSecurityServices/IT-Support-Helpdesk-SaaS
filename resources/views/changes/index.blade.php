@extends('layouts.app')
@section('title','Changes')
@section('heading','Change management')
@section('content')
<x-card>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th scope="col" class="p-2">Title</th><th scope="col" class="p-2">Type</th><th scope="col" class="p-2">Risk</th><th scope="col" class="p-2">Status</th><th scope="col" class="p-2">Window</th></tr></thead>
<tbody>
@forelse($changes as $c)
<tr class="border-b dark:border-slate-700">
<td class="p-2"><a class="font-medium text-blue-600" href="{{ route('changes.show',$c) }}">{{ $c->title }}</a></td>
<td class="p-2">{{ ucfirst($c->type) }}</td>
<td class="p-2"><x-badge :color="$c->risk==='high'?'red':($c->risk==='medium'?'yellow':'green')">{{ ucfirst($c->risk) }}</x-badge></td>
<td class="p-2"><x-badge>{{ ucfirst(str_replace('_',' ',$c->status)) }}</x-badge></td>
<td class="p-2 text-xs">{{ $c->window_start?->format('d M H:i') ?? '—' }}</td>
</tr>
@empty<tr><td colspan="5" class="p-6 text-center text-slate-500">No changes recorded.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-2">{{ $changes->links() }}</div>
</x-card>
<x-card title="New change request" class="mt-4">
<form method="POST" action="{{ route('changes.store') }}" class="grid gap-2 text-sm md:grid-cols-3">@csrf
<x-field label="Title *"><input name="title" required class="pf-input"></x-field>
<x-field label="Type"><select name="type" class="pf-select"><option value="standard">Standard</option><option value="normal">Normal</option><option value="emergency">Emergency</option></select></x-field>
<x-field label="Risk"><select name="risk" class="pf-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></x-field>
<span class="md:col-span-3"><x-btn>Create draft</x-btn></span>
</form>
</x-card>
@endsection

