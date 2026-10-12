# Testing — SupportFlow Cloud

Run: `php artisan test` (isolated sqlite `:memory:` database, array mail/queue/session).

## Results (12 Oct 2026, PHP 8.0.30, Windows/XAMPP)

**157 passed, 0 failed** in ~35s (includes 9-test `DesignSystemTest` redesign suite):

| Suite | Coverage |
|---|---|
| `TenantIsolationTest` (5) | Cross-tenant URLs/APIs blocked, scoping, portal customer separation, export containment |
| `TicketWorkflowTest` (3) | Full lifecycle, customer restrictions, time/billable totals |
| `SlaTest` (4) | Weekend/holiday math, pause extension, idempotent evaluation |
| `InvoiceTest` (2) | Decimal totals/tax/discount, payments, idempotency, PDF |
| `SubscriptionTest` (4) | Registration provisioning, plan change/cancel, webhook verify+dedupe, seat limits |
| `PortalAndApiTest` (3) | Portal end-to-end, token API lifecycle, assets/import |
| `TwoFactorTest` (2) | TOTP enable, login challenge, disable (+recovery codes page) |
| `PagesRenderTest` (45+) | Every staff/portal/admin/marketing screen renders 200 (incl. KB, catalogue, ITSM, CMDB, automation, tokens, impersonation) |
| `ItsmKnowledgeTest` (3) | KB lifecycle, published+public invariant, feedback, cross-tenant block |
| `ItsmCatalogTest` (3) | Schema validation, conditionals, SoD approvals, fulfil/confirm, isolation |
| `ItsmWorkflowsTest` (3) | Problems, change lifecycle + locks, major incidents + audience notify |
| `CmdbAutomationTest` (4) | CMDB edges/impact, rule firing + idempotency, notify caps, permissions |
| `IdentityTest` (9) | Super-admin + tenant MFA, recovery codes, token scopes/expiry/revoke, service accounts, deactivation purge, invitation revoke, auth events, impersonation cycle |
| `PrivacyOpsTest` (7) | Personal export, closure, retention, mail webhook, `/up` probe, locale, security settings |
| `SecurityRegressionTest` (5) | SSRF policy, blocked delivery log, redirect guard, headers, role elevation |
| `PerformanceTest` (2) | 300-ticket dashboard/list timing, N+1 query guard |
| `SubscriptionLifecycleTest` (8) | Trial/grace expiry, period-end cancel, reactivation, proration, renewal/refund/dispute/sync webhooks |
| `OpsFeaturesTest` (6) | Feature flags, onboarding, tenant export, append-only audit, queued webhooks |
| `AccessibilityTest` (16) | Lang/title/landmarks/labels/alt/names/table structure across 19 pages |
| `MailDeliveryTest` (2) | Template preview, safe test send, recipient validation |
| `DesignSystemTest` (9) | New identity markers, 3D hero wiring + fallbacks, nav completeness, no-emoji rule, public/app/portal/auth shells, theme hooks |
| Breeze `Auth/*` + `ExampleTest` | Login, registration (extended), password reset/confirm, email verify |

## How to run subsets

```bat
php artisan test --filter=TenantIsolationTest
php artisan test --filter=SlaTest
```

## Known honest gaps (not executed)

- No browser/end-to-end automation (no Selenium/Playwright in this environment);
  covered instead by HTTP-level render tests for every screen.
- No live Stripe/SMTP verification (no credentials) — webhook logic tested with
  correctly-signed fixtures; live mode requires `docs/BILLING.md` steps.
- `composer audit` reports 22 advisories in Laravel 9-era dependencies
  (framework EOL); upgrade path is Laravel 10+ on PHP 8.1+ when hosting allows.

## Adding tests

Use `tests/MakesTenants.php` (`seedPlans` + `makeTenant` + `makeUser`) for
tenant-aware fixtures. Never hit real payment or mail providers from tests.
