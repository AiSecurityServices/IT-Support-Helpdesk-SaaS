# Requirements traceability — SupportFlow Cloud

Statuses: **IV** = Implemented and verified · **IU** = Implemented but unverified ·
**P** = Partially implemented · **M** = Missing · **B** = Blocked by external config.

## Original specification (core product)

| # | Requirement | Status | Code | Evidence |
|---|---|---|---|---|
| C1 | Multi-tenant isolation (tenant_id, middleware, policies) | IV | `TenantScope`, `SetTenantContext`, policies | `TenantIsolationTest` (5), full suite |
| C2 | Auth: register/login/verify/reset/invitations/avatars | IV | Breeze + `RegisteredUserController`, `ProfileController` | `Auth/*` tests, `SubscriptionTest` |
| C3 | RBAC owner/admin/manager/technician/customer + super-admin | IV | `EnsureRole`, `EnsureSuperAdmin`, policies | `PagesRenderTest`, `IdentityTest` |
| C4 | Marketing site + pricing + legal pages | IV | `MarketingController`, views | `PagesRenderTest`, live 200s |
| C5 | Customers/contacts/locations, CSV import/export | IV | `CustomerController` | prior suite + render tests |
| C6 | Tickets: lifecycle, queues, replies/notes, merge, watchers, bulk | IV | `TicketService`, `TicketController` | `TicketWorkflowTest` |
| C7 | SLA engine + escalation jobs | IV | `SlaCalculator`, `SlaService`, `sla:evaluate` | `SlaTest` (4) |
| C8 | Teams/technicians/time tracking | IV | `Team/Technician/TimeEntryController` | prior + render tests |
| C9 | Assets + warranty + import/export | IV | `AssetController` | `PortalAndApiTest` |
| C10 | Remote support records + consent | IV | `RemoteSessionController` | render tests |
| C11 | Customer portal (scoped) | IV | `PortalController` | `PortalAndApiTest`, isolation |
| C12 | Notifications (in-app + mail, queued-capable) | IV | notifications, `SendReminders` | workflow tests (fake) |
| C13 | Service invoices/payments/PDF | IV | `InvoiceController`, DomPDF | `InvoiceTest` |
| C14 | SaaS plans/trials/checkout/webhooks/limits | IV | `BillingController`, `PlanSeeder` | `SubscriptionTest` |
| C15 | Reports + CSV exports | IV | `ReportController` | render tests |
| C16 | Super-admin dashboard | IV | `AdminController` | render tests |
| C17 | REST API v1 (Sanctum) | IV | `routes/api.php` | `PortalAndApiTest`, `IdentityTest` |
| C18 | TOTP MFA | IV | `TwoFactorController`, Google2FA | `TwoFactorTest` |
| C19 | Docs (README, install, billing, deploy, backups, testing) | IV | `docs/*` | this pack |

## This prompt (§4 ITSM gaps)

| # | Requirement | Status | Code | Evidence |
|---|---|---|---|---|
| G1 | Knowledge base (states, versions, feedback, suggestions) | IV | `KnowledgeBaseController`, mig 000011 | `ItsmKnowledgeTest` (3) |
| G2 | Service catalogue + approvals + tasks + evidence | IV | `ServiceCatalogController` | `ItsmCatalogTest` (3) |
| G3 | Problem / Change / Major incident + SoD | IV | 3 controllers, audited approvals | `ItsmWorkflowsTest` (3) |
| G4 | CMDB relationships + impact view | IV | `CmdbController`, `CiRelationship` | `CmdbAutomationTest` |
| G5 | Automation engine (triggers/conditions/actions/history) | IV | `AutomationEngine` | `CmdbAutomationTest` (3) |

## This prompt (§5 identity)

| # | Requirement | Status | Code | Evidence |
|---|---|---|---|---|
| I1 | Mandatory super-admin MFA | IV | `EnsureSuperAdmin` | `IdentityTest` |
| I2 | Tenant MFA enforcement | IV | login + `settings.security` | `IdentityTest`, `PrivacyOpsTest` |
| I3 | Recovery codes (hashed, single-use) | IV | `TwoFactorController`, `recovery_codes` | `IdentityTest`, `TwoFactorTest` |
| I4 | Session revocation on password/reset/deactivate | IV | `logoutOtherDevices`, token purge | `IdentityTest` |
| I5 | Auth event history + suspicious logging | IV | `AuthEvent`, login hooks | `IdentityTest` |
| I6 | Invitation expiry/revocation | IV | `TechnicianController` | `IdentityTest` |
| I7 | Scoped API tokens (expiry/revoke/last-used) + service accounts | IV | `ApiTokenController`, `token.valid` | `IdentityTest` |
| I8 | Audited time-limited impersonation | IV | `ImpersonationController`, middleware, banner | `IdentityTest` |
| I9 | OIDC/SAML/SCIM federation | M (gated) | `config/sso.php` stub only | `docs/ENTERPRISE_IDENTITY.md` — honest non-claim |

## Cross-cutting (§6–§17)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| X1 | Tenant isolation incl. jobs/exports/downloads | IV | 5 isolation tests + SoD/negative tests in every new suite |
| X2 | i18n: locale, TZ policy, currency, es pack | IV (foundation) | `PrivacyOpsTest` (locale, strings); `HasUtcExpiries` |
| X3 | WCAG 2.2 AA target | P | `ACCESSIBILITY_REPORT.md` — automated scan n/a, manual pass recorded |
| X4 | Email deliverability (SPF/DKIM guidance, bounces, prefs) | IV (app) / B (provider) | `MailWebhookController`, `mail_events`, `EMAIL_DELIVERABILITY.md`, `MailTestController` (preview + safe test send), `MailDeliveryTest` |
| X5 | Privacy/export/retention/closure | IV | `PrivacyController`, `supportflow:retention`, `PrivacyOpsTest` |
| X6 | Security review + regression tests | IV | `SECURITY_REVIEW.md`, `SecurityRegressionTest` |
| X7 | Backup + verified restore | IV | `supportflow:backup`, `supportflow:backup-verify` PASSED 2026-10-12 |
| X8 | Monitoring (`/up`, request IDs, heartbeat, mail failures) | IV | `HealthController`, `AddRequestId`, `PrivacyOpsTest` |
| X9 | CI pipeline | IV | `.github/workflows/ci.yml` (runners needed to execute) |
| X10 | Performance baseline | IV | `PerformanceTest`: 70ms/36ms/26q; `PERFORMANCE_REPORT.md` |
| X11 | Inbound email-to-ticket | M (blocked) | No provider credentials; documented, not advertised |
| X12 | Staging env + live Stripe/SMTP verification | B | `docs/BILLING.md`, `DEPLOYMENT.md` steps; fixtures used in tests |
| X13 | Pen test / certification / availability SLA | M (external) | Explicitly not claimed; see `KNOWN_LIMITATIONS.md` |

## Release 1.1.0 additions (2026-10-12, this execution)

| # | Requirement | Status | Code | Evidence |
|---|---|---|---|---|
| R1 | Subscription lifecycle: trial expiry, grace expiry, period-end cancel, reactivation | IV | `SubscriptionService`, `supportflow:subscriptions` (daily), mig 000013 | `SubscriptionLifecycleTest` (8) |
| R2 | Proration credits on plan changes (decimal-safe) | IV | `SubscriptionService::prorationCredit`, `proration_credit` events | `SubscriptionLifecycleTest` |
| R3 | Renewal / refund / dispute / sync webhooks | IV | `BillingController` handlers + tenant resolver | `SubscriptionLifecycleTest` (signed fixtures, duplicates) |
| R4 | Suspended-workspace billing access (recovery path) | IV | `SetTenantContext` billing exception | `SubscriptionLifecycleTest` |
| R5 | Feature flags + staged rollout UI | IV | `config/features.php`, `feature()`, settings UI, nav gating | `OpsFeaturesTest` |
| R6 | Onboarding checklist | IV | `OnboardingChecklist`, dashboard widget | `OpsFeaturesTest` |
| R7 | Owner workspace export (chunked JSON, no internal notes) | IV | `PrivacyController@exportTenant` | `OpsFeaturesTest` (scoping) |
| R8 | Append-only audit log | IV | `AuditLog` model guards | `OpsFeaturesTest` |
| R9 | Queued webhooks with backoff + retry cap | IV | `DeliverWebhook` job, `WebhookService` dispatch | `OpsFeaturesTest` (Http::fake) |
| R10 | Automated a11y structural checks | IV | `AccessibilityTest` (16 checks × 19 pages) + label/landmark fixes | Suite green |
| R11 | Security headers middleware | IV | `SecurityHeaders` | `SecurityRegressionTest` |
| R12 | Tenant-namespaced cache keys, request-id audit correlation | IV | `tenant_cache_key()`, `audit()` meta | Code + `PrivacyOpsTest` |
| R13 | CHANGELOG + app version | IV | `CHANGELOG.md`, `config/app.php` version | `/up` exposes version |
