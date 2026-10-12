<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reusable workflow automation: triggers → conditions → actions + history.
 *
 * Safety properties:
 * - Rules are tenant-scoped; a rule can only touch its own tenant's records.
 * - Idempotent: identical events produce one run (unique dedupe key).
 * - Loop-proof: actions executed by the engine never re-fire the engine.
 * - Rate-limited per rule per hour; failures recorded, never fatal to callers.
 */
class AutomationEngine
{
    protected static bool $executing = false;

    public static function fire(string $trigger, array $context): void
    {
        if (self::$executing) {
            return; // loop prevention: engine actions don't cascade
        }

        $tenantId = (int) ($context['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return;
        }

        $rules = AutomationRule::withoutTenantScope()->where('tenant_id', $tenantId)
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            try {
                self::runRule($rule, $trigger, $context);
            } catch (\Throwable $e) {
                Log::warning('Automation rule failed', ['rule' => $rule->id, 'error' => $e->getMessage()]);
                self::record($rule, $tenantId, $context, 'failed', ['error' => $e->getMessage()]);
            }
        }
    }

    protected static function runRule(AutomationRule $rule, string $trigger, array $context): void
    {
        $tenantId = $rule->tenant_id;

        // Rate limit per rule.
        $recent = AutomationRun::withoutTenantScope()->where('automation_rule_id', $rule->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recent >= $rule->max_runs_per_hour) {
            return;
        }

        // Conditions (all must match; empty = always).
        foreach ($rule->conditions ?? [] as $cond) {
            if (! self::conditionMatches($cond, $context)) {
                self::record($rule, $tenantId, $context, 'skipped', ['reason' => 'conditions']);

                return;
            }
        }

        // Idempotency key: same rule + trigger + subject + payload = one run.
        $subjectId = $context['subject_id'] ?? null;
        $dedupe = sha1($rule->id.'|'.$trigger.'|'.$subjectId.'|'.json_encode($context['fingerprint'] ?? []));

        if (AutomationRun::withoutTenantScope()->where('automation_rule_id', $rule->id)->where('dedupe_key', $dedupe)->exists()) {
            return; // retried job / duplicate event: safe no-op
        }

        self::$executing = true;
        $results = [];
        try {
            foreach ($rule->actions ?? [] as $action) {
                $results[] = self::executeAction($rule, $action, $context);
            }
        } finally {
            self::$executing = false;
        }

        self::record($rule, $tenantId, $context, 'ok', ['actions' => $results], $dedupe);
        $rule->update(['last_run_at' => now(), 'run_count' => $rule->run_count + 1]);
        audit('automation.ran', $rule, ['trigger' => $trigger, 'subject' => $subjectId]);
    }

    protected static function conditionMatches(array $cond, array $context): bool
    {
        $actual = data_get($context, $cond['field'] ?? '');
        $expected = $cond['value'] ?? null;

        return match ($cond['operator'] ?? 'eq') {
            'eq' => $actual == $expected,
            'neq' => $actual != $expected,
            'gt' => $actual > $expected,
            'gte' => $actual >= $expected,
            'lt' => $actual < $expected,
            'lte' => $actual <= $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            'in' => in_array($actual, (array) $expected, false),
            default => false,
        };
    }

    protected static function executeAction(AutomationRule $rule, array $action, array $context): array
    {
        $tenantId = $rule->tenant_id;
        $ticket = isset($context['ticket_id'])
            ? Ticket::withoutTenantScope()->where('tenant_id', $tenantId)->find($context['ticket_id'])
            : null;

        return match ($action['type'] ?? '') {
            'assign_team' => self::doAssign($ticket, null, (int) ($action['params']['team_id'] ?? 0), $rule),
            'assign_technician' => self::doAssign($ticket, (int) ($action['params']['user_id'] ?? 0), null, $rule),
            'set_priority' => self::doSetField($ticket, 'priority', $action['params']['value'] ?? null, ['low', 'normal', 'high', 'urgent']),
            'add_label' => self::doAddLabel($ticket, (string) ($action['params']['label'] ?? '')),
            'escalate' => self::doEscalate($ticket),
            'notify' => self::doNotify($rule, $ticket, $action['params'] ?? [], $context),
            'webhook' => self::doWebhook($rule, $action['params'] ?? [], $context),
            'internal_note' => self::doNote($ticket, (string) ($action['params']['body'] ?? '')),
            default => ['action' => $action['type'] ?? '?', 'result' => 'unknown_action_skipped'],
        };
    }

    protected static function ticketOrFail(?Ticket $ticket): Ticket
    {
        abort_unless($ticket, 422, 'Automation action needs a ticket subject.');

        return $ticket;
    }

    protected static function doAssign(?Ticket $ticket, ?int $userId, ?int $teamId, AutomationRule $rule): array
    {
        $ticket = self::ticketOrFail($ticket);
        DB::transaction(function () use ($ticket, $userId, $teamId, $rule) {
            if ($userId) {
                abort_unless(User::where('tenant_id', $rule->tenant_id)->where('id', $userId)->whereIn('role', User::STAFF_ROLES)->exists(), 422);
                $ticket->assigned_to = $userId;
            }
            if ($teamId) {
                abort_unless(\App\Models\Team::withoutTenantScope()->where('tenant_id', $rule->tenant_id)->where('id', $teamId)->exists(), 422);
                $ticket->team_id = $teamId;
            }
            $ticket->save();
        });

        return ['action' => 'assign', 'user' => $userId, 'team' => $teamId];
    }

    protected static function doSetField(?Ticket $ticket, string $field, $value, array $allowed): array
    {
        $ticket = self::ticketOrFail($ticket);
        abort_unless(in_array($value, $allowed, true), 422, 'Automation: invalid value.');
        $ticket->{$field} = $value;
        $ticket->save();

        return ['action' => 'set_'.$field, 'value' => $value];
    }

    protected static function doAddLabel(?Ticket $ticket, string $label): array
    {
        $ticket = self::ticketOrFail($ticket);
        $label = trim(preg_replace('/[^a-z0-9\-_ ]/i', '', $label));
        abort_if($label === '', 422, 'Automation: empty label.');
        $tags = $ticket->tagList();
        if (! in_array($label, $tags, true)) {
            $tags[] = $label;
            $ticket->tags = implode(', ', $tags);
            $ticket->save();
        }

        return ['action' => 'add_label', 'label' => $label];
    }

    protected static function doEscalate(?Ticket $ticket): array
    {
        $ticket = self::ticketOrFail($ticket);
        $ticket->escalation_level = min(5, (int) $ticket->escalation_level + 1);
        $ticket->save();

        return ['action' => 'escalate', 'level' => $ticket->escalation_level];
    }

    protected static function doNotify(AutomationRule $rule, ?Ticket $ticket, array $params, array $context): array
    {
        $audience = $params['audience'] ?? 'assignee'; // assignee|team|managers|requester
        $message = substr((string) ($params['message'] ?? 'Automation notification'), 0, 500);
        $targets = collect();

        if ($ticket) {
            $targets = match ($audience) {
                'assignee' => collect([$ticket->assignee]),
                'team' => $ticket->team?->members ?? collect(),
                'requester' => collect([$ticket->requester]),
                default => collect(),
            };
        }
        if ($audience === 'managers') {
            $targets = User::where('tenant_id', $rule->tenant_id)
                ->whereIn('role', ['owner', 'admin', 'manager'])->where('is_active', true)->get();
        }

        $sent = 0;
        foreach ($targets->filter()->unique('id') as $user) {
            $user->notify(new \App\Notifications\AutomationNotification(
                $rule->name.': '.$message,
                $ticket ? route('tickets.show', $ticket) : route('dashboard')
            ));
            $sent++;
            if ($sent >= 25) {
                break; // notification-loop protection
            }
        }

        return ['action' => 'notify', 'audience' => $audience, 'sent' => $sent];
    }

    protected static function doWebhook(AutomationRule $rule, array $params, array $context): array
    {
        $endpoint = \App\Models\WebhookEndpoint::withoutTenantScope()->where('tenant_id', $rule->tenant_id)
            ->where('id', $params['endpoint_id'] ?? 0)->where('is_active', true)->first();
        abort_unless($endpoint, 422, 'Automation: webhook endpoint not found.');
        app(WebhookService::class)->deliver($endpoint, 'automation.'.$rule->trigger, [
            'rule' => $rule->name, 'subject_id' => $context['subject_id'] ?? null,
        ]);

        return ['action' => 'webhook', 'endpoint' => $endpoint->id];
    }

    protected static function doNote(?Ticket $ticket, string $body): array
    {
        $ticket = self::ticketOrFail($ticket);
        abort_if(trim($body) === '', 422, 'Automation: empty note.');
        $ticket->messages()->create([
            'tenant_id' => $ticket->tenant_id, 'author_id' => null,
            'visibility' => 'internal', 'body' => '[Automation] '.$body,
        ]);

        return ['action' => 'internal_note'];
    }

    protected static function record(AutomationRule $rule, int $tenantId, array $context, string $status, array $detail = [], ?string $dedupe = null): void
    {
        try {
            AutomationRun::create([
                'automation_rule_id' => $rule->id, 'tenant_id' => $tenantId,
                'subject_id' => $context['subject_id'] ?? null,
                'status' => $status, 'detail' => $detail ?: null,
                'dedupe_key' => $dedupe ?? sha1($rule->id.'|'.microtime(true).'|'.random_int(0, PHP_INT_MAX)),
            ]);
        } catch (\Throwable $e) {
            // Dedupe race: another worker recorded the same event first — safe no-op.
            Log::info('Automation run already recorded', ['rule' => $rule->id]);
        }
    }
}
