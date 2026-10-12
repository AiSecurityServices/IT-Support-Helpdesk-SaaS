<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class SecurityAlertNotification extends TenantNotification
{
    public function __construct(public string $message)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Security alert — '.brand('product_name'))
            ->greeting($this->brandGreeting())
            ->line($this->message)
            ->line('If this was not you, reset your password immediately and contact your administrator.');
    }

    public function toArray($notifiable): array
    {
        return ['kind' => 'security_alert', 'message' => $this->message, 'url' => route('profile.edit')];
    }
}
