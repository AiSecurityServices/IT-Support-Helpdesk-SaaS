<?php

namespace App\Notifications;

use App\Models\Asset;
use Illuminate\Notifications\Messages\MailMessage;

class WarrantyExpiringNotification extends TenantNotification
{
    public function __construct(public Asset $asset)
    {
    }

    public function via($notifiable): array
    {
        // De-duplicate: one reminder per asset per day (tenant-namespaced key).
        $key = tenant_cache_key("warranty-reminded:{$this->asset->id}:".now()->format('Y-m-d'), $this->asset->tenant_id);
        if (cache()->has($key)) {
            return [];
        }
        cache()->put($key, true, now()->addDay());

        return parent::via($notifiable);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Warranty expiring: {$this->asset->name}")
            ->greeting($this->brandGreeting())
            ->line("Asset {$this->asset->asset_tag} — {$this->asset->name} warranty expires on {$this->asset->warranty_expires_at?->format('d M Y')}.")
            ->action('View asset', route('assets.show', $this->asset));
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'warranty_expiring', 'asset_id' => $this->asset->id,
            'name' => $this->asset->name,
            'url' => route('assets.show', $this->asset),
        ];
    }
}
