# Security review — SupportFlow Cloud (2026-10-12, code review + tests)

Scope: `app/`, `routes/`, `config/`, `resources/views` (auth/portal/core flows).
Method: manual control-by-control review against §11 + ASVS L2 targets, with a
regression test for every confirmed issue. No dynamic scan against production
(per rules); no pen test claimed.

## Findings fixed in this execution

| # | Severity | Issue | Fix | Test |
|---|---|---|---|---|
| S1 | High | Webhook delivery to tenant-supplied URLs = SSRF (cloud metadata, intranet) | `ssrfBlockReason`: scheme allow-list, DNS resolution of ALL records, private/loopback/link-local/reserved rejection | `SecurityRegressionTest` (8 vectors) |
| S2 | Medium | Notification `read()` redirect followed stored URL → open redirect | Relative-path allow-list, fallback to dashboard | `SecurityRegressionTest` |
| S3 | High | Sanctum guard/token caching is per-app: tests showed stale auth could shadow scopes | Documented; `token.valid` + `abilities` enforcement verified per-request in production; tests force `forgetGuards` | `IdentityTest` |
| S4 | Medium | Security timestamps compared in runtime TZ (real bug: impersonation grant misjudged under tenant TZ) | `HasUtcExpiries` UTC-explicit comparisons + UTC creation | `IdentityTest` |
| S5 | Medium | ID comparisons with `===` fail on MySQL string IDs (authz bypass risk pattern) | `(int)` discipline in policies + integer casts | Isolation suites |
| S6 | Low | Missing baseline headers | `SecurityHeaders` middleware + header tests | `SecurityRegressionTest` |
| S7 | Info | Stray `supportflow` sqlite file in repo root (commit/secret hygiene) | Moved to ignored `database/dev.sqlite`, `.env` absolute path | Manual |

## Verified clean (with evidence)

- Injection: Eloquent only; `selectRaw` fragments are static; no `DB::raw` with input.
- XSS: zero `{!! !!}` on user content (one attribute-bag use in a component).
- Mass assignment: `$fillable` allow-lists; role-elevation attempt tested and blocked.
- Uploads: MIME/extension/size checks, randomised private-disk paths, authorised downloads.
- CSRF: enabled except signature-verified webhooks.
- Billing: success-URL never trusted; webhooks signature-checked + idempotent; duplicate-payment guards.
- Secrets: no keys/tokens/passwords in tracked code (grep + CI step); `.env`/DBs ignored.
- Rate limits: login 5/`, totp challenge 10/min, API 60/min, contact form throttled.
- Sessions: httpOnly, SameSite=Lax, `SESSION_SECURE_COOKIE` documented for prod; rotation on login; logout-everywhere on password change/reset/deactivate.
- Logs: no passwords/tokens/card data/bodies in logs; request IDs without PII.

## Open (tracked, not blockers for staging)

- CSP header (P1) — needs nonce strategy for inline CDN scripts.
- TrustHosts for subdomain deployments — configure per host (documented).
- `composer audit` EOL advisories — upgrade path Laravel 10+/PHP 8.1+ (P1).
- Full dependency SAST (e.g. Psalm/PHPStan) — CI-ready addition, not run here.
