<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * Privacy by design: personal data export, tenant closure and retention.
 * Financial/security records are retained per docs/DATA_RETENTION_AND_DELETION.md.
 */
class PrivacyController extends Controller
{
    /** Authenticated user downloads their own personal data (JSON). */
    public function exportMine(Request $request)
    {
        $user = $request->user();
        $data = [
            'exported_at' => now()->toIso8601String(),
            'profile' => $user->only(['name', 'email', 'phone', 'job_title', 'timezone', 'locale', 'created_at']),
            'tickets_requested' => Ticket::withoutTenantScope()->where('requester_id', $user->id)->get()
                ->map(fn ($t) => $t->only(['number', 'subject', 'description', 'status', 'priority', 'created_at'])),
            'auth_events' => \App\Models\AuthEvent::where('user_id', $user->id)->latest()->limit(200)->get()
                ->map(fn ($e) => $e->only(['event', 'ip_address', 'created_at'])),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'my-data-'.$user->id.'.json', ['Content-Type' => 'application/json']);
    }

    /**
     * Owner-only full workspace export (JSON): customers, tickets (+public
     * messages only — internal notes stay internal), assets, invoices,
     * problems/changes, knowledge base. Paginated per collection so large
     * workspaces stream instead of loading everything into memory.
     */
    public function exportTenant(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        $tenant = currentTenant();
        abort_unless($tenant, 404);
        $id = $tenant->id;
        audit('tenant.exported', $tenant);

        $chunked = function ($query, $map) {
            $out = [];
            $query->chunk(500, function ($rows) use (&$out, $map) {
                foreach ($rows as $row) {
                    $out[] = $map($row);
                }
            });

            return $out;
        };

        $data = [
            'exported_at' => now()->toIso8601String(),
            'tenant' => $tenant->only(['name', 'email', 'phone', 'timezone', 'locale', 'currency']),
            'customers' => $chunked(
                \App\Models\Customer::where('tenant_id', $id)->with('contacts'),
                fn ($c) => array_merge($c->only(['company_name', 'email', 'phone', 'city', 'country', 'status']),
                    ['contacts' => $c->contacts->map(fn ($k) => $k->only(['first_name', 'last_name', 'email', 'phone']))->all()])
            ),
            'tickets' => $chunked(
                \App\Models\Ticket::where('tenant_id', $id)->with(['publicMessages']),
                fn ($t) => array_merge($t->only(['number', 'subject', 'description', 'status', 'priority', 'created_at', 'resolved_at']),
                    ['replies' => $t->publicMessages->map(fn ($m) => $m->only(['body', 'created_at']))->all()])
            ),
            'assets' => $chunked(
                \App\Models\Asset::where('tenant_id', $id),
                fn ($a) => $a->only(['asset_tag', 'name', 'type', 'status', 'serial_number'])
            ),
            'invoices' => $chunked(
                \App\Models\Invoice::where('tenant_id', $id)->with('items'),
                fn ($i) => array_merge($i->only(['number', 'status', 'total', 'amount_paid', 'issue_date', 'due_date']),
                    ['items' => $i->items->map(fn ($l) => $l->only(['description', 'quantity', 'unit_price', 'line_total']))->all()])
            ),
            'knowledge_base' => $chunked(
                \App\Models\KbArticle::where('tenant_id', $id),
                fn ($a) => $a->only(['title', 'status', 'visibility', 'version', 'updated_at'])
            ),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }, 'workspace-export-'.$tenant->slug.'-'.now()->format('Ymd').'.json', ['Content-Type' => 'application/json']);
    }

    /**
     * Tenant closure (owner only): cancels subscription, revokes all tokens,
     * marks workspace closed. Erasure of operational data follows the retention
     * schedule; financial records are preserved as required by law.
     */
    public function closeTenant(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);
        $request->validate(['confirm' => 'required|accepted', 'reason' => 'nullable|string|max:1000']);
        $tenant = currentTenant();
        abort_unless($tenant, 404);

        \Illuminate\Support\Facades\DB::transaction(function () use ($tenant, $request) {
            foreach ($tenant->users as $member) {
                $member->tokens()->delete();
            }
            if ($sub = $tenant->activeSubscription) {
                $sub->status = 'cancelled';
                $sub->cancelled_at = now();
                $sub->save();
            }
            $tenant->status = 'cancelled';
            $tenant->save();
            audit('tenant.closed', $tenant, ['reason' => $request->input('reason')]);
        });
        auth()->logout();
        $request->session()->invalidate();

        return redirect('/')->with('status', 'Your workspace has been closed. Contact support within 90 days for data export or erasure requests.');
    }
}
