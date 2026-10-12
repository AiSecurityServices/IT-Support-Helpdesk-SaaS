<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BillingEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Platform Super Admin — aggregate operations only. Per-tenant record
 * access is deliberately absent: support access must be explicit and audited.
 */
class AdminController extends Controller
{
    public function dashboard()
    {
        $metrics = [
            'tenants_total' => Tenant::count(),
            'tenants_trial' => Tenant::where('status', 'trial')->count(),
            'tenants_active' => Tenant::where('status', 'active')->count(),
            'tenants_past_due' => Tenant::where('status', 'past_due')->count(),
            'tenants_suspended' => Tenant::where('status', 'suspended')->count(),
            'mrr_pence' => Subscription::whereIn('status', ['active', 'past_due', 'grace'])
                ->with('plan')->get()->sum(fn ($s) => $s->interval === 'annual'
                    ? ($s->plan?->price_annual ?? 0) / 12 : ($s->plan?->price_monthly ?? 0)),
            'tickets_total' => Ticket::withoutTenantScope()->count(),
            'users_total' => User::count(),
        ];

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'recent_tenants' => Tenant::latest()->limit(8)->get(),
            'recent_events' => BillingEvent::where('tenant_id', '!=', 0)->latest()->limit(10)->get(),
            'failed_jobs' => DB::table('failed_jobs')->orderByDesc('failed_at')->limit(5)->get(),
            'audit' => AuditLog::whereNull('tenant_id')->latest()->limit(10)->get(),
        ]);
    }

    public function tenants(Request $request)
    {
        $query = Tenant::with(['activeSubscription.plan'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->input('q').'%');
        }

        return view('admin.tenants', ['tenants' => $query->paginate(15)->withQueryString()]);
    }

    public function tenantShow(Tenant $tenant)
    {
        $tenant->load(['activeSubscription.plan', 'users']);
        $usage = [
            'tickets' => Ticket::withoutTenantScope()->where('tenant_id', $tenant->id)->count(),
            'users' => $tenant->users()->count(),
        ];

        return view('admin.tenant', compact('tenant', 'usage') + [
            'events' => BillingEvent::where('tenant_id', $tenant->id)->latest()->limit(20)->get(),
        ]);
    }

    public function suspend(Request $request, Tenant $tenant)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $tenant->status = 'suspended';
        $tenant->suspended_at = now();
        $tenant->save();
        AuditLog::record(null, $request->user()->id, 'admin.tenant_suspended', $tenant, ['reason' => $request->input('reason')]);

        return back()->with('success', "Tenant {$tenant->name} suspended.");
    }

    public function reactivate(Tenant $tenant)
    {
        $tenant->status = 'active';
        $tenant->suspended_at = null;
        $tenant->save();
        AuditLog::record(null, request()->user()->id, 'admin.tenant_reactivated', $tenant);

        return back()->with('success', "Tenant {$tenant->name} reactivated.");
    }

    public function plans()
    {
        return view('admin.plans', ['plans' => Plan::orderBy('sort_order')->get()]);
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'price_monthly' => 'required|numeric|min:0',
            'price_annual' => 'required|numeric|min:0', 'currency' => 'required|string|size:3',
            'technician_seats' => 'required|integer|min:1', 'asset_limit' => 'required|integer|min:1',
            'ticket_limit_monthly' => 'required|integer|min:1', 'storage_mb' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean', 'trial_eligible' => 'nullable|boolean',
        ]);
        $plan->fill($data + ['is_active' => $request->boolean('is_active', true),
            'trial_eligible' => $request->boolean('trial_eligible', true)])->save();
        AuditLog::record(null, $request->user()->id, 'admin.plan_updated', $plan);

        return back()->with('success', 'Plan updated.');
    }

    public function audit(Request $request)
    {
        $query = AuditLog::latest();
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        return view('admin.audit', ['logs' => $query->paginate(30)->withQueryString()]);
    }

    public function health()
    {
        return view('admin.health', [
            'queue' => config('queue.default'),
            'failed_jobs' => DB::table('failed_jobs')->orderByDesc('failed_at')->limit(20)->get(),
            'recent_exceptions' => collect(),
            'webhook_failures' => \App\Models\WebhookDelivery::where('delivered', false)->latest()->limit(20)->get(),
        ]);
    }
}
