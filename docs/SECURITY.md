# Security — SupportFlow Cloud

## Implemented controls

- Tenant isolation: global query scopes + server-side policies on every controller,
  export, download, search, job and API endpoint; automated cross-tenant attack tests.
- Authentication: email verification, password reset, login throttling (5 attempts),
  10-char complex passwords, inactive-account lockout, optional TOTP 2FA
  (encrypted secrets, login challenge, rate-limited), session invalidation for
  suspended tenants, logout-other-devices on password change.
- Authorisation: least-privilege role matrix, last-owner protection, reassignment
  required before deactivation, customer portal hard-scoped to own organisation,
  internal notes excluded from portal/API/exports by construction.
- Input & files: validation everywhere, MIME/extension/size checks, randomised
  private-disk storage paths, authorised downloads only, formula-injection
  neutralisation in CSV exports, import row limits with dry-run-style error reports.
- Payments: never trust success URLs (Stripe verification), webhook signature +
  idempotency, duplicate-reference protection, no card data stored or logged.
- Platform: CSRF (except signature-verified webhooks), rate-limited auth/API,
  audit logs for sensitive actions, sanitised error pages (no traces in production),
  `APP_DEBUG=false` enforced in deployment guide, secrets only via environment.

## Threat notes & limitations

- Single-database tenancy relies on code-level scoping (tested) rather than
  database-level row security; direct database access must be restricted to operators.
- Email delivery depends on configured SMTP; `log` driver is development-only.
- Stripe test mode must be used before live keys; never commit `STRIPE_*` values.
- Backups contain personal data — encrypt and restrict per `docs/BACKUPS.md`.

## Privacy (UK GDPR readiness, not certification)

Personal-data export on request (CSV exports per module), retention policy in
`docs/BACKUPS.md`, soft-delete + archive instead of hard deletes, privacy/terms pages,
per-tenant data erasure on request after cancellation. This documents behaviour;
it is not legal advice and the product is not certified compliant.
