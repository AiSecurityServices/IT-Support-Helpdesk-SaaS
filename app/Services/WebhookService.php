<?php

namespace App\Services;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Signed outgoing webhooks with delivery logging, retries and
 * idempotency keys. Failures never break the originating workflow.
 */
class WebhookService
{
    public function dispatch(int $tenantId, string $event, array $payload): void
    {
        $endpoints = WebhookEndpoint::where('tenant_id', $tenantId)
            ->where('is_active', true)->get()
            ->filter(fn ($e) => in_array($event, $e->events ?? [], true)
                || in_array('*', $e->events ?? [], true));

        foreach ($endpoints as $endpoint) {
            $this->deliver($endpoint, $event, $payload);
        }
    }

    public function deliver(WebhookEndpoint $endpoint, string $event, array $payload): void
    {
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id, 'event' => $event,
            'payload' => $payload, 'attempts' => 0,
        ]);

        // Queued with backoff + retry cap; runs inline when QUEUE_CONNECTION=sync.
        // Failures never break the originating workflow.
        try {
            \App\Jobs\DeliverWebhook::dispatch($delivery->id);
        } catch (\Throwable $e) {
            Log::warning('Webhook dispatch raised', ['endpoint' => $endpoint->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Returns a block reason when a webhook URL is unsafe to fetch:
     * non-http(s) scheme, unresolvable host, or any resolved IP in a
     * private/loopback/link-local/reserved range (incl. DNS-rebinding
     * mitigation by checking ALL resolved addresses).
     */
    public function ssrfBlockReason(string $url): ?string
    {
        $parts = parse_url($url);
        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return 'scheme_not_allowed';
        }
        $host = $parts['host'] ?? '';
        if ($host === '') {
            return 'missing_host';
        }
        // Literal IPs: check directly (no DNS to rebind).
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->ipBlocked($host) ? 'private_ip_literal' : null;
        }
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if ($records === false || $records === []) {
            return 'unresolvable_host';
        }
        foreach ($records as $rec) {
            $ip = $rec['ip'] ?? $rec['ipv6'] ?? null;
            if ($ip && $this->ipBlocked($ip)) {
                return 'private_ip_resolved';
            }
        }

        return null;
    }

    protected function ipBlocked(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
        // FILTER_FLAG_NO_RES_RANGE misses some; explicit loopback/link-local nets:
        foreach (['127.', '10.', '169.254.', '192.168.', '::1', 'fe80:', 'fc00:', 'fd00:'] as $prefix) {
            if (stripos($ip, $prefix) === 0) {
                return true;
            }
        }
        if (preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip)) {
            return true;
        }

        return false;
    }
}
