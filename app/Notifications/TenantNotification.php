<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class TenantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        $prefs = currentTenant()?->setting('notifications', []);
        $channels = ['database'];
        $mailEnabled = $prefs['email_enabled'] ?? true;
        if ($mailEnabled && $notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function brandGreeting(): string
    {
        return 'Hello from '.brand('product_name', 'SupportFlow Cloud');
    }
}
