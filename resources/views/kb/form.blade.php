@extends('layouts.app')
@section('title', isset($article) ? 'Edit article' : 'New article')
@section('heading', isset($article) ? 'Edit article' : 'New article')
@section('content')
<x-card>
@php $a = $article ?? null; @endphp
<form method="POST" action="{{ $a ? route('kb.update',$a) : route('kb.store') }}">@csrf @if($a) @method('PUT') @endif
<x-field label="Title"><input name="title" value="{{ old('title',$a->title ?? '') }}" required class="pf-input"></x-field>
<div class="grid gap-4 md:grid-cols-3">
<x-field label="Category"><select name="kb_category_id" class="pf-select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('kb_category_id',$a->kb_category_id ?? '')==$c->id)>{{ $c->name }}</option>@endforeach</select></x-field>
<x-field label="Visibility" hint="Only Published + Public is ever customer-visible."><select name="visibility" class="pf-select"><option value="internal" @selected(old('visibility',$a->visibility ?? 'internal')==='internal')>Internal (staff only)</option><option value="public" @selected(old('visibility',$a->visibility ?? '')==='public')>Public (customers)</option></select></x-field>
<x-field label="Tags (comma separated)"><input name="tags" value="{{ old('tags',$a->tags ?? '') }}" class="pf-input"></x-field>
</div>
<x-field label="Body"><textarea name="body" rows="12" required class="pf-textarea">{{ old('body',$a->body ?? '') }}</textarea></x-field>
<x-btn>{{ $a ? 'Save new version' : 'Save draft' }}</x-btn>
</form>
</x-card>
@endsection


