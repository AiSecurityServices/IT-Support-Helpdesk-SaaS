# Known limitations — SupportFlow Cloud

Honest boundaries. Nothing here is advertised as working.

1. **No live payment/email verification** — Stripe and SMTP need real credentials;
   the app runs in manual/log modes until `BILLING.md` / `EMAIL_DELIVERABILITY.md`
   steps are completed in staging.
2. **No SSO/SCIM** — `config/sso.php` is a disabled stub; enterprise federation is a
   separately gated capability (`ENTERPRISE_IDENTITY.md`).
3. **No inbound email-to-ticket** — blocked on mail-provider integration.
4. **Laravel 9 / PHP 8.0 ceiling** — `composer audit` advisories exist in EOL-era
   dependencies; upgrade to Laravel 10+ requires PHP 8.1+ hosting (tracked P1).
5. **SQLite-first verification** — the suite runs on sqlite; MySQL behaviour must be
   confirmed in staging (P1 gate).
6. **Accessibility** — code-level AA groundwork done; full manual AT audit outstanding.
7. **No CSP header yet** — inline CDN scripts need a nonce strategy first (P1).
8. **Single-region storage** — no configurable data residency; backups/logs/DB live
   where the host puts them (see privacy docs).
9. **No independent pen test, no certifications, no availability SLA** — targets and
   methods are documented; guarantees are not made.
10. **Spanish pack covers auth/passwords/pagination only** — full UI translation is
    incremental from here.
