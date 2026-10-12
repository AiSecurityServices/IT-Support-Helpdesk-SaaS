@extends('layouts.app')
@section('title','Knowledge base')
@section('heading','Knowledge base')
@section('content')
<div class="mb-3 flex flex-wrap gap-2 text-sm">
  <form method="GET" class="flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search articles…" aria-label="Search articles" class="pf-input">
    <select name="status" aria-label="Filter by status" class="pf-select"><option value="">All statuses</option>@foreach(['draft','review','published','archived'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
    <x-btn variant="secondary">Search</x-btn>
  </form>
  <span class="ml-auto"><a href="{{ route('kb.create') }}" class="rounded-lg bg-blue-600 px-4 py-1 text-white">+ New article</a></span>
</div>
<x-card>
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th scope="col" class="p-2">Title</th><th scope="col" class="p-2">Category</th><th scope="col" class="p-2">Visibility</th><th scope="col" class="p-2">Status</th><th scope="col" class="p-2">Version</th><th scope="col" class="p-2">Feedback</th></tr></thead>
<tbody>
@forelse($articles as $a)
<tr class="border-b hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/50">
<td class="p-2"><a class="font-medium text-blue-600" href="{{ route('kb.show',$a) }}">{{ $a->title }}</a></td>
<td class="p-2">{{ $a->category?->name ?? '—' }}</td>
<td class="p-2"><x-badge :color="$a->visibility==='public'?'blue':'slate'">{{ ucfirst($a->visibility) }}</x-badge></td>
<td class="p-2"><x-badge :color="$a->status==='published'?'green':($a->status==='review'?'yellow':'slate')">{{ ucfirst($a->status) }}</x-badge></td>
<td class="p-2">v{{ $a->version }}</td>
<td class="p-2">{{ $a->helpful_votes }} helpful / {{ $a->not_helpful_votes }} not</td>
</tr>
@empty<tr><td colspan="6" class="p-6 text-center text-slate-500">No articles yet.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-2">{{ $articles->links() }}</div>
</x-card>
<x-card title="Categories" class="mt-4">
<form method="POST" action="{{ route('kb.categories.store') }}" class="flex gap-2 text-sm">@csrf<input name="name" placeholder="New category" required aria-label="New category name" class="pf-input flex-1"><x-btn variant="secondary">Add</x-btn></form>
<div class="mt-2 flex flex-wrap gap-1">@foreach($categories as $c)<x-badge>{{ $c->name }}</x-badge>@endforeach</div>
</x-card>
@endsection

