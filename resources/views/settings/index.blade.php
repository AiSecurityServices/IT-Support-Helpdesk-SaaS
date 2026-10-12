@extends('layouts.app')
@section('title','Workspace settings')
@section('heading','Workspace settings')
@section('content')
<div class="grid gap-4 lg:grid-cols-2">
<x-card title="Organisation">
<form method="POST" action="{{ route('settings.organisation') }}" class="space-y-2 text-sm">@csrf @method('PUT')
<x-field label="Workspace name"><input name="name" value="{{ $tenant->name }}" required class="pf-input"></x-field>
<div class="grid grid-cols-2 gap-2">
<x-field label="Email"><input name="email" type="email" value="{{ $tenant->email }}" class="pf-input"></x-field>
<x-field label="Phone"><input name="phone" value="{{ $tenant->phone }}" class="pf-input"></x-field>
<x-field label="Timezone"><select name="timezone" class="pf-select">@foreach(timezone_identifiers_list() as $tz)<option @selected($tenant->timezone===$tz)>{{ $tz }}</option>@endforeach</select></x-field>
<x-field label="Currency"><input name="currency" value="{{ $tenant->currency }}" maxlength="3" class="pf-input"></x-field>
</div>
<x-field label="Locale"><input name="locale" value="{{ $tenant->locale }}" class="pf-input"></x-field>
<x-btn variant="secondary">Save</x-btn>
</form>
</x-card>
<x-card title="Branding">
<form method="POST" action="{{ route('settings.branding') }}" enctype="multipart/form-data" class="space-y-2 text-sm">@csrf @method('PUT')
<x-field label="Display company name"><input name="company_name" value="{{ $tenant->setting('branding.company_name') }}" class="pf-input"></x-field>
<x-field label="Accent colour"><input type="color" name="accent_color" value="{{ $tenant->setting('branding.accent_color','#2563eb') }}"></x-field>
<x-field label="Support email"><input name="support_email" type="email" value="{{ $tenant->setting('branding.support_email') }}" class="pf-input"></x-field>
<x-field label="Logo"><input type="file" name="logo" accept="image/*"></x-field>
<x-btn variant="secondary">Save branding</x-btn>
</form>
</x-card>
<x-card title="Working hours (SLA calendar)">
<form method="POST" action="{{ route('settings.hours') }}" class="space-y-2 text-sm">@csrf @method('PUT')
<x-field label="Working days (1=Mon…7=Sun, comma)"><input name="working_days" value="{{ implode(',', $tenant->workingCalendar()['days']) }}" required class="pf-input"></x-field>
<div class="grid grid-cols-2 gap-2"><x-field label="Start"><input type="time" name="work_start" value="{{ $tenant->workingCalendar()['start'] }}" class="pf-input"></x-field><x-field label="End"><input type="time" name="work_end" value="{{ $tenant->workingCalendar()['end'] }}" class="pf-input"></x-field></div>
<x-btn variant="secondary">Save hours</x-btn>
</form>
<p class="mt-2 text-xs"><a class="text-blue-600" href="{{ route('sla.index') }}">Manage SLA policies & holidays →</a></p>
</x-card>
<x-card title="Notifications">
<form method="POST" action="{{ route('settings.notifications') }}" class="space-y-2 text-sm">@csrf @method('PUT')
<label class="flex items-center gap-2"><input type="checkbox" name="email_enabled" value="1" @checked(($tenant->setting('notifications.email_enabled',true)))> Send email notifications</label>
<x-btn variant="secondary">Save</x-btn>
</form>
</x-card>
<x-card title="Security & approvals">
<form method="POST" action="{{ route('settings.security') }}" class="space-y-2 text-sm">@csrf @method('PUT')
<label class="flex items-center gap-2"><input type="checkbox" name="require_mfa" value="1" @checked($tenant->setting('security.require_mfa',false))> Require two-factor authentication for staff</label>
<label class="flex items-center gap-2"><input type="checkbox" name="separation_of_duties" value="1" @checked($tenant->setting('approvals.separation_of_duties',true))> Enforce separation of duties on approvals (recommended)</label>
<x-btn variant="secondary">Save</x-btn>
</form>
<p class="mt-2 text-sm"><a class="text-blue-600" href="{{ route('settings.tokens') }}">Manage API credentials & service accounts →</a></p>
<p class="mt-1 text-sm"><a class="text-blue-600" href="{{ route('privacy.export') }}">Download my personal data (JSON) →</a></p>
<p class="mt-1 text-sm"><a class="text-blue-600" href="{{ route('settings.export') }}">Download full workspace export (JSON) →</a></p>
<p class="mt-1 text-sm"><a class="text-blue-600" href="{{ route('settings.mail.preview') }}">Mail templates & delivery test →</a></p>
</x-card>
<x-card title="Feature rollout">
<p class="mb-2 text-sm text-slate-500">Untick a module to hide it from navigation during staged rollouts. Core safety features cannot be disabled.</p>
<form method="POST" action="{{ route('settings.features') }}" class="space-y-2 text-sm">@csrf @method('PUT')
@foreach(['knowledge_base' => 'Knowledge base', 'service_catalog' => 'Service catalogue', 'problems' => 'Problems', 'changes' => 'Changes', 'major_incidents' => 'Major incidents', 'cmdb' => 'CMDB', 'automation' => 'Automation', 'outgoing_webhooks' => 'Outgoing webhooks'] as $flag => $label)
<label class="flex items-center gap-2"><input type="checkbox" name="features[{{ $flag }}]" value="1" @checked(feature($flag))> {{ $label }}</label>
@endforeach
<x-btn variant="secondary">Save rollout</x-btn>
</form>
</x-card>
<x-card title="Invoice defaults">
<form method="POST" action="{{ route('settings.invoice') }}" class="space-y-2 text-sm">@csrf @method('PUT')
<div class="grid grid-cols-2 gap-2">
<x-field label="Number prefix"><input name="prefix" value="{{ $tenant->setting('invoice.prefix','INV-') }}" required class="pf-input"></x-field>
<x-field label="Currency"><input name="currency" value="{{ $tenant->setting('invoice.currency','GBP') }}" maxlength="3" class="pf-input"></x-field>
<x-field label="Tax %"><input type="number" step="0.01" name="tax_percent" value="{{ $tenant->setting('invoice.tax_percent',20) }}" class="pf-input"></x-field>
<x-field label="Payment terms (days)"><input type="number" name="payment_terms_days" value="{{ $tenant->setting('invoice.payment_terms_days',14) }}" class="pf-input"></x-field>
<x-field label="Default hourly rate"><input type="number" step="0.01" name="default_hourly_rate" value="{{ $tenant->setting('invoice.default_hourly_rate',75) }}" class="pf-input"></x-field>
</div>
<x-btn variant="secondary">Save</x-btn>
</form>
</x-card>
<x-card title="Categories">
<form method="POST" action="{{ route('settings.categories.store') }}" class="flex gap-2 text-sm">@csrf<input name="name" placeholder="New category" required class="pf-input flex-1"><x-btn variant="secondary">Add</x-btn></form>
<ul class="mt-2 text-sm">@foreach($categories as $c)<li class="flex justify-between border-b py-1 last:border-0 dark:border-slate-700"><span>{{ $c->name }}</span><form method="POST" action="{{ route('settings.categories.destroy',$c) }}">@csrf @method('DELETE')<button class="text-xs text-red-600">Remove</button></form></li>@endforeach</ul>
</x-card>
<x-card title="Saved replies">
<form method="POST" action="{{ route('settings.replies.store') }}" class="space-y-2 text-sm">@csrf
<input name="title" placeholder="Title" required class="pf-input">
<textarea name="body" placeholder="Reply body" required rows="2" class="pf-textarea"></textarea>
<x-btn variant="secondary">Add reply</x-btn>
</form>
<ul class="mt-2 text-sm">@foreach($savedReplies as $r)<li class="flex justify-between border-b py-1 last:border-0 dark:border-slate-700"><span>{{ $r->title }}</span><form method="POST" action="{{ route('settings.replies.destroy',$r) }}">@csrf @method('DELETE')<button class="text-xs text-red-600">Remove</button></form></li>@endforeach</ul>
</x-card>
<x-card title="Custom ticket fields">
<form method="POST" action="{{ route('settings.fields.store') }}" class="grid grid-cols-2 gap-2 text-sm">@csrf
<input name="label" placeholder="Label" required class="pf-input">
<select name="type" class="pf-select"><option value="text">Text</option><option value="number">Number</option><option value="date">Date</option><option value="select">Select</option><option value="boolean">Yes/No</option></select>
<input name="options" placeholder="Options (comma, for select)" class="pf-input">
<label class="col-span-2 flex items-center gap-2"><input type="checkbox" name="is_required" value="1"> Required</label>
<span class="col-span-2"><x-btn variant="secondary">Add field</x-btn></span>
</form>
<ul class="mt-2 text-sm">@foreach($customFields as $f)<li class="border-b py-1 last:border-0 dark:border-slate-700">{{ $f->label }} ({{ $f->type }})</li>@endforeach</ul>
</x-card>
<x-card title="Skills">
<form method="POST" action="{{ route('settings.skills.store') }}" class="flex gap-2 text-sm">@csrf<input name="name" placeholder="New skill" required class="pf-input flex-1"><x-btn variant="secondary">Add</x-btn></form>
<div class="mt-2 flex flex-wrap gap-1">@foreach($skills as $s)<x-badge>{{ $s->name }}</x-badge>@endforeach</div>
</x-card>
<x-card title="Outgoing webhooks" class="lg:col-span-2">
<form method="POST" action="{{ route('settings.webhooks.store') }}" class="flex flex-wrap gap-2 text-sm">@csrf
<input name="url" type="url" placeholder="https://…" required class="pf-input flex-1">
<label class="flex items-center gap-1"><input type="checkbox" name="events[]" value="ticket.assigned" checked> ticket.assigned</label>
<label class="flex items-center gap-1"><input type="checkbox" name="events[]" value="ticket.replied" checked> ticket.replied</label>
<x-btn variant="secondary">Add endpoint</x-btn>
</form>
<ul class="mt-2 text-sm">@foreach($webhooks as $w)<li class="flex justify-between border-b py-1 last:border-0 dark:border-slate-700"><span>{{ $w->url }} <span class="text-slate-500">({{ implode(',',$w->events ?? []) }})</span></span><form method="POST" action="{{ route('settings.webhooks.destroy',$w) }}">@csrf @method('DELETE')<button class="text-xs text-red-600">Remove</button></form></li>@endforeach</ul>
</x-card>
@if(auth()->user()->hasRole('owner'))
<x-card title="Danger zone: close workspace">
<p class="text-sm text-slate-600 dark:text-slate-300">Cancels the subscription, revokes every API token and signs everyone out. Operational data follows the retention schedule; financial records are preserved as required by law. See docs/DATA_RETENTION_AND_DELETION.md.</p>
<form method="POST" action="{{ route('settings.close') }}" class="mt-2 space-y-2 text-sm" onsubmit="return confirm('Close this workspace? This cannot be undone.')">@csrf
<x-field label="Reason (optional)"><input name="reason" class="pf-input"></x-field>
<label class="flex items-center gap-2"><input type="checkbox" name="confirm" value="1" required> I understand this closes the workspace</label>
<x-btn variant="danger">Close workspace</x-btn>
</form>
</x-card>
@endif
</div>
@endsection


