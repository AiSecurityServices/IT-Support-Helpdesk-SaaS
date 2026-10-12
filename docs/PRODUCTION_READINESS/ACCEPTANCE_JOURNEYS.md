# End-to-end acceptance journeys — SupportFlow Cloud

Each journey: expected result, actual result, test evidence, unresolved issues.
Staging-only journeys (F-live, J) record procedure + entry criteria instead of results.

## Journey A — Tenant onboarding ✅
Expected: register → workspace + trial + owner; configure; invite techs; team; access rules hold.
Actual: as expected. Evidence: `SubscriptionTest::test_registration_creates_tenant_trial_and_defaults`,
`OpsFeaturesTest::test_onboarding_checklist_tracks_real_state`, `IdentityTest` (invitations),
`PagesRenderTest` (settings/billing render). Unresolved: none at app level.

## Journey B — Customer-to-technician support ✅
Expected: portal ticket → assign → public reply visible / internal note hidden.
Actual: as expected. Evidence: `PortalAndApiTest` (portal e2e), `TicketWorkflowTest`
(customer restrictions), `TenantIsolationTest` (portal customer separation). Unresolved: none.

## Journey C — SLA processing ✅
Expected: policy + calendar, boundary deadlines, warnings, pauses, overdue, escalation, retries.
Actual: as expected. Evidence: `SlaTest` (weekends, holidays, pause extension, idempotent
evaluation), `CmdbAutomationTest` (sla.breach automation). Unresolved: none.

## Journey D — Asset and service history ✅
Expected: create/assign/link/transfer/maintain with correct history.
Actual: as expected. Evidence: `PortalAndApiTest::test_asset_crud_and_warranty_math`. Unresolved: none.

## Journey E — Billable work and invoicing ✅
Expected: time → invoice → tax/discount → partial/full payment → zero balance + PDF.
Actual: as expected. Evidence: `InvoiceTest` (totals, payments, idempotency, PDF totals).
Unresolved: none.

## Journey F — SaaS billing ⚠️ partial (staging-gated)
Expected: test-mode checkout, signed webhooks, renewals, duplicates, failures, upgrades,
cancellation, entitlements.
Actual: all webhook/state logic verified with correctly-signed fixtures
(`SubscriptionTest`, `SubscriptionLifecycleTest` — renewal, refund, dispute, sync,
duplicates, proration, period-end cancel, reactivation). Live provider round-trip
requires Stripe test keys in staging (procedure: `docs/BILLING.md`). Unresolved: live
round-trip, real disputed-funds reconciliation with merchant bank.

## Journey G — Security isolation ✅
Expected: cross-tenant attempts fail across URLs, search, APIs, bulk, downloads,
invoices, assets, reports, jobs, webhooks.
Actual: as expected. Evidence: `TenantIsolationTest` (5), negative cases in
`ItsmKnowledgeTest`, `ItsmCatalogTest`, `ItsmWorkflowsTest`, `CmdbAutomationTest`,
`IdentityTest` (token scopes), `SecurityRegressionTest`. Unresolved: none open (P0 zero).

## Journey H — Recovery and reliability ✅ (app-level)
Expected: isolated restore + health + smoke tests.
Actual: as expected on dev data. Evidence: `supportflow:backup-verify` PASSED
2026-10-12 (63 tables, counts matched, login smoke); `/up` probe test; runbook
`docs/OPERATIONS/BACKUP_RESTORE_RUNBOOK.md`. Unresolved: production-scale restore
timing (staging gate).

## Journey I — Internationalisation and accessibility ⚠️ partial
Expected: locales, TZ/DST, currency, keyboard, validation, mobile.
Actual: locale switching + translated strings + UTC-expiry policy tested
(`PrivacyOpsTest`); 16 automated structural a11y checks green across 19 pages
(`AccessibilityTest`); DST covered in SLA boundary tests. Unresolved: manual
screen-reader pass, metered contrast check (P1).

## Journey J — Production deployment ⚠️ staging-gated
Expected: staging deploy, migrations, workers, scheduler, smoke tests.
Actual: procedure documented and dry-run locally (`migrate:fresh --seed`,
scheduler registration, `/up` 200, public pages 200). Unresolved: requires
staging host (P1 gate in `RELEASE_CHECKLIST.md`).
