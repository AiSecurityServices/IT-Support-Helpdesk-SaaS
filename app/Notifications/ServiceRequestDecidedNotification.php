<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Notifications\Messages\MailMessage;

class ServiceRequestDecidedNotification extends TenantNotification
{
    public function __construct(public ServiceRequest $serviceRequest, public bool $isApprovalRequest = false)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $m = (new MailMessage)->greeting($this->brandGreeting());
        if ($this->isApprovalRequest) {
            return $m->subject("[{$this->serviceRequest->number}] Approval requested")
                ->line("Service request {$this->serviceRequest->number} ({$this->serviceRequest->item?->name}) needs your decision.")
                ->action('Review request', route('catalog.requests.show', $this->serviceRequest));
        }

        return $m->subject("[{$this->serviceRequest->number}] Request {$this->serviceRequest->status}")
            ->line("Your service request {$this->serviceRequest->number} is now {$this->serviceRequest->status}.")
            ->action('View request', route(
                $notifiable->isCustomer() ? 'portal.requests.show' : 'catalog.requests.show',
                $this->serviceRequest
            ));
    }

    public function toArray($notifiable): array
    {
        return ['kind' => 'service_request', 'request_id' => $this->serviceRequest->id,
            'number' => $this->serviceRequest->number,
            'url' => route($notifiable->isCustomer() ? 'portal.requests.show' : 'catalog.requests.show', $this->serviceRequest)];
    }
}
