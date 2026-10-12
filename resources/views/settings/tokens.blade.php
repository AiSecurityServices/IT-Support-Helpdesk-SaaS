@extends('layouts.app')
@section('title','API credentials')
@section('heading','API credentials & service accounts')
@section('content')
@if(session('plain_token'))
<x-card title="New token — copy it now">
<p class="rounded bg-slate-100 p-2 font-mono text-sm break-all dark:bg-slate-700">{{ session('plain_token') }}</p>
<p class="mt-1 text-xs text-slate-500">It will never be shown again.</p>
</x-card>
@endif
<div class="mt-4 grid gap-4 lg:grid-cols-2">
<x-card title="Your tokens">
<div class="overflow-x-auto"><table class="w-full text-left text-sm">
<thead><tr class="border-b dark:border-slate-700"><th scope="col" class="p-2">Name</th><th scope="col" class="p-2">Scopes</th><th scope="col" class="p-2">Last used</th><th scope="col" class="p-2">Expires</th><th scope="col" class="p-2"></th></tr></thead>
<tbody>
@forelse($tokens as $t)
<tr class="border-b dark:border-slate-700">
<td class="p-2">{{ $t->name }}</td>
<td class="p-2 text-xs">{{ implode(', ', $t->abilities ?? []) }}</td>
<td class="p-2 text-xs">{{ $t->last_used_at?->diffForHumans() ?? 'never' }}</td>
<td class="p-2 text-xs">{{ $t->expires_at?->format('d M Y') ?? 'never' }}@if($t->expires_at && $t->expires_at->isPast()) <x-badge color="red">Expired</x-badge>@endif</td>
<td class="p-2"><form method="POST" action="{{ route('settings.tokens.revoke',$t->id) }}" onsubmit="return confirm('Revoke this token? Integrations using it stop immediately.')">@csrf @method('DELETE')<button class="text-xs text-red-600">Revoke</button></form></td>
</tr>
@empty<tr><td colspan="5" class="p-4 text-center text-slate-500">No tokens yet.</td></tr>@endforelse
</tbody></table></div>
</x-card>
<x-card title="Issue token">
<form method="POST" action="{{ route('settings.tokens.store') }}" class="space-y-2 text-sm">@csrf
<x-field label="Name *"><input name="name" required placeholder="e.g. Monitoring integration" class="pf-input"></x-field>
<fieldset><legend class="text-sm font-medium">Scopes</legend>
@foreach(\App\Http\Controllers\ApiTokenController::ABILITIES as $a)<label class="mr-3 text-sm"><input type="checkbox" name="abilities[]" value="{{ $a }}"> {{ $a }}</label>@endforeach
</fieldset>
<x-field label="Expires (optional, max 2 years)"><input type="date" name="expires_at" class="pf-input"></x-field>
<x-btn>Issue token</x-btn>
</form>
</x-card>
</div>
@if(auth()->user()->hasRole('owner'))
<x-card title="Service accounts (token-only, password login disabled)" class="mt-4">
@foreach($serviceAccounts as $s)<div class="border-b py-1 text-sm last:border-0 dark:border-slate-700">{{ $s->name }} <span class="text-slate-500">· {{ $s->email }}</span></div>@endforeach
<form method="POST" action="{{ route('settings.service-accounts.store') }}" class="mt-2 flex gap-2 text-sm">@csrf
<input name="name" placeholder="Account name" required aria-label="Service account name" class="pf-input flex-1">
<x-btn variant="secondary">Create account</x-btn>
</form>
</x-card>
@endif
@endsection

