@extends('layouts.app')
@section('title','New ticket')
@section('heading','New ticket')
@section('content')
<x-card>
<form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">@csrf
<div class="grid gap-4 md:grid-cols-2">
<x-field label="Subject"><input name="subject" value="{{ old('subject') }}" required class="pf-input"></x-field>
<x-field label="Priority">
<select name="priority" class="pf-select">@foreach(config('supportflow.ticket_priorities') as $k=>$v)<option value="{{ $k }}" @selected(old('priority','normal')===$k)>{{ $v }}</option>@endforeach</select>
</x-field>
</div>
<x-field label="Description"><textarea name="description" rows="5" required class="pf-textarea">{{ old('description') }}</textarea></x-field>
<div class="grid gap-4 md:grid-cols-3">
<x-field label="Customer"><select name="customer_id" class="pf-select"><option value="">—</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id')==$c->id)>{{ $c->company_name }}</option>@endforeach</select></x-field>
<x-field label="Category"><select name="category_id" class="pf-select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></x-field>
<x-field label="Due date"><input type="date" name="due_at" value="{{ old('due_at') }}" class="pf-input"></x-field>
<x-field label="Assign to"><select name="assigned_to" class="pf-select"><option value="">Unassigned</option>@foreach($technicians as $t)<option value="{{ $t->id }}" @selected(old('assigned_to')==$t->id)>{{ $t->name }}</option>@endforeach</select></x-field>
<x-field label="Team"><select name="team_id" class="pf-select"><option value="">—</option>@foreach($teams as $t)<option value="{{ $t->id }}" @selected(old('team_id')==$t->id)>{{ $t->name }}</option>@endforeach</select></x-field>
<x-field label="Related asset"><select name="asset_id" class="pf-select"><option value="">—</option>@foreach($assets as $a)<option value="{{ $a->id }}">{{ $a->asset_tag }} — {{ $a->name }}</option>@endforeach</select></x-field>
</div>
<x-field label="Tags (comma separated)"><input name="tags" value="{{ old('tags') }}" class="pf-input"></x-field>
@foreach($customFields as $f)
<x-field label="{{ $f->label }}{{ $f->is_required ? ' *' : '' }}">
@if($f->type==='select')<select name="custom_fields[{{ $f->key }}]" class="pf-select"><option value="">—</option>@foreach($f->options ?? [] as $o)<option>{{ $o }}</option>@endforeach</select>
@else<input name="custom_fields[{{ $f->key }}]" class="pf-input">
@endif</x-field>
@endforeach
<x-field label="Attachments" hint="Max 10MB per file."><input type="file" name="attachments[]" multiple></x-field>
<x-btn>Create ticket</x-btn>
</form>
</x-card>
@endsection


