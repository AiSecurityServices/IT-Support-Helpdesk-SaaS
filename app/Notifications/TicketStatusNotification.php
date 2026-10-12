<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;

class TicketStatusNotification extends TenantNotification
{
    public function __construct(public Ticket $ticket, public string $event)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->number}] Ticket {$this->event}")
            ->greeting($this->brandGreeting())
            ->line("Your support request {$this->ticket->number} — {$this->ticket->subject} is now **{$this->event}**.")
            ->action('View ticket', route('portal.tickets.show', $this->ticket));
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'ticket_'.$this->event, 'ticket_id' => $this->ticket->id,
            'number' => $this->ticket->number,
            'url' => route('portal.tickets.show', $this->ticket),
        ];
    }
}
