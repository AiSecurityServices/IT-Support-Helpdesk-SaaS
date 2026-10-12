@extends('layouts.portal')
@section('title','Invoices')
@section('heading','Invoices')
@section('content')
<x-card>
@forelse($invoices as $i)<a href="{{ route('portal.invoices.show',$i) }}" class="flex justify-between border-b py-2 text-sm last:border-0"><span><strong>{{ $i->number }}</strong> · due {{ $i->due_date?->format('d M Y') }}</span><span>{{ money($i->balance(),$i->currency) }} outstanding · {{ $i->status }}</span></a>
@empty<p class="text-sm text-slate-500">No invoices.</p>@endforelse
<div class="mt-2">{{ $invoices->links() }}</div>
</x-card>
@endsection
