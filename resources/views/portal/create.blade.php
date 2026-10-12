@extends('layouts.portal')
@section('title','New request')
@section('heading','New support request')
@section('content')
<x-card>
<form method="POST" action="{{ route('portal.tickets.store') }}" enctype="multipart/form-data">@csrf
<x-field label="Subject"><input name="subject" required class="pf-input"></x-field>
<x-field label="Describe the issue"><textarea name="description" rows="5" required class="pf-textarea"></textarea></x-field>
<div class="grid gap-4 md:grid-cols-3">
<x-field label="Category"><select name="category_id" class="pf-select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></x-field>
<x-field label="Priority"><select name="priority" class="pf-select"><option value="normal">Normal</option><option value="low">Low</option><option value="high">High</option><option value="urgent">Urgent</option></select></x-field>
<x-field label="Device"><select name="asset_id" class="pf-select"><option value="">—</option>@foreach($assets as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></x-field>
</div>
<x-btn>Submit request</x-btn>
</form>
</x-card>
@endsection

