<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\SlaPolicy;
use App\Models\Tenant;
use App\Models\Ticket;
use Carbon\Carbon;

/**
 * Business-calendar SLA calculator.
 *
 * - All deadlines computed in the tenant timezone.
 * - Only configured working days/hours count; weekends + tenant holidays excluded.
 * - Pause windows (e.g. Pending Customer) are subtracted from elapsed time.
 */
class SlaCalculator
{
    public function __construct(protected ?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? currentTenant();
    }

    public function policyFor(Ticket $ticket): ?SlaPolicy
    {
        if ($ticket->sla_policy_id) {
            $policy = SlaPolicy::find($ticket->sla_policy_id);
            if ($policy && $policy->is_active) {
                return $policy;
            }
        }
        if ($ticket->customer?->sla_policy_id) {
            $policy = SlaPolicy::find($ticket->customer->sla_policy_id);
            if ($policy && $policy->is_active) {
                return $policy;
            }
        }

        return SlaPolicy::where('tenant_id', $ticket->tenant_id)
            ->where('is_active', true)->where('is_default', true)->first()
            ?? SlaPolicy::where('tenant_id', $ticket->tenant_id)->where('is_active', true)->first();
    }

    public function calendar(SlaPolicy $policy = null): array
    {
        if ($policy?->working_hours) {
            return $policy->working_hours;
        }

        return $this->tenant?->workingCalendar() ?? [
            'days' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '17:30',
            'timezone' => config('app.timezone'),
        ];
    }

    public function holidays(): array
    {
        if (! $this->tenant) {
            return [];
        }

        return Holiday::where('tenant_id', $this->tenant->id)
            ->pluck('date')->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))->all();
    }

    /**
     * Add N business minutes to $from, honouring working hours, days and holidays.
     */
    public function addBusinessMinutes(Carbon $from, int $minutes, array $calendar = null, array $holidays = null): Carbon
    {
        $calendar ??= $this->calendar();
        $holidays ??= $this->holidays();
        $tz = $calendar['timezone'] ?? config('app.timezone');

        $cursor = $from->copy()->setTimezone($tz);
        $remaining = $minutes;
        $guard = 0;

        while ($remaining > 0 && $guard++ < 20000) {
            if (! $this->isWorkingMoment($cursor, $calendar, $holidays)) {
                $cursor = $this->nextWorkingStart($cursor, $calendar, $holidays);

                continue;
            }
            [$dayEnd] = $this->dayBounds($cursor, $calendar);
            $available = $cursor->diffInMinutes($dayEnd, false);
            if ($available <= 0) {
                $cursor = $this->nextWorkingStart($cursor, $calendar, $holidays);

                continue;
            }
            $step = min($remaining, $available);
            $cursor->addMinutes($step);
            $remaining -= $step;
            if ($remaining > 0) {
                $cursor = $this->nextWorkingStart($cursor, $calendar, $holidays);
            }
        }

        return $cursor;
    }

    /** Business minutes elapsed between two moments (pauses excluded by caller). */
    public function businessMinutesBetween(Carbon $from, Carbon $to, array $calendar = null, array $holidays = null): int
    {
        $calendar ??= $this->calendar();
        $holidays ??= $this->holidays();
        $tz = $calendar['timezone'] ?? config('app.timezone');

        $cursor = $from->copy()->setTimezone($tz);
        $end = $to->copy()->setTimezone($tz);
        if ($end->lessThanOrEqualTo($cursor)) {
            return 0;
        }
        $total = 0;
        $guard = 0;
        while ($cursor->lessThan($end) && $guard++ < 20000) {
            if (! $this->isWorkingMoment($cursor, $calendar, $holidays)) {
                $cursor = $this->nextWorkingStart($cursor, $calendar, $holidays);

                continue;
            }
            [$dayEnd] = $this->dayBounds($cursor, $calendar);
            $sliceEnd = $end->lessThan($dayEnd) ? $end->copy() : $dayEnd;
            $total += $cursor->diffInMinutes($sliceEnd);
            $cursor = $sliceEnd->copy();
            if ($cursor->lessThan($end)) {
                $cursor = $this->nextWorkingStart($cursor, $calendar, $holidays);
            }
        }

        return max(0, $total);
    }

    /**
     * Compute first-response + resolution deadlines for a ticket,
     * subtracting accumulated pause windows (pending_customer periods).
     */
    public function deadlinesFor(Ticket $ticket): array
    {
        $policy = $this->policyFor($ticket);
        $calendar = $this->calendar($policy);
        $holidays = $this->holidays();
        $created = Carbon::parse($ticket->created_at)->setTimezone($calendar['timezone'] ?? config('app.timezone'));

        $firstTarget = (int) ($policy?->targetFor($ticket->priority, 'first_response')
            ?? config("supportflow.sla_default_targets.{$ticket->priority}.first_response", 480));
        $resolutionTarget = (int) ($policy?->targetFor($ticket->priority, 'resolution')
            ?? config("supportflow.sla_default_targets.{$ticket->priority}.resolution", 2880));

        $paused = $this->pausedBusinessMinutes($ticket, $calendar, $holidays);

        return [
            'policy' => $policy,
            'first_response_deadline' => $this->addBusinessMinutes($created, $firstTarget + $paused, $calendar, $holidays),
            'resolution_deadline' => $this->addBusinessMinutes($created, $resolutionTarget + $paused, $calendar, $holidays),
            'paused_minutes' => $paused,
        ];
    }

    /**
     * Remaining business minutes until $deadline (negative when breached),
     * excluding pause windows that ended before now... pauses that are
     * currently open extend the deadline instead (handled via deadlinesFor).
     */
    public function remainingBusinessMinutes(Carbon $deadline, array $calendar = null, array $holidays = null): int
    {
        $now = Carbon::now(($calendar['timezone'] ?? config('app.timezone')));

        if ($now->greaterThanOrEqualTo($deadline)) {
            return -$this->businessMinutesBetween($deadline, $now, $calendar, $holidays);
        }

        return $this->businessMinutesBetween($now, $deadline, $calendar, $holidays);
    }

    // ---------- internal ----------

    protected function dayBounds(Carbon $moment, array $calendar): array
    {
        [$sh, $sm] = array_map('intval', explode(':', $calendar['start'] ?? '09:00'));
        [$eh, $em] = array_map('intval', explode(':', $calendar['end'] ?? '17:30'));
        $start = $moment->copy()->setTime($sh, $sm, 0);
        $end = $moment->copy()->setTime($eh, $em, 0);

        return [$end, $start];
    }

    protected function isWorkingMoment(Carbon $moment, array $calendar, array $holidays): bool
    {
        if (! in_array((int) $moment->format('N'), $calendar['days'] ?? [1, 2, 3, 4, 5], true)) {
            return false;
        }
        if (in_array($moment->format('Y-m-d'), $holidays, true)) {
            return false;
        }
        [$end, $start] = $this->dayBounds($moment, $calendar);

        return $moment->greaterThanOrEqualTo($start) && $moment->lessThan($end);
    }

    protected function nextWorkingStart(Carbon $moment, array $calendar, array $holidays): Carbon
    {
        $cursor = $moment->copy()->addMinute()->startOfMinute();
        $guard = 0;
        while ($guard++ < 3000) {
            [$end, $start] = $this->dayBounds($cursor, $calendar);
            $dayOk = in_array((int) $cursor->format('N'), $calendar['days'] ?? [1, 2, 3, 4, 5], true)
                && ! in_array($cursor->format('Y-m-d'), $holidays, true);
            if ($dayOk && $cursor->lessThan($start)) {
                return $start;
            }
            if ($dayOk && $cursor->greaterThanOrEqualTo($start) && $cursor->lessThan($end)) {
                return $cursor;
            }
            $cursor = $cursor->copy()->addDay()->setTime(0, 0);
        }

        return $cursor;
    }

    /**
     * Sum of business minutes the ticket spent paused (status history:
     * pending_customer periods), used to extend deadlines.
     */
    protected function pausedBusinessMinutes(Ticket $ticket, array $calendar, array $holidays): int
    {
        $policy = $this->policyFor($ticket);
        if ($policy && ! $policy->pause_on_pending_customer) {
            return 0;
        }
        $pauses = \App\Models\TicketHistory::withoutTenantScope()
            ->where('ticket_id', $ticket->id)
            ->whereIn('action', ['status_paused', 'status_resumed'])
            ->orderBy('created_at')->get();

        $total = 0;
        $openPause = null;
        foreach ($pauses as $event) {
            if ($event->action === 'status_paused') {
                $openPause = Carbon::parse($event->created_at);
            } elseif ($event->action === 'status_resumed' && $openPause) {
                $total += $this->businessMinutesBetween($openPause, Carbon::parse($event->created_at), $calendar, $holidays);
                $openPause = null;
            }
        }
        if ($openPause && in_array($ticket->status, ['pending_customer'], true)) {
            $total += $this->businessMinutesBetween($openPause, Carbon::now(), $calendar, $holidays);
        }

        return $total;
    }
}
