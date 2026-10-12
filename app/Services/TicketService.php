<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketRepliedNotification;
use App\Notifications\TicketStatusNotification;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates ticket workflow rules: validated transitions, history,
 * SLA timer events, assignment notifications. All operations run in
 * transactions so history can never diverge from ticket state.
 */
class TicketService
{
    public function create(array $data, User $actor): Ticket
    {
        return DB::transaction(function () use ($data, $actor) {
            $ticket = new Ticket($data);
            $ticket->tenant_id = $actor->tenant_id;
            $ticket->requester_id = $ticket->requester_id ?? $actor->id;
            $ticket->status = $ticket->status ?? 'new';
            if (! $ticket->sla_policy_id && ($ticket->customer?->sla_policy_id ?? null)) {
                // resolved below after customer set; safe default from customer
            }
            $ticket->save();

            if ($ticket->customer_id) {
                $customer = $ticket->customer;
                if ($customer?->sla_policy_id) {
                    $ticket->sla_policy_id = $customer->sla_policy_id;
                    $ticket->save();
                }
            }

            $this->log($ticket, $actor, 'created');
            app(SlaService::class)->startTimers($ticket);

            if ($ticket->assigned_to) {
                $this->notifyAssignee($ticket);
            }

            audit('ticket.created', $ticket, ['number' => $ticket->number]);

            $created = $ticket->fresh();
            AutomationEngine::fire('ticket.created', $this->autoContext($created));

            return $created;
        });
    }

    public function transition(Ticket $ticket, string $to, User $actor, ?string $note = null): Ticket
    {
        $allowed = config("supportflow.ticket_transitions.{$ticket->status}", []);
        abort_unless(in_array($to, $allowed, true), 422, "Transition from {$ticket->status} to {$to} is not allowed.");

        return DB::transaction(function () use ($ticket, $to, $actor, $note) {
            $from = $ticket->status;
            $ticket->status = $to;
            $now = now();
            if ($to === 'resolved' && ! $ticket->resolved_at) {
                $ticket->resolved_at = $now;
            }
            if ($to === 'closed' && ! $ticket->closed_at) {
                $ticket->closed_at = $now;
            }
            if ($to === 'open' && $from === 'closed') {
                $ticket->resolved_at = null;
                $ticket->closed_at = null;
            }
            $ticket->save();

            if ($from === 'pending_customer' && $to !== 'pending_customer') {
                $this->log($ticket, $actor, 'status_resumed', ['from' => $from, 'to' => $to]);
            }
            if ($to === 'pending_customer') {
                $this->log($ticket, $actor, 'status_paused', ['from' => $from, 'to' => $to]);
            }
            $this->log($ticket, $actor, 'status_changed', ['from' => $from, 'to' => $to, 'note' => $note]);

            app(SlaService::class)->handleTransition($ticket, $from, $to);

            // Notify requester/customer of public status changes.
            if (in_array($to, ['resolved', 'closed'], true)) {
                $this->notifyCustomer($ticket, $to);
            }

            audit('ticket.status', $ticket, ['from' => $from, 'to' => $to]);

            $fresh = $ticket->fresh();
            AutomationEngine::fire('ticket.updated', $this->autoContext($fresh, ['from_status' => $from]));

            return $fresh;
        });
    }

    public function addMessage(Ticket $ticket, User $author, string $body, string $visibility = 'public'): TicketMessage
    {
        abort_if($visibility === 'internal' && ! $author->isStaff(), 403);

        return DB::transaction(function () use ($ticket, $author, $body, $visibility) {
            $message = $ticket->messages()->create([
                'tenant_id' => $ticket->tenant_id,
                'author_id' => $author->id,
                'visibility' => $visibility,
                'body' => $body,
            ]);

            // First staff reply marks first response time.
            if ($author->isStaff() && ! $ticket->first_response_at) {
                $ticket->first_response_at = now();
                $ticket->save();
                app(SlaService::class)->markFirstResponse($ticket);
            }

            $this->log($ticket, $author, $visibility === 'internal' ? 'internal_note' : 'public_reply');

            if ($visibility === 'public') {
                $this->notifyCounterpart($ticket, $author, $message);
            }

            if ($visibility === 'public' && ! $author->isStaff()) {
                AutomationEngine::fire('customer.replied', $this->autoContext($ticket));
            }

            return $message;
        });
    }

    public function assign(Ticket $ticket, ?int $userId, ?int $teamId, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $userId, $teamId, $actor) {
            $before = ['assigned_to' => $ticket->assigned_to, 'team_id' => $ticket->team_id];
            $ticket->assigned_to = $userId;
            $ticket->team_id = $teamId;
            if (in_array($ticket->status, ['new'], true) && ($userId || $teamId)) {
                $ticket->status = 'open';
            }
            $ticket->save();

            $this->log($ticket, $actor, 'assigned', [
                'from' => $before, 'to' => ['assigned_to' => $userId, 'team_id' => $teamId],
            ]);

            if ($userId && $userId !== $actor->id) {
                $this->notifyAssignee($ticket);
            }
            audit('ticket.assigned', $ticket, ['to' => $userId, 'team' => $teamId]);

            $fresh = $ticket->fresh();
            AutomationEngine::fire('ticket.reassigned', $this->autoContext($fresh));

            return $fresh;
        });
    }

    public function merge(Ticket $primary, Ticket $secondary, User $actor): Ticket
    {
        abort_if($primary->id === $secondary->id, 422, 'Cannot merge a ticket into itself.');
        abort_if($primary->tenant_id !== $secondary->tenant_id, 403);

        return DB::transaction(function () use ($primary, $secondary, $actor) {
            foreach ($secondary->messages as $message) {
                $primary->messages()->create($message->only([
                    'tenant_id', 'author_id', 'visibility', 'body',
                ]) + ['created_at' => $message->created_at, 'updated_at' => $message->updated_at]);
            }
            foreach ($secondary->timeEntries as $entry) {
                $entry->ticket_id = $primary->id;
                $entry->save();
            }
            $this->log($primary, $actor, 'merged', ['from_ticket' => $secondary->number]);
            $this->log($secondary, $actor, 'merged_into', ['into_ticket' => $primary->number]);
            $secondary->status = 'closed';
            $secondary->closed_at = now();
            $secondary->save();
            audit('ticket.merged', $primary, ['merged' => $secondary->number]);

            return $primary->fresh();
        });
    }

    // ---------- internal ----------

    /** Automation context: flat facts about the ticket + identity for dedupe. */
    protected function autoContext(Ticket $ticket, array $extra = []): array
    {
        return array_merge([
            'tenant_id' => $ticket->tenant_id,
            'subject_id' => $ticket->id,
            'ticket_id' => $ticket->id,
            'ticket' => [
                'status' => $ticket->status, 'priority' => $ticket->priority,
                'customer_id' => $ticket->customer_id, 'team_id' => $ticket->team_id,
                'assigned_to' => $ticket->assigned_to, 'category_id' => $ticket->category_id,
            ],
            'fingerprint' => ['status' => $ticket->status, 'priority' => $ticket->priority,
                'assigned_to' => $ticket->assigned_to, 'team_id' => $ticket->team_id,
                'updated' => $ticket->updated_at?->format('YmdHi')],
        ], $extra);
    }

    protected function log(Ticket $ticket, ?User $actor, string $action, array $changes = []): void
    {
        TicketHistory::create([
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'changes' => $changes ?: null,
        ]);
    }

    protected function notifyAssignee(Ticket $ticket): void
    {
        $assignee = $ticket->assignee;
        if ($assignee) {
            $assignee->notify(new TicketAssignedNotification($ticket));
            app(WebhookService::class)->dispatch($ticket->tenant_id, 'ticket.assigned', [
                'ticket_id' => $ticket->id, 'number' => $ticket->number,
                'assigned_to' => $assignee->id,
            ]);
        }
    }

    protected function notifyCustomer(Ticket $ticket, string $event): void
    {
        $requester = $ticket->requester;
        if ($requester && $requester->id !== auth()->id()) {
            $requester->notify(new TicketStatusNotification($ticket, $event));
        }
    }

    protected function notifyCounterpart(Ticket $ticket, User $author, TicketMessage $message): void
    {
        if ($author->isStaff()) {
            // Notify requester + watchers.
            $targets = collect([$ticket->requester])->filter()
                ->merge($ticket->watchers)->unique('id')
                ->reject(fn ($u) => $u->id === $author->id);
            foreach ($targets as $target) {
                $target->notify(new TicketRepliedNotification($ticket, $message));
            }
        } else {
            // Customer replied → notify assignee + team lead + watchers.
            $targets = collect([$ticket->assignee, $ticket->team?->lead])->filter()
                ->merge($ticket->watchers)->unique('id')
                ->reject(fn ($u) => $u->id === $author->id);
            foreach ($targets as $target) {
                $target->notify(new TicketRepliedNotification($ticket, $message));
            }
        }
        app(WebhookService::class)->dispatch($ticket->tenant_id, 'ticket.replied', [
            'ticket_id' => $ticket->id, 'number' => $ticket->number,
            'author_id' => $author->id, 'visibility' => 'public',
        ]);
    }
}
