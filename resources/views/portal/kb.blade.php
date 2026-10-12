@extends('layouts.portal')
@section('title','Help articles')
@section('heading','Help articles')
@section('content')
<form method="GET" class="mb-3 flex gap-2 text-sm"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search articles…" aria-label="Search articles" class="pf-input flex-1"><x-btn variant="secondary">Search</x-btn></form>
<x-card>
@forelse($articles as $a)<a href="{{ route('portal.kb.show',$a) }}" class="block border-b border-slate-200 py-2.5 last:border-0 dark:border-slate-700"><span class="font-semibold text-cyan-700 hover:underline dark:text-cyan-300">{{ $a->title }}</span><br><span class="text-xs text-slate-500">{{ $a->category?->name }} · {{ $a->helpful_votes }} found helpful</span></a>
@empty<div class="pf-empty"><x-icon name="book" class="pf-icon-lg" /><p class="text-sm">No articles found.</p></div>@endforelse
<div class="mt-2">{{ $articles->links() }}</div>
</x-card>
@endsection

