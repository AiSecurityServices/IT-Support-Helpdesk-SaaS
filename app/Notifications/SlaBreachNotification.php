<?php

namespace App\Notifications;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;

class SlaBreachNotification extends TenantNotification
{
    public function __construct(public Ticket $ticket, public string $target, public Carbon $deadline)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->number}] SLA BREACH: {$this->target} deadline missed")
            ->greeting($this->brandGreeting())
            ->line("Ticket {$this->ticket->number} has breached its ".str_replace('_', ' ', $this->target)." deadline ({$this->deadline->format('d M Y H:i')}). It has been escalated to level {$this->ticket->escalation_level}.")
            ->action('Open ticket', route('tickets.show', $this->ticket));
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'sla_breach', 'ticket_id' => $this->ticket->id,
            'number' => $this->ticket->number, 'target' => $this->target,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
