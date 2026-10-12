@extends('layouts.portal')
@section('title','Profile')
@section('heading','Profile')
@section('content')
<x-card>
<form method="POST" action="{{ route('portal.profile.update') }}">@csrf @method('PUT')
<x-field label="Name"><input name="name" value="{{ $user->name }}" required class="pf-input"></x-field>
<x-field label="Phone"><input name="phone" value="{{ $user->phone }}" class="pf-input"></x-field>
<x-btn>Save</x-btn>
</form>
</x-card>
@endsection

