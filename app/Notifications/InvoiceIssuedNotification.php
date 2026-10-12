<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;

class InvoiceIssuedNotification extends TenantNotification
{
    public function __construct(public Invoice $invoice)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invoice {$this->invoice->number} — ".money($this->invoice->total, $this->invoice->currency))
            ->greeting($this->brandGreeting())
            ->line("A new invoice {$this->invoice->number} for ".money($this->invoice->total, $this->invoice->currency)." is due {$this->invoice->due_date?->format('d M Y')}.")
            ->action('View invoice', route('portal.invoices.show', $this->invoice));
    }

    public function toArray($notifiable): array
    {
        return [
            'kind' => 'invoice_issued', 'invoice_id' => $this->invoice->id,
            'number' => $this->invoice->number,
            'url' => route('portal.invoices.show', $this->invoice),
        ];
    }
}
