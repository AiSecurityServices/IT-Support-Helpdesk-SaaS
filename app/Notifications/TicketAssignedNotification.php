<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;

class TicketAssignedNotification extends TenantNotification
{
    public function __construct(public Ticket $ticket)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->number}] Ticket assigned to you")
            ->greeting($this->brandGreeting())
            ->line("Ticket {$this->ticket->number} — {$this->ticket->subject} has been assigned to you.")
            ->line('Priority: '.ucfirst($this->ticket->priority).' · Status: '.ucfirst(str_replace('_', ' ', $this->ticket->status)))
            ->action('Open ticket', route('tickets.show', $this->ticket));
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'ticket_assigned', 'ticket_id' => $this->ticket->id,
            'number' => $this->ticket->number, 'subject' => $this->ticket->subject,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
