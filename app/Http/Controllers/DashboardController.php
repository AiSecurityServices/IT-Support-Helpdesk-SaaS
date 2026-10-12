<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Services\SlaCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isCustomer()) {
            return redirect()->route('portal.dashboard');
        }

        $tenantId = $this->tenantId();
        $base = Ticket::where('tenant_id', $tenantId);

        // Staff visibility: technicians see team + assigned work; managers see all.
        if ($user->hasRole('technician')) {
            $teamIds = $user->teams()->pluck('teams.id');
            $base = $base->where(function ($q) use ($user, $teamIds) {
                $q->where('assigned_to', $user->id)->orWhereIn('team_id', $teamIds);
            });
        }

        $open = (clone $base)->whereIn('status', Ticket::OPEN_STATUSES);
        $calculator = new SlaCalculator(currentTenant());

        $stats = [
            'open' => (clone $open)->count(),
            'unassigned' => (clone $open)->whereNull('assigned_to')->count(),
            'urgent' => (clone $open)->where('priority', 'urgent')->count(),
            'overdue' => (clone $open)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'awaiting_customer' => (clone $open)->where('status', 'pending_customer')->count(),
            'resolved_7d' => (clone $base)->where('status', 'resolved')
                ->where('resolved_at', '>=', now()->subDays(7))->count(),
            'new_today' => (clone $base)->whereDate('created_at', today())->count(),
        ];

        $byStatus = (clone $base)->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $byPriority = (clone $open)->selectRaw('priority, COUNT(*) as c')->groupBy('priority')->pluck('c', 'priority');

        $volume = (clone $base)->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->where('created_at', '>=', now()->subDays(14))->groupBy('d')->orderBy('d')->get();

        $myTickets = Ticket::with(['customer', 'assignee'])
            ->where('tenant_id', $tenantId)->where('assigned_to', $user->id)
            ->whereIn('status', Ticket::OPEN_STATUSES)->orderBy('due_at')->limit(8)->get();

        $recent = (clone $base)->with(['customer', 'assignee'])->latest()->limit(8)->get();

        $warrantyAlerts = Asset::where('tenant_id', $tenantId)
            ->whereNotNull('warranty_expires_at')
            ->whereDate('warranty_expires_at', '<=', now()->addDays(30))
            ->orderBy('warranty_expires_at')->limit(5)->get();

        $invoiceStats = [
            'outstanding' => Invoice::where('tenant_id', $tenantId)
                ->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('total'),
            'overdue_count' => Invoice::where('tenant_id', $tenantId)->where('status', 'overdue')->count(),
        ];

        $subscription = currentTenant()?->activeSubscription;

        $onboarding = currentTenant() && $request->user()->hasRole('owner', 'admin')
            ? app(\App\Services\OnboardingChecklist::class)->steps(currentTenant())
            : null;

        return view('dashboard', compact(
            'stats', 'byStatus', 'byPriority', 'volume', 'myTickets',
            'recent', 'warrantyAlerts', 'invoiceStats', 'subscription', 'onboarding'
        ));
    }
}
