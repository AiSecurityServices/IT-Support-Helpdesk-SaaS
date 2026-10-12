@extends('layouts.app')
@section('title','Notifications')
@section('heading','Notifications')
@section('content')
<x-card>
<form method="POST" action="{{ route('notifications.readAll') }}" class="mb-2">@csrf<button class="text-sm text-blue-600">Mark all as read</button></form>
@forelse($notifications as $n)
<form method="POST" action="{{ route('notifications.read',$n->id) }}">@csrf
<button class="block w-full border-b py-2 text-left text-sm last:border-0 dark:border-slate-700 {{ $n->read_at ? 'opacity-60' : '' }}">
<strong>{{ $n->data['kind'] ?? 'notification' }}</strong> — {{ $n->data['number'] ?? $n->data['name'] ?? '' }}
<span class="text-slate-500">{{ $n->created_at->diffForHumans() }}</span>
</button>
</form>
@empty<p class="text-sm text-slate-500">No notifications.</p>@endforelse
<div class="mt-2">{{ $notifications->links() }}</div>
</x-card>
@endsection
