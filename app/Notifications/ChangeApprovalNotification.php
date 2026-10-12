<?php

namespace App\Notifications;

use App\Models\Change;
use Illuminate\Notifications\Messages\MailMessage;

class ChangeApprovalNotification extends TenantNotification
{
    public function __construct(public Change $change)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Change] Approval requested: {$this->change->title}")
            ->greeting($this->brandGreeting())
            ->line("Change '{$this->change->title}' ({$this->change->type}, risk: {$this->change->risk}) needs your decision.")
            ->action('Review change', route('changes.show', $this->change));
    }

    public function toArray($notifiable): array
    {
        return ['kind' => 'change_approval', 'change_id' => $this->change->id,
            'url' => route('changes.show', $this->change)];
    }
}
