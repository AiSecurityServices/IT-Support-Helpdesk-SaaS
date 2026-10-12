<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outgoing webhook attempt with backoff (60s, 5m, 15m, 1h) and a 5-try cap.
 * Delivery rows are created upfront by WebhookService so replays and the
 * admin health screen share one source of truth. With QUEUE_CONNECTION=sync
 * (local default) this runs inline; workers retry automatically otherwise.
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->delivered) {
            return; // already delivered (e.g. duplicate dispatch) — safe no-op
        }
        $endpoint = WebhookEndpoint::find($delivery->webhook_endpoint_id);
        if (! $endpoint || ! $endpoint->is_active) {
            $delivery->status_code = null;
            $delivery->delivered = false;
            $delivery->save();

            return;
        }

        $service = app(\App\Services\WebhookService::class);
        $blockReason = $service->ssrfBlockReason($endpoint->url);
        if ($blockReason) {
            Log::warning('Webhook blocked (SSRF policy)', ['endpoint' => $endpoint->id, 'reason' => $blockReason]);
            $delivery->delivered = false;
            $delivery->save();

            return;
        }

        $delivery->attempts = $delivery->attempts + 1;
        $delivery->save();

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-SupportFlow-Event' => $delivery->event,
                    'X-SupportFlow-Signature' => 'sha256='.hash_hmac('sha256', $this->signedBody($delivery, $endpoint), $endpoint->secret),
                    'X-Idempotency-Key' => $this->idempotencyKey($delivery),
                ])->withBody($this->signedBody($delivery, $endpoint), 'application/json')->post($endpoint->url);

            $delivery->status_code = $response->status();
            $delivery->delivered = $response->successful();
            $delivery->save();

            if (! $response->successful()) {
                throw new \RuntimeException('Webhook endpoint returned '.$response->status());
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook delivery failed', ['endpoint' => $endpoint->id, 'attempt' => $delivery->attempts, 'error' => $e->getMessage()]);
            $delivery->status_code = null;
            $delivery->delivered = false;
            $delivery->save();
            if ($delivery->attempts < $this->tries) {
                $this->release($this->backoff()[min($delivery->attempts - 1, 3)]);
            }
            // On the final attempt the row stays failed (admin health shows it);
            // with real workers the exception below also lands in failed_jobs.
            throw $e;
        }
    }

    protected function idempotencyKey(WebhookDelivery $delivery): string
    {
        return hash('sha256', $delivery->webhook_endpoint_id.'|'.$delivery->event.'|'.json_encode($delivery->payload));
    }

    protected function signedBody(WebhookDelivery $delivery, WebhookEndpoint $endpoint): string
    {
        return json_encode(['event' => $delivery->event, 'data' => $delivery->payload, 'idempotency_key' => $this->idempotencyKey($delivery)]);
    }
}
