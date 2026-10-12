<?php

namespace App\Notifications;

use App\Models\MajorIncident;
use Illuminate\Notifications\Messages\MailMessage;

class MajorIncidentUpdateNotification extends TenantNotification
{
    public function __construct(public MajorIncident $incident, public string $body)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Major incident] {$this->incident->title}")
            ->greeting($this->brandGreeting())
            ->line(str($this->body)->limit(500))
            ->action('View your request', route('portal.tickets'));
    }

    public function toArray($notifiable): array
    {
        return ['kind' => 'major_incident', 'incident_id' => $this->incident->id,
            'url' => route('portal.tickets')];
    }
}
