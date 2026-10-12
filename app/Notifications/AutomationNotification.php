<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AutomationNotification extends TenantNotification
{
    public function __construct(public string $text, public string $url)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Automation] '.$this->text)
            ->greeting($this->brandGreeting())
            ->line($this->text)
            ->action('View', $this->url);
    }

    public function toArray($notifiable): array
    {
        return ['kind' => 'automation', 'message' => $this->text, 'url' => $this->url];
    }
}
