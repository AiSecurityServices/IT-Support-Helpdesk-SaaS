<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomFieldDefinition;
use App\Models\Department;
use App\Models\SavedReply;
use App\Models\Skill;
use App\Models\WebhookEndpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()->hasRole('owner', 'admin'), 403);
        $tenant = currentTenant();

        return view('settings.index', [
            'tenant' => $tenant,
            'categories' => Category::where('tenant_id', $tenant->id)->orderBy('sort_order')->get(),
            'departments' => Department::where('tenant_id', $tenant->id)->orderBy('name')->get(),
            'skills' => Skill::where('tenant_id', $tenant->id)->orderBy('name')->get(),
            'savedReplies' => SavedReply::where('tenant_id', $tenant->id)->orderBy('title')->get(),
            'customFields' => CustomFieldDefinition::where('tenant_id', $tenant->id)->get(),
            'webhooks' => WebhookEndpoint::where('tenant_id', $tenant->id)->get(),
        ]);
    }

    public function updateOrganisation(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50', 'timezone' => 'required|timezone',
            'locale' => 'required|string|max:10', 'currency' => 'required|string|size:3',
        ]);
        currentTenant()->fill($data)->save();
        audit('settings.organisation');

        return back()->with('success', 'Organisation settings saved.');
    }

    public function updateBranding(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        $data = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'accent_color' => 'nullable|string|max:7',
            'support_email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);
        $settings = currentTenant()->settings ?? [];
        $settings['branding'] = [
            'company_name' => $data['company_name'] ?? null,
            'accent_color' => $data['accent_color'] ?? null,
            'support_email' => $data['support_email'] ?? null,
        ];
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('tenant-logos', 'public');
            $settings['branding']['logo_path'] = $path;
        }
        currentTenant()->updateSettings($settings);
        audit('settings.branding');

        return back()->with('success', 'Branding saved.');
    }

    public function updateWorkingHours(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        $days = $request->input('working_days');
        if (! is_array($days)) {
            $days = array_values(array_unique(array_filter(array_map(
                fn ($v) => is_numeric(trim((string) $v)) ? (int) trim((string) $v) : null,
                explode(',', (string) $days)
            ))));
            $request->merge(['working_days' => $days]);
        }
        $data = $request->validate([
            'working_days' => 'required|array|min:1', 'working_days.*' => 'integer|min:1|max:7',
            'work_start' => 'required|date_format:H:i', 'work_end' => 'required|date_format:H:i|after:work_start',
        ]);
        currentTenant()->updateSettings(['working_hours' => [
            'days' => $data['working_days'], 'start' => $data['work_start'],
            'end' => $data['work_end'], 'timezone' => currentTenant()->timezone,
        ]]);
        audit('settings.working_hours');

        return back()->with('success', 'Working hours saved. SLA deadlines use these from now on.');
    }

    public function updateNotifications(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        currentTenant()->updateSettings(['notifications' => [
            'email_enabled' => $request->boolean('email_enabled', true),
        ]]);
        audit('settings.notifications');

        return back()->with('success', 'Notification preferences saved.');
    }

    public function updateInvoice(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        $data = $request->validate([
            'prefix' => 'required|string|max:10', 'currency' => 'required|string|size:3',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'payment_terms_days' => 'required|integer|min:0|max:365',
            'default_hourly_rate' => 'required|numeric|min:0|max:10000',
        ]);
        currentTenant()->updateSettings(['invoice' => $data]);
        audit('settings.invoice');

        return back()->with('success', 'Invoice settings saved.');
    }

    public function updateSecurity(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        currentTenant()->updateSettings([
            'security' => ['require_mfa' => $request->boolean('require_mfa')],
            'approvals' => ['separation_of_duties' => $request->boolean('separation_of_duties', true)],
        ]);
        audit('settings.security');

        return back()->with('success', 'Security settings saved.');
    }

    public function updateFeatures(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        $allowed = ['knowledge_base', 'service_catalog', 'problems', 'changes', 'major_incidents', 'cmdb', 'automation', 'outgoing_webhooks'];
        $flags = [];
        foreach ($allowed as $flag) {
            $flags[$flag] = (bool) ($request->input('features.'.$flag, false));
        }
        currentTenant()->updateSettings(['features' => $flags]);
        audit('settings.features', null, $flags);

        return back()->with('success', 'Feature rollout saved.');
    }

    // ---- Lookup management ----

    public function storeCategory(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string|max:500']);
        Category::create($data + ['tenant_id' => $this->tenantId(), 'slug' => Str::slug($data['name'])]);

        return back()->with('success', 'Category added.');
    }

    public function destroyCategory(Category $category)
    {
        abort_unless((int) $category->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->canManage('configuration'), 403);
        abort_if($category->tickets()->exists(), 422, 'Category is in use and cannot be deleted. Deactivate instead.');
        $category->delete();

        return back()->with('success', 'Category removed.');
    }

    public function storeDepartment(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string|max:500']);
        Department::create($data + ['tenant_id' => $this->tenantId()]);

        return back()->with('success', 'Department added.');
    }

    public function storeSkill(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $data = $request->validate(['name' => 'required|string|max:255']);
        Skill::firstOrCreate(['tenant_id' => $this->tenantId(), 'name' => $data['name']]);

        return back()->with('success', 'Skill added.');
    }

    public function storeReply(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $data = $request->validate(['title' => 'required|string|max:255', 'body' => 'required|string|max:5000']);
        SavedReply::create($data + ['tenant_id' => $this->tenantId()]);

        return back()->with('success', 'Saved reply added.');
    }

    public function destroyReply(SavedReply $reply)
    {
        abort_unless((int) $reply->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->canManage('configuration'), 403);
        $reply->delete();

        return back()->with('success', 'Saved reply removed.');
    }

    public function storeCustomField(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $data = $request->validate([
            'label' => 'required|string|max:255', 'type' => 'required|in:text,number,date,select,boolean',
            'options' => 'nullable|string|max:1000', 'is_required' => 'nullable|boolean',
        ]);
        CustomFieldDefinition::create([
            'tenant_id' => $this->tenantId(), 'applies_to' => 'ticket',
            'label' => $data['label'], 'key' => Str::slug($data['label'], '_'),
            'type' => $data['type'],
            'options' => isset($data['options']) ? array_map('trim', explode(',', $data['options'])) : null,
            'is_required' => $request->boolean('is_required'),
        ]);

        return back()->with('success', 'Custom field added.');
    }

    public function storeWebhook(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        abort_unless(currentTenant()?->activeSubscription?->plan?->hasFeature('webhooks') ?? false, 403, 'Webhooks require the Professional plan.');
        $data = $request->validate([
            'url' => 'required|url|max:1000', 'events' => 'required|array|min:1',
        ]);
        WebhookEndpoint::create([
            'tenant_id' => $this->tenantId(), 'url' => $data['url'],
            'events' => $data['events'], 'secret' => Str::random(48), 'is_active' => true,
        ]);

        return back()->with('success', 'Webhook endpoint added. Sign deliveries with the shown secret (stored securely).');
    }

    public function destroyWebhook(WebhookEndpoint $webhook)
    {
        abort_unless((int) $webhook->tenant_id === $this->tenantId(), 404);
        abort_unless(request()->user()->canManage('configuration'), 403);
        $webhook->delete();

        return back()->with('success', 'Webhook removed.');
    }
}
