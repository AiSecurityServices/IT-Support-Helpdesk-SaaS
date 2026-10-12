@extends('layouts.portal')
@section('title', $article->title)
@section('heading', $article->title)
@section('content')
<x-card>
<div class="whitespace-pre-wrap text-sm">{{ $article->body }}</div>
<form method="POST" action="{{ route('kb.feedback',$article) }}" class="mt-3 flex items-center gap-2 border-t pt-3 text-sm">@csrf
<span>Was this helpful?</span>
<button name="helpful" value="1" class="pf-btn pf-btn-ghost pf-btn-sm"><x-icon name="check" /> Yes</button>
<button name="helpful" value="0" class="pf-btn pf-btn-ghost pf-btn-sm"><x-icon name="x" /> No</button>
</form>
</x-card>
@endsection
