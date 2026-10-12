@extends('layouts.app')
@section('title', $article->title)
@section('heading', $article->title)
@section('content')
<div class="mb-3 flex flex-wrap items-center gap-2 text-sm">
  <x-badge :color="$article->status==='published'?'green':'slate'">{{ ucfirst($article->status) }}</x-badge>
  <x-badge :color="$article->visibility==='public'?'blue':'slate'">{{ ucfirst($article->visibility) }}</x-badge>
  <span class="text-slate-500">v{{ $article->version }} · by {{ $article->author?->name }} · reviewed {{ $article->last_reviewed_at?->format('d M Y') ?? 'never' }}</span>
  <span class="ml-auto flex gap-2">
    <a href="{{ route('kb.edit',$article) }}" class="rounded-lg border px-3 py-1">Edit</a>
    <form method="POST" action="{{ route('kb.destroy',$article) }}" onsubmit="return confirm('Archive this article?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-300 px-3 py-1 text-red-600">Archive</button></form>
  </span>
</div>
<div class="grid gap-4 lg:grid-cols-3">
<x-card class="lg:col-span-2">
<div class="prose max-w-none whitespace-pre-wrap text-sm dark:prose-invert">{{ $article->body }}</div>
<div class="mt-3 border-t pt-3 dark:border-slate-700">
<p class="text-sm font-medium">Was this helpful?</p>
<form method="POST" action="{{ route('kb.feedback',$article) }}" class="mt-1 flex items-center gap-2 text-sm">@csrf
<input type="hidden" name="helpful" value="1">
<button formaction="{{ route('kb.feedback',$article) }}" class="pf-btn pf-btn-ghost pf-btn-sm"><x-icon name="check" /> Yes, helpful</button>
</form>
<form method="POST" action="{{ route('kb.feedback',$article) }}" class="mt-1 flex items-center gap-2 text-sm">@csrf
<input type="hidden" name="helpful" value="0">
<input name="comment" placeholder="What was missing? (optional)" class="pf-input flex-1">
<button class="pf-btn pf-btn-ghost pf-btn-sm"><x-icon name="x" /> Not helpful</button>
</form>
<p class="mt-1 text-xs text-slate-500">{{ $article->helpful_votes }} found this helpful · {{ $article->not_helpful_votes }} did not</p>
</div>
</x-card>
<div class="space-y-4">
<x-card title="Workflow">
<form method="POST" action="{{ route('kb.transition',$article) }}" class="flex gap-2 text-sm">@csrf
<select name="to" class="pf-select">@foreach(['draft','review','published','archived'] as $s)<option value="{{ $s }}" @selected($article->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<x-btn variant="secondary">Move</x-btn>
</form>
<p class="mt-1 text-xs text-slate-500">Publishing needs manager+. Only Published + Public reaches customers.</p>
</x-card>
<x-card title="Related articles">
@foreach($related as $r)<div class="text-sm"><a class="text-blue-600" href="{{ route('kb.show',$r) }}">{{ $r->title }}</a></div>@endforeach
</x-card>
<x-card title="Version history">
@foreach($article->versions->take(8) as $v)<div class="border-b py-1 text-xs last:border-0 dark:border-slate-700">v{{ $v->version }} · {{ $v->created_at->format('d M Y H:i') }}</div>@endforeach
</x-card>
</div>
</div>
@endsection

