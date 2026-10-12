@extends('layouts.app')
@section('title','Automation')
@section('heading','Workflow automation')
@section('content')
<div class="grid gap-4 lg:grid-cols-2">
@foreach($rules as $rule)
<x-card>
<div class="flex items-center justify-between">
<h3 class="font-semibold">{{ $rule->name }}</h3>
<x-badge :color="$rule->is_active?'green':'slate'">{{ $rule->is_active?'Active':'Disabled' }}</x-badge>
</div>
<p class="text-xs text-slate-500">On <code>{{ $rule->trigger }}</code> · {{ count($rule->conditions ?? []) }} condition(s) · {{ count($rule->actions ?? []) }} action(s) · {{ $rule->run_count }} runs</p>
<ul class="mt-1 text-xs text-slate-500">
@foreach($rule->conditions ?? [] as $c)<li>IF {{ $c['field'] }} {{ $c['operator'] }} {{ $c['value'] }}</li>@endforeach
@foreach($rule->actions ?? [] as $a)<li>THEN {{ $a['type'] }} {{ json_encode($a['params'] ?? []) }}</li>@endforeach
</ul>
<div class="mt-2 flex gap-2 text-sm">
<form method="POST" action="{{ route('automation.dry-run',$rule) }}">@csrf<x-btn variant="ghost">Dry-run</x-btn></form>
<form method="POST" action="{{ route('automation.destroy',$rule) }}" onsubmit="return confirm('Delete rule?')">@csrf @method('DELETE')<button class="text-xs text-red-600">Delete</button></form>
</div>
</x-card>
@endforeach
</div>
<div class="mt-4 grid gap-4 lg:grid-cols-2">
<x-card title="New rule">
<form method="POST" action="{{ route('automation.store') }}" class="space-y-2 text-sm">@csrf
<x-field label="Name *"><input name="name" required placeholder="e.g. Urgent VIP auto-assign" class="pf-input"></x-field>
<x-field label="Trigger"><select name="trigger" class="pf-select">@foreach(\App\Models\AutomationRule::TRIGGERS as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></x-field>
<div class="grid grid-cols-3 gap-2">
<x-field label="Condition field"><input name="conditions[0][field]" placeholder="ticket.priority" class="pf-input"></x-field>
<x-field label="Operator"><select name="conditions[0][operator]" class="pf-select"><option value="eq">equals</option><option value="neq">not equals</option><option value="contains">contains</option><option value="in">in</option></select></x-field>
<x-field label="Value"><input name="conditions[0][value]" placeholder="urgent" class="pf-input"></x-field>
</div>
<x-field label="Action"><select name="actions[0][type]" class="pf-select"><option value="assign_team">Assign team</option><option value="assign_technician">Assign technician</option><option value="set_priority">Set priority</option><option value="add_label">Add label</option><option value="escalate">Escalate</option><option value="notify">Notify</option><option value="internal_note">Internal note</option><option value="webhook">Webhook</option></select></x-field>
<x-field label="Action params (JSON)" hint='e.g. {"team_id":2} or {"value":"high"} or {"audience":"managers","message":"…"}'><textarea name="actions_json" rows="2" placeholder='{"team_id": 2}' class="pf-textarea"></textarea></x-field>
<label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> Active (dry-run first!)</label>
<x-btn>Create rule</x-btn>
</form>
</x-card>
<x-card title="Recent runs">
@foreach($runs as $r)<div class="border-b py-1 text-xs last:border-0 dark:border-slate-700"><x-badge :color="$r->status==='ok'?'green':($r->status==='failed'?'red':'slate')">{{ $r->status }}</x-badge> rule #{{ $r->automation_rule_id }} · subject #{{ $r->subject_id }} · {{ $r->created_at->diffForHumans() }}</div>@endforeach
</x-card>
</div>
@endsection

