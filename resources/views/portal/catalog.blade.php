@extends('layouts.portal')
@section('title','IT services')
@section('heading','Request an IT service')
@section('content')
<div class="grid gap-4 md:grid-cols-2">
@foreach($items as $item)
<x-card>
<h2 class="font-semibold">{{ $item->name }}</h2>
<p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $item->description }}</p>
@if($item->requires_approval)<p class="mt-1 text-xs text-slate-500">Requires manager approval.</p>@endif
<form method="POST" action="{{ route('portal.services.request',$item) }}" enctype="multipart/form-data" class="mt-2 space-y-2 text-sm">@csrf
@foreach($item->form_schema ?? [] as $f)
<x-field label="{{ $f['label'] }}{{ !empty($f['required']) ? ' *' : '' }}">
@if(($f['type'] ?? 'text')==='select')<select name="fields[{{ $f['key'] }}]" class="pf-select"><option value="">—</option>@foreach($f['options'] ?? [] as $o)<option>{{ $o }}</option>@endforeach</select>
@elseif(($f['type'] ?? 'text')==='boolean')<select name="fields[{{ $f['key'] }}]" class="pf-select"><option value="yes">Yes</option><option value="no">No</option></select>
@else<input name="fields[{{ $f['key'] }}]" type="{{ $f['type']==='number' ? 'number' : ($f['type']==='date' ? 'date' : 'text') }}" class="pf-input">
@endif</x-field>
@endforeach
<x-btn>Submit request</x-btn>
</form>
</x-card>
@endforeach
</div>
@endsection

