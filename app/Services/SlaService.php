<?php

namespace App\Services;

use App\Models\SlaEvent;
use App\Models\Ticket;
use App\Notifications\SlaBreachNotification;
use App\Notifications\SlaWarningNotification;
use Carbon\Carbon;

/**
 * Owns SLA timer lifecycle: start, transition handling, first-response
 * capture, and scheduled evaluation with idempotent alerting.
 */
class SlaService
{
    protected SlaCalculator $calculator;

    public function __construct(?SlaCalculator $calculator = null)
    {
        $this->calculator = $calculator ?? new SlaCalculator;
    }

    public function startTimers(Ticket $ticket): void
    {
        $deadlines = $this->calculator->deadlinesFor($ticket);
        SlaEvent::create([
            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
            'type' => 'started', 'target' => 'first_response',
            'deadline_at' => $deadlines['first_response_deadline'],
            'meta' => ['policy_id' => $deadlines['policy']?->id],
        ]);
        SlaEvent::create([
            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
            'type' => 'started', 'target' => 'resolution',
            'deadline_at' => $deadlines['resolution_deadline'],
            'meta' => ['policy_id' => $deadlines['policy']?->id],
        ]);
    }

    public function handleTransition(Ticket $ticket, string $from, string $to): void
    {
        if ($to === 'pending_customer') {
            SlaEvent::create([
                'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
                'type' => 'paused', 'meta' => ['from' => $from, 'to' => $to],
            ]);
        }
        if ($from === 'pending_customer') {
            $deadlines = $this->calculator->deadlinesFor($ticket);
            SlaEvent::create([
                'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
                'type' => 'resumed',
                'deadline_at' => $deadlines['resolution_deadline'],
                'meta' => ['from' => $from, 'to' => $to],
            ]);
        }
        if (in_array($to, ['resolved', 'closed'], true)) {
            $this->evaluateAtClose($ticket);
        }
    }

    public function markFirstResponse(Ticket $ticket): void
    {
        $deadlines = $this->calculator->deadlinesFor($ticket);
        $met = Carbon::parse($ticket->first_response_at)->lessThanOrEqualTo($deadlines['first_response_deadline']);
        SlaEvent::create([
            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
            'type' => $met ? 'met' : 'breached', 'target' => 'first_response',
            'deadline_at' => $deadlines['first_response_deadline'],
        ]);
    }

    protected function evaluateAtClose(Ticket $ticket): void
    {
        $deadlines = $this->calculator->deadlinesFor($ticket);
        $closedAt = Carbon::parse($ticket->resolved_at ?? $ticket->closed_at ?? now());
        $met = $closedAt->lessThanOrEqualTo($deadlines['resolution_deadline']);
        SlaEvent::create([
            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
            'type' => $met ? 'met' : 'breached', 'target' => 'resolution',
            'deadline_at' => $deadlines['resolution_deadline'],
        ]);
    }

    /**
     * Scheduled evaluation over open tickets. Idempotent: warns/breaches
     * are recorded once per ticket+target (duplicate runs are no-ops).
     */
    public function evaluateOpenTickets(?int $tenantId = null): array
    {
        $query = Ticket::withoutTenantScope()->whereIn('status', Ticket::OPEN_STATUSES);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        $stats = ['checked' => 0, 'warned' => 0, 'breached' => 0, 'escalated' => 0];

        foreach ($query->with(['customer', 'assignee', 'team.lead'])->cursor() as $ticket) {
            $stats['checked']++;
            $this->calculator = new SlaCalculator($ticket->tenant);
            $deadlines = $this->calculator->deadlinesFor($ticket);
            $now = Carbon::now();

            foreach (['first_response', 'resolution'] as $target) {
                if ($target === 'first_response' && $ticket->first_response_at) {
                    continue;
                }
                $deadline = $deadlines[$target.'_deadline'];
                $threshold = (float) config('supportflow.sla_warning_threshold', 0.8);

                $alreadyWarned = SlaEvent::withoutTenantScope()->where('ticket_id', $ticket->id)
                    ->where('target', $target)->where('type', 'warned')->exists();
                $alreadyBreached = SlaEvent::withoutTenantScope()->where('ticket_id', $ticket->id)
                    ->where('target', $target)->where('type', 'breached')->exists();

                if ($now->greaterThan($deadline)) {
                    if (! $alreadyBreached) {
                        SlaEvent::create([
                            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
                            'type' => 'breached', 'target' => $target, 'deadline_at' => $deadline,
                        ]);
                        $stats['breached']++;
                        $this->alert($ticket, 'breach', $target, $deadline);
                        $this->escalate($ticket);
                        $stats['escalated']++;
                    }
                } elseif (! $alreadyWarned && ! $alreadyBreached) {
                    // Warn once 80% of the target window has elapsed.
                    $created = Carbon::parse($ticket->created_at);
                    $totalWindow = max(1, $created->diffInMinutes($deadline));
                    $elapsed = $created->diffInMinutes(min($now, $deadline));
                    if ($elapsed / $totalWindow >= $threshold) {
                        SlaEvent::create([
                            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
                            'type' => 'warned', 'target' => $target, 'deadline_at' => $deadline,
                        ]);
                        $stats['warned']++;
                        $this->alert($ticket, 'warning', $target, $deadline);
                    }
                }
            }
        }

        return $stats;
    }

    protected function alert(Ticket $ticket, string $kind, string $target, Carbon $deadline): void
    {
        $recipients = collect([$ticket->assignee, $ticket->team?->lead])->filter()->unique('id');
        // Also notify tenant managers.
        $managers = \App\Models\User::where('tenant_id', $ticket->tenant_id)
            ->whereIn('role', ['owner', 'admin', 'manager'])->where('is_active', true)->get();
        $recipients = $recipients->merge($managers)->unique('id');

        foreach ($recipients as $recipient) {
            if ($kind === 'breach') {
                $recipient->notify(new SlaBreachNotification($ticket, $target, $deadline));
            } else {
                $recipient->notify(new SlaWarningNotification($ticket, $target, $deadline));
            }
        }

        AutomationEngine::fire($kind === 'breach' ? 'sla.breach' : 'sla.warning', [
            'tenant_id' => $ticket->tenant_id, 'subject_id' => $ticket->id,
            'ticket_id' => $ticket->id,
            'ticket' => ['status' => $ticket->status, 'priority' => $ticket->priority,
                'customer_id' => $ticket->customer_id, 'team_id' => $ticket->team_id,
                'assigned_to' => $ticket->assigned_to],
            'fingerprint' => ['target' => $target, 'kind' => $kind],
        ]);
    }

    protected function escalate(Ticket $ticket): void
    {
        $ticket->escalation_level = min(5, (int) $ticket->escalation_level + 1);
        if ($ticket->priority === 'low') {
            $ticket->priority = 'normal';
        } elseif ($ticket->priority === 'normal') {
            $ticket->priority = 'high';
        }
        $ticket->save();
        SlaEvent::create([
            'tenant_id' => $ticket->tenant_id, 'ticket_id' => $ticket->id,
            'type' => 'escalated', 'meta' => ['level' => $ticket->escalation_level],
        ]);
    }
}
