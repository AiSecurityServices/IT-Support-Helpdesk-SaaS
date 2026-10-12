@extends('layouts.app')
@section('title','Problems')
@section('heading','Problem management')
@section('content')
<x-card>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th scope="col" class="p-2">Title</th><th scope="col" class="p-2">Status</th><th scope="col" class="p-2">Known error</th><th scope="col" class="p-2">Linked incidents</th><th scope="col" class="p-2">Owner</th></tr></thead>
<tbody>
@forelse($problems as $p)
<tr class="border-b dark:border-slate-700">
<td class="p-2"><a class="font-medium text-blue-600" href="{{ route('problems.show',$p) }}">{{ $p->title }}</a></td>
<td class="p-2"><x-badge>{{ ucfirst(str_replace('_',' ',$p->status)) }}</x-badge></td>
<td class="p-2">{{ $p->known_error ? 'Yes' : 'No' }}</td>
<td class="p-2">{{ $p->tickets_count }}</td>
<td class="p-2">{{ $p->owner?->name ?? '—' }}</td>
</tr>
@empty<tr><td colspan="5" class="p-6 text-center text-slate-500">No problems recorded.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-2">{{ $problems->links() }}</div>
</x-card>
<x-card title="Record a problem" class="mt-4">
<form method="POST" action="{{ route('problems.store') }}" class="grid gap-2 text-sm md:grid-cols-2">@csrf
<x-field label="Title *"><input name="title" required class="pf-input"></x-field>
<x-field label="Description"><input name="description" class="pf-input"></x-field>
<span class="md:col-span-2"><x-btn>Create</x-btn></span>
</form>
</x-card>
@endsection

