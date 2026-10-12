<?php

namespace App\Notifications;

use App\Models\TenantInvitation;
use Illuminate\Notifications\Messages\MailMessage;

class StaffInvitationNotification extends TenantNotification
{
    public function __construct(public TenantInvitation $invitation, public string $acceptUrl)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're invited to join {$this->invitation->tenant->name}")
            ->greeting($this->brandGreeting())
            ->line("You've been invited to join {$this->invitation->tenant->name} as {$this->invitation->role}.")
            ->action('Accept invitation', $this->acceptUrl)
            ->line('This invitation expires on '.$this->invitation->expires_at?->format('d M Y H:i').'.');
    }

    public function toArray($notifiable): array
    {
        return ['kind' => 'invitation', 'tenant' => $this->invitation->tenant->name, 'url' => $this->acceptUrl];
    }
}
