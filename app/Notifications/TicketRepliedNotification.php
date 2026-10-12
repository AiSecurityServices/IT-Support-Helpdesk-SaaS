<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Notifications\Messages\MailMessage;

class TicketRepliedNotification extends TenantNotification
{
    public function __construct(public Ticket $ticket, public TicketMessage $message)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $author = $this->message->author?->name ?? 'Support';

        return (new MailMessage)
            ->subject("[{$this->ticket->number}] New reply from {$author}")
            ->greeting($this->brandGreeting())
            ->line("{$author} replied to ticket {$this->ticket->number} — {$this->ticket->subject}:")
            ->line(str($this->message->body)->limit(300))
            ->action('View conversation', route('tickets.show', $this->ticket));
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'ticket_replied', 'ticket_id' => $this->ticket->id,
            'number' => $this->ticket->number,
            'excerpt' => str($this->message->body)->limit(140),
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
