# Standards mapping — SupportFlow Cloud

Engineering verification targets (NOT certifications). Versions pinned at audit.

## OWASP Top 10:2025 → controls

| Risk | Implementation | Evidence |
|---|---|---|
| Broken access control | Tenant scopes + policies on every path; binding-time scope; SoD | Isolation suites (all modules), `SecurityRegressionTest` |
| Security misconfiguration | Secure defaults, `APP_DEBUG=false` gate, headers middleware | `SECURITY_REVIEW.md`, header tests |
| Cryptographic failures | bcrypt, encrypted TOTP secrets, hashed recovery codes, TLS-only deployment gate | Code + deployment checklist |
| Injection | Eloquent/parameterised everywhere; static `selectRaw`; validated inputs | Review + suite |
| Insecure design | SoD approvals, audited impersonation, idempotent billing | Workflow tests |
| Vulnerable components | `composer audit` in CI; Laravel 9 EOL tracked as P1 | CI step, `KNOWN_LIMITATIONS.md` |
| Auth failures | Throttling, MFA, recovery codes, session revocation, auth events | `IdentityTest`, `TwoFactorTest` |
| Data/software integrity | Signed webhooks in/out, idempotency, SBOM via `composer.lock` | Webhook tests |
| Logging failures | Audit logs, auth events, mail events, request IDs, `/up` | `PrivacyOpsTest`, admin health |
| SSRF | Outgoing-URL policy (scheme/DNS/private-range) | `SecurityRegressionTest` |

## OWASP ASVS 5.0.0 (target: Level 2, applicable controls)

In scope: V2 authentication (2.1–2.3, 2.7–2.10), V3 session (3.1–3.7),
V4 access control (4.1–4.3), V5 validation/encoding, V6 crypto, V7 errors/logging,
V8 data protection, V9 comms, V10 malicious code (file upload), V11 business logic
(SoD, billing idempotency), V13 API (13.1–13.4: authN/Z, rate limits, schema
validation), V14 configuration.
Exceptions: V1.14 (no E2E yet — HTTP render tests substitute), full 2.4 (federation
— gated, documented), V14.5 (CSP — P1 follow-up). Evidence: `SECURITY_REVIEW.md`.

## OWASP API Security Top 10

BOLA/BFLA → tenant scope + policies + negative tests; authN → Sanctum + expiry +
scopes; excess exposure → minimal serializers; rate limits → `throttle:api`;
SSRF → guard; mass assignment → `$fillable` + elevation test; inventory → `docs/API.md`.

## WCAG 2.2 (target AA)

See `ACCESSIBILITY_REPORT.md`: skip links, labelled controls, focus-visible,
reduced-motion, live regions on alerts, table scopes, 44px+ targets on primary
actions, zoom-safe layouts. Manual AT pass outstanding (P1).

## UK/EU GDPR (ICO guidance)

Lawful-basis + minimisation review, export/closure/retention implemented, processor
register, DPA template pointer, breach procedure — see `PRIVACY_AND_DATA_GOVERNANCE.md`.
Not a compliance declaration.

## NCSC SaaS guidance

User lifecycle (invite/revoke/deactivate/token purge), admin security (MFA,
impersonation), monitoring (`/up`, heartbeat), recovery (backup+verified restore),
incident response doc — mapped in `docs/OPERATIONS/INCIDENT_RESPONSE.md`.

## PCI DSS (scope minimisation)

No card data collected/stored; Stripe Checkout + hosted portal; webhooks verified;
merchant/provider/host responsibilities in `docs/BILLING.md`. No certification claim.

## Supply chain

Pinned `composer.lock`, `composer validate` + `composer audit` in CI, secret-scan
step, `.gitignore`d secrets/env/DBs, git-SHA backup manifests. Gitleaks/SBOM tooling
noted as staging-runner additions.
