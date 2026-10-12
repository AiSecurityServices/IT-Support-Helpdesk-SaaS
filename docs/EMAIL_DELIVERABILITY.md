# Email deliverability — SupportFlow Cloud

## Sending

- `MAIL_MAILER=log` (dev) → `storage/logs`; SMTP or transactional provider in
  staging/production. All notifications send HTML + plain-text parts.
- Tenant-safe branding: sender identity from workspace settings; verified
  sender addresses required before enabling a tenant's mail.

## Domain authentication (owner action, per sending domain)

1. SPF: `v=spf1 include:<provider> -all`.
2. DKIM: publish provider keys; verify in provider console.
3. DMARC: start `p=none`, monitor, then `p=quarantine`/`reject`.
4. Test with provider seed lists + `supportflow:remind` dry runs before launch.

## Reliability

- Queued delivery with backoff + `failed_jobs` visibility; `/admin/health`
  surfaces failures; `mail_events` records provider bounces/complaints via the
  signed `/webhooks/mail` receiver (`MAIL_WEBHOOK_SECRET`).
- Notification preferences + per-tenant kill switch; automation notify actions
  capped at 25 recipients; idempotency keys prevent retry duplicates.
- A message is reported delivered only from provider confirmation, never from
  queue acceptance.

## Inbound email-to-ticket

Not implemented (blocked: no provider credentials). Requirements when built:
verified sender, tenant routing, threading tokens, duplicate detection,
attachment validation, private-note safety. Not advertised until then.
