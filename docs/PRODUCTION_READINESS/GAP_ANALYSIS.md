# Gap analysis — SupportFlow Cloud

Audit date: 2026-10-12. Baseline before this phase: 72 passing tests, core
helpdesk complete, none of §4–§5 enterprise/ITSM items present (verified by
codebase grep: no knowledge/catalogue/problem/change/automation/token code).

## Closed in this execution (implemented + tested)

1. **Knowledge base** — states, versioning, feedback, suggestions, portal library,
   strict published+public invariant (`ItsmKnowledgeTest`).
2. **Service catalogue** — schema-validated forms, conditional questions, approvals
   with separation of duties, tasks, evidence, confirmation (`ItsmCatalogTest`).
3. **Problem/Change/Major incident** — audited transitions, SoD, audience updates
   (`ItsmWorkflowsTest`).
4. **CMDB** — typed tenant-scoped edges, impact/blast-radius view (`CmdbAutomationTest`).
5. **Automation engine** — 10 triggers, conditions, 8 actions, idempotency, rate
   limits, loop guard, dry-run, run history (`CmdbAutomationTest`).
6. **Identity** — mandatory super-admin MFA, tenant MFA enforcement, recovery codes,
   auth events, invitation revocation, token scopes/expiry/revocation, service
   accounts, audited impersonation (`IdentityTest`, extended `TwoFactorTest`).
7. **Isolation deepening** — binding-time tenant scope (middleware before
   `SubstituteBindings`), ambient-scope-free background engine, `(int)` policy
   discipline (MySQL string IDs).
8. **Timezone integrity** — `HasUtcExpiries` UTC-explicit security timestamps after
   finding a real TZ-shift bug in impersonation grants.
9. **Security** — security headers, SSRF guard on webhooks, open-redirect guard,
   secret-scan CI step, SBOM note (`SecurityRegressionTest`).
10. **Ops** — `/up` probe, request IDs, scheduler heartbeat, mail bounce receiver,
    real `supportflow:backup` + verified restore (PASSED 2026-10-12), retention
    command, privacy export/closure, CI workflow.
11. **i18n foundation** — user locale, es pack, UTC policy, currency display rules.

## Closed in release 1.1.0 (2026-10-12, this execution)

12. **Subscription lifecycle** — trial/grace expiry, period-end cancellation,
    reactivation, proration credits, renewal/refund/dispute/sync webhooks,
    suspended-workspace billing access (`SubscriptionLifecycleTest`, 8 tests).
13. **Operational readiness** — feature flags, onboarding checklist, tenant export,
    append-only audit, queued webhooks with backoff (`OpsFeaturesTest`).
14. **A11y automation + label association** — 16 structural checks; `x-field`
    labels associated; skip links/landmarks on all layouts (`AccessibilityTest`).
15. **Mail ops** — template preview + safe test send (`MailDeliveryTest`).
16. **Security headers** — baseline middleware + tests.

## Remaining gaps (owned, with references)

| Gap | Severity | Reference |
|---|---|---|
| Live Stripe/SMTP verification | P1 | `BILLING.md`, `EMAIL_DELIVERABILITY.md` — needs credentials + staging |
| SSO (OIDC/SAML) + SCIM | P2 (gated) | `ENTERPRISE_IDENTITY.md` — by design, not advertised |
| Inbound email-to-ticket | P2 | Blocked on provider; not advertised anywhere |
| Full WCAG 2.2 AA audit (manual AT pass) | P1 | `ACCESSIBILITY_REPORT.md` |
| CSP headers | P1 | inline CDN scripts need nonce strategy; tracked |
| Independent pen test / certifications | P2 (external) | `KNOWN_LIMITATIONS.md` |
| MySQL production-path verification | P1 | Suite runs on sqlite; MySQL check in staging gate |
| Load test beyond 300-ticket baseline | P2 | Method in `PERFORMANCE_REPORT.md` |

No gap that is advertised as available is left unimplemented.
