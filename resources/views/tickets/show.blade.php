@extends('layouts.app')
@section('title', $ticket->number.' — '.$ticket->subject)
@section('eyebrow')Ticket {{ $ticket->number }} · {{ $ticket->customer?->company_name ?? 'No customer' }}@endsection
@section('heading'){{ $ticket->subject }}@endsection
@section('actions')
  <a href="{{ route('tickets.edit',$ticket) }}" class="pf-btn pf-btn-ghost pf-btn-sm">Edit</a>
  <form method="POST" action="{{ $ticket->watchers->contains(auth()->id()) ? route('tickets.unwatch',$ticket) : route('tickets.watch',$ticket) }}">@csrf @if($ticket->watchers->contains(auth()->id())) @method('DELETE') <button class="pf-btn pf-btn-ghost pf-btn-sm">Unwatch</button> @else <button class="pf-btn pf-btn-ghost pf-btn-sm">Watch</button> @endif</form>
@endsection
@section('content')
<div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
  @include('tickets.partials.badges')
  <span class="text-slate-500">opened {{ $ticket->created_at->format('d M Y H:i') }} by {{ $ticket->requester?->name ?? '—' }}</span>
</div>

<div class="grid gap-5 lg:grid-cols-3">
<div class="space-y-5 lg:col-span-2">
  <x-card title="Description"><p class="whitespace-pre-wrap text-[0.92rem] leading-relaxed">{{ $ticket->description }}</p>
    @if($ticket->tagList())<div class="mt-3 flex flex-wrap gap-1.5">@foreach($ticket->tagList() as $tag)<x-badge>{{ $tag }}</x-badge>@endforeach</div>@endif
  </x-card>

  <x-card title="Conversation">
    <ol class="space-y-3">
    @forelse($ticket->messages as $m)
    <li class="rounded-[10px] border p-3.5 {{ $m->visibility==='internal' ? 'border-violet-400/50 bg-violet-500/[0.06]' : 'border-slate-200 dark:border-slate-700' }}">
      <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
        <strong class="text-sm text-slate-800 dark:text-slate-100">{{ $m->author?->name ?? 'System' }}</strong> · {{ $m->created_at->format('d M Y H:i') }}
        @if($m->visibility==='internal')<x-badge color="purple">Internal — hidden from customer</x-badge>@else<x-badge color="blue">Visible to customer</x-badge>@endif
      </div>
      <p class="mt-1.5 whitespace-pre-wrap text-[0.9rem] leading-relaxed">{{ $m->body }}</p>
    </li>
    @empty<li class="pf-empty"><x-icon name="send" class="pf-icon-lg" /><p class="text-sm">No replies yet — start the conversation below.</p></li>@endforelse
    </ol>

    <form method="POST" action="{{ route('tickets.reply',$ticket) }}" enctype="multipart/form-data" class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">@csrf
      <x-field label="Public reply (visible to customer)">
        <textarea name="body" rows="3" required class="pf-input"></textarea>
      </x-field>
      <div class="flex flex-wrap items-center gap-2">
        <x-btn><x-icon name="send" /> Send reply</x-btn>
        <input type="file" name="attachments[]" multiple aria-label="Attach files to reply" class="text-sm">
        @if($savedReplies->isNotEmpty())
        <label class="ml-auto flex items-center gap-2 text-sm">Template
        <select id="savedReply" class="pf-select !w-auto text-sm"><option value="">Template…</option>@foreach($savedReplies as $r)<option value="{{ $r->id }}">{{ $r->title }}</option>@endforeach</select></label>
        @endif
      </div>
    </form>
    <form method="POST" action="{{ route('tickets.note',$ticket) }}" class="mt-4 border-t border-dashed border-violet-400/50 pt-4">@csrf
      <x-field label="Internal note (never shown to customer)"><textarea name="body" rows="2" required class="pf-input"></textarea></x-field>
      <x-btn variant="secondary">Save internal note</x-btn>
    </form>
  </x-card>

  <x-card title="Time tracking · {{ $ticket->timeEntries->sum('minutes') }} min">
    @forelse($ticket->timeEntries as $e)
    <div class="flex items-center justify-between gap-2 border-b border-slate-200 py-2 text-sm last:border-0 dark:border-slate-700">
      <span>{{ $e->technician?->name }} · <strong>{{ $e->minutes }} min</strong> {{ $e->billable ? '(billable)' : '(non-billable)' }} — {{ \Str::limit($e->description,60) }} <span class="text-slate-500">{{ $e->status }}</span></span>
      <span class="flex shrink-0 gap-2">
        @if($e->status==='running')<form method="POST" action="{{ route('time.stop',$e) }}">@csrf<button class="text-xs font-semibold text-cyan-700 hover:underline dark:text-cyan-300">Stop</button></form>@endif
        @if($e->status!=='invoiced')<form method="POST" action="{{ route('time.destroy',$e) }}" onsubmit="return confirm('Delete entry?')">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600 hover:underline">Delete</button></form>@endif
      </span>
    </div>
    @empty<p class="text-sm text-slate-500">No time logged yet.</p>@endforelse
    <div class="mt-3 flex flex-wrap items-end gap-2.5">
      <form method="POST" action="{{ route('tickets.time.store',$ticket) }}" class="flex flex-wrap items-end gap-2.5 text-sm">@csrf
        <x-field label="Minutes"><input type="number" name="minutes" min="1" max="1440" required class="pf-input !w-24"></x-field>
        <x-field label="Details"><input name="description" class="pf-input md:!w-56"></x-field>
        <x-field label="Rate/hr"><input type="number" step="0.01" name="hourly_rate" class="pf-input !w-24"></x-field>
        <label class="mb-3 flex items-center gap-1.5 text-sm"><input type="checkbox" name="billable" value="1" checked class="pf-check"> Billable</label>
        <span class="mb-3"><x-btn variant="secondary">Log time</x-btn></span>
      </form>
      <form method="POST" action="{{ route('tickets.timer.start',$ticket) }}" class="mb-3">@csrf<x-btn variant="ghost"><x-icon name="clock" /> Start timer</x-btn></form>
    </div>
  </x-card>
</div>

<div class="space-y-5">
  <x-card title="Service level">
    <p class="text-sm">Policy: <strong>{{ $deadlines['policy']?->name ?? 'Tenant default' }}</strong></p>
    <dl class="mt-2 space-y-1.5 text-sm">
    @if($remaining['first_response']!==null)
      <div class="flex justify-between gap-2"><dt class="text-slate-500">First response</dt><dd><strong class="{{ $remaining['first_response']<0?'text-red-600':'' }}">{{ $remaining['first_response']<0 ? 'Breached' : $remaining['first_response'].' min left' }}</strong></dd></div>
    @else<div class="flex justify-between gap-2"><dt class="text-slate-500">First response</dt><dd>Recorded</dd></div>@endif
    @if($remaining['resolution']!==null)
      <div class="flex justify-between gap-2"><dt class="text-slate-500">Resolution</dt><dd><strong class="{{ $remaining['resolution']<0?'text-red-600':'' }}">{{ $remaining['resolution']<0 ? 'Breached' : $remaining['resolution'].' min left' }}</strong></dd></div>
    @endif
    </dl>
    <p class="mt-2 font-mono text-[0.7rem] text-slate-500">Due {{ $ticket->due_at?->format('d M Y H:i') ?? '—' }} · {{ $deadlines['paused_minutes'] }} min paused</p>
  </x-card>

  <x-card title="Assignment">
    <p class="text-sm">Technician: <strong>{{ $ticket->assignee?->name ?? 'Unassigned' }}</strong></p>
    <p class="mt-1 text-sm">Team: <strong>{{ $ticket->team?->name ?? '—' }}</strong></p>
    @can('manageAssignment', $ticket)
    <form method="POST" action="{{ route('tickets.assign',$ticket) }}" class="mt-3 space-y-2.5">@csrf
      <x-field label="Technician"><select name="assigned_to" class="pf-select text-sm"><option value="">Unassigned</option>@foreach(\App\Models\User::where('tenant_id',$ticket->tenant_id)->whereIn('role',['owner','admin','manager','technician'])->orderBy('name')->get() as $u)<option value="{{ $u->id }}" @selected($ticket->assigned_to==$u->id)>{{ $u->name }}</option>@endforeach</select></x-field>
      <x-field label="Team"><select name="team_id" class="pf-select text-sm"><option value="">—</option>@foreach(\App\Models\Team::where('tenant_id',$ticket->tenant_id)->orderBy('name')->get() as $tm)<option value="{{ $tm->id }}" @selected($ticket->team_id==$tm->id)>{{ $tm->name }}</option>@endforeach</select></x-field>
      <x-btn variant="secondary">Save assignment</x-btn>
    </form>
    @endcan
  </x-card>

  <x-card title="Change status">
    <form method="POST" action="{{ route('tickets.transition',$ticket) }}">@csrf
      <x-field label="Move to"><select name="to" class="pf-select">@foreach($transitions as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach</select></x-field>
      <x-btn variant="secondary">Apply</x-btn>
    </form>
    <form method="POST" action="{{ route('tickets.merge',$ticket) }}" class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700">@csrf
      <x-field label="Merge another ticket into this one (ticket ID)"><input name="secondary_id" class="pf-input"></x-field>
      <x-btn variant="ghost">Merge</x-btn>
    </form>
  </x-card>

  <x-card title="Details">
    <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
      <dt class="text-slate-500">Category</dt><dd>{{ $ticket->category?->name ?? '—' }}</dd>
      <dt class="text-slate-500">Asset</dt><dd>@if($ticket->asset)<a class="font-medium text-cyan-700 hover:underline dark:text-cyan-300" href="{{ route('assets.show',$ticket->asset) }}">{{ $ticket->asset->name }}</a>@else — @endif</dd>
      <dt class="text-slate-500">Source</dt><dd>{{ ucfirst($ticket->source) }}</dd>
      <dt class="text-slate-500">Satisfaction</dt><dd>{{ $ticket->satisfaction_rating ? $ticket->satisfaction_rating.'/5' : '—' }}</dd>
    </dl>
  </x-card>

  <x-card title="History">
    @foreach($ticket->history->take(12) as $h)
    <div class="border-b border-slate-200 py-1.5 text-xs last:border-0 dark:border-slate-700"><strong>{{ str_replace('_',' ',$h->action) }}</strong> <span class="text-slate-500">by {{ $h->actor?->name ?? 'system' }} · {{ $h->created_at->diffForHumans() }}</span></div>
    @endforeach
  </x-card>
</div>
</div>
@push('scripts')
<script>
document.getElementById('savedReply')?.addEventListener('change', function(){
  var replies = @json($savedReplies->pluck('body','id'));
  if(this.value && replies[this.value]){ document.querySelector('form[action*="reply"] textarea').value = replies[this.value]; }
});
</script>
@endpush
@endsection
