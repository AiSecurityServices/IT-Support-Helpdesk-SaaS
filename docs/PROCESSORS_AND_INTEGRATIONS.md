# Processors & integrations — SupportFlow Cloud

## Sub-processors (register — confirm before contracts)

| Processor | Purpose | Data | Basis pointer |
|---|---|---|---|
| Hosting provider (owner-chosen) | Compute/storage/DB | All workspace data | DPA with host |
| SMTP / transactional mail (owner-chosen) | Email delivery | Recipients + message content | DPA; SPF/DKIM/DMARC per `EMAIL_DELIVERABILITY.md` |
| Stripe (if enabled) | Subscription + invoice payments | Billing identifiers, amounts | Stripe DPA; no card data touches the app |
| Backup storage (owner-chosen) | Recovery copies | DB + file snapshots | Encryption + access control |

## Integrations (status)

- Outgoing webhooks: HMAC-signed, SSRF-guarded, logged (`docs/API.md`).
- Mail bounce receiver: shared-secret endpoint → `mail_events`.
- Stripe: verified webhooks, idempotent (`docs/BILLING.md`).
- SSO/SCIM: NOT available — gated enterprise capability, no data shared.

## DPA template pointer

Use your counsel's data-processing addendum covering: subject matter, duration,
nature/purpose, data categories (see privacy doc), sub-processors above,
security measures (see `SECURITY.md`), breach notification, audit assistance,
return/deletion. This file is a register, not an agreement.
