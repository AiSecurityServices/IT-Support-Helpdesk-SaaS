<?php

namespace App\Http\Controllers;

use App\Models\MailEvent;
use Illuminate\Http\Request;

/**
 * Provider bounce/complaint receiver. The provider must sign calls with the
 * shared MAIL_WEBHOOK_SECRET; unsigned calls are rejected. Bounced addresses
 * are recorded for support diagnostics (see admin health).
 */
class MailWebhookController extends Controller
{
    public function store(Request $request)
    {
        $secret = config('mail.webhook_secret');
        abort_unless($secret && hash_equals($secret, (string) $request->header('X-Mail-Signature', '')), 403);

        $data = $request->validate([
            'recipient' => 'required|email|max:255',
            'kind' => 'required|in:bounced,complained,failed,delivered',
            'tenant_id' => 'nullable|integer',
            'provider' => 'nullable|string|max:50',
        ]);

        MailEvent::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'recipient' => $data['recipient'], 'kind' => $data['kind'],
            'provider' => $data['provider'] ?? 'smtp',
            'payload' => $request->except(['recipient', 'kind', 'tenant_id', 'provider']),
        ]);

        return response()->json(['received' => true]);
    }
}
