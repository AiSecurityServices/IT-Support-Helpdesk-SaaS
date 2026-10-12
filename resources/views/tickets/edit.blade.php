@extends('layouts.app')
@section('title','Edit '.$ticket->number)
@section('heading','Edit '.$ticket->number)
@section('content')
<x-card>
<form method="POST" action="{{ route('tickets.update',$ticket) }}">@csrf @method('PUT')
<x-field label="Subject"><input name="subject" value="{{ old('subject',$ticket->subject) }}" required class="pf-input"></x-field>
<x-field label="Description"><textarea name="description" rows="5" required class="pf-textarea">{{ old('description',$ticket->description) }}</textarea></x-field>
<div class="grid gap-4 md:grid-cols-3">
<x-field label="Priority"><select name="priority" class="pf-select">@foreach(config('supportflow.ticket_priorities') as $k=>$v)<option value="{{ $k }}" @selected($ticket->priority===$k)>{{ $v }}</option>@endforeach</select></x-field>
<x-field label="Customer"><select name="customer_id" class="pf-select"><option value="">—</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected($ticket->customer_id==$c->id)>{{ $c->company_name }}</option>@endforeach</select></x-field>
<x-field label="Category"><select name="category_id" class="pf-select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected($ticket->category_id==$c->id)>{{ $c->name }}</option>@endforeach</select></x-field>
<x-field label="Due date"><input type="date" name="due_at" value="{{ old('due_at',$ticket->due_at?->format('Y-m-d')) }}" class="pf-input"></x-field>
<x-field label="Tags"><input name="tags" value="{{ old('tags',$ticket->tags) }}" class="pf-input"></x-field>
</div>
<x-btn>Save changes</x-btn> <a href="{{ route('tickets.show',$ticket) }}" class="text-sm text-slate-500">Cancel</a>
</form>
</x-card>
@endsection


