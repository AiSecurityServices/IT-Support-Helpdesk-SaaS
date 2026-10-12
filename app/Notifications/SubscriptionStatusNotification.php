<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionStatusNotification extends TenantNotification
{
    public function __construct(public Tenant $tenant, public string $reason)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $subjects = [
            'trial_expired' => 'Your trial has ended',
            'suspended' => 'Workspace suspended — action required',
            'payment_failed' => 'Payment failed — update your payment method',
            'refunded' => 'A payment was refunded',
            'disputed' => 'A payment is under dispute',
        ];
        $bodies = [
            'trial_expired' => 'Your free trial ended and the workspace is suspended. Choose a plan to reactivate — your data is retained.',
            'suspended' => 'Your workspace was suspended after the grace period. Pay the outstanding balance to restore access.',
            'payment_failed' => 'We could not collect payment for your subscription. Please update your payment method within the grace period.',
            'refunded' => 'A subscription payment was refunded to you. If this was unexpected, contact support.',
            'disputed' => 'A subscription payment is under dispute with your bank. Your access continues while we review; please contact support.',
        ];

        return (new MailMessage)
            ->subject('[Billing] '.($subjects[$this->reason] ?? 'Subscription update'))
            ->greeting($this->brandGreeting())
            ->line($bodies[$this->reason] ?? 'Your subscription status changed.')
            ->action('Open billing', route('billing.index'));
    }

    public function toArray($notifiable): array
    {
        return ['kind' => 'subscription_'.$this->reason, 'url' => route('billing.index')];
    }
}
