@extends('layouts.app')
@section('title','Service catalogue')
@section('heading','Service catalogue')
@section('content')
<div class="grid gap-4 lg:grid-cols-2">
@foreach($items as $item)
<x-card>
<div class="flex items-center justify-between">
<h3 class="font-semibold">{{ $item->name }}</h3>
<x-badge :color="$item->is_active?'green':'slate'">{{ $item->is_active?'Active':'Inactive' }}</x-badge>
</div>
<p class="text-sm text-slate-600 dark:text-slate-300">{{ $item->description }}</p>
<p class="mt-1 text-xs text-slate-500">Team: {{ $item->team?->name ?? '—' }} · Approval: {{ $item->requires_approval ? 'yes ('.$item->approver_role.')' : 'no' }} · Fields: {{ count($item->form_schema ?? []) }}</p>
<details class="mt-2 text-sm"><summary class="cursor-pointer text-blue-600">Edit item</summary>
<form method="POST" action="{{ route('catalog.update',$item) }}" class="mt-2 space-y-2">@csrf @method('PUT')
<x-field label="Name"><input name="name" value="{{ $item->name }}" required class="pf-input"></x-field>
<x-field label="Description"><textarea name="description" rows="2" class="pf-textarea">{{ $item->description }}</textarea></x-field>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="requires_approval" value="1" @checked($item->requires_approval)> Requires approval</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Active</label>
<x-btn variant="secondary">Save</x-btn>
</form>
<form method="POST" action="{{ route('catalog.destroy',$item) }}" onsubmit="return confirm('Delete catalogue item?')" class="mt-1">@csrf @method('DELETE')<button class="text-xs text-red-600">Delete</button></form>
</details>
</x-card>
@endforeach
</div>
<x-card title="New catalogue item" class="mt-4">
<form method="POST" action="{{ route('catalog.store') }}" class="grid gap-2 text-sm md:grid-cols-2">@csrf
<x-field label="Name *"><input name="name" required class="pf-input"></x-field>
<x-field label="Description"><input name="description" class="pf-input"></x-field>
<div class="md:col-span-2"><x-btn>Create item</x-btn> <span class="text-xs text-slate-500">Form fields, team, SLA and approval are configured via edit after creation.</span></div>
</form>
</x-card>
<p class="mt-2 text-sm text-slate-500">Form schemas (required fields, types, options, conditional questions) are stored as structured JSON on each item and validated server-side on every request.</p>
@endsection


