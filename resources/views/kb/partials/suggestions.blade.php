@if($articles->isNotEmpty())
<div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm dark:bg-blue-900/20" role="status">
<p class="font-medium">Suggested help articles:</p>
<ul class="list-disc pl-5">
@foreach($articles as $a)<li><a class="text-blue-600" href="{{ auth()->user()->isCustomer() ? route('portal.kb.show',$a) : route('kb.show',$a) }}">{{ $a->title }}</a></li>@endforeach
</ul>
</div>
@endif
