# Changelog — SupportFlow Cloud

All notable changes. Format follows Keep a Changelog. Versions are tags;
`config('app.version')` exposes the current release to `/up` and footers.

## [1.1.0] — 2026-10-12

### Added
- Subscription lifecycle: `supportflow:subscriptions` daily command (trial expiry,
  period-end cancellation finalization, grace expiry with suspension), cancel at
  period end, self-service reactivation path, proration credits on plan changes.
- Stripe webhook handlers: `invoice.paid` renewal, `subscription.updated` sync,
  `charge.refunded` and `charge.dispute.created` with owner notifications.
- Suspended workspaces retain billing access so owners can pay to reactivate.
- Feature flags (`config/features.php` + tenant overrides + settings UI + nav gating).
- Onboarding checklist on the dashboard (derived from real tenant state).
- Owner workspace export (JSON, chunked, internal notes excluded).
- Append-only audit log (model-level update/delete guard).
- Queued outgoing webhooks (`DeliverWebhook` job, backoff, 5-try cap, logging).
- Mail template preview + safe test delivery (`TestMail`, settings UI).
- Automated accessibility smoke suite (16 structural checks across 19 pages).
- Security: baseline headers middleware, SSRF URL policy, notification redirect guard.
- `symfony/dom-crawler` (dev) for HTML assertions; CHANGELOG itself.

### Fixed
- Tenant context now binds before route-model binding (defence in depth).
- UTC-explicit security-timestamp comparisons (`HasUtcExpiries`).
- Strict ID comparisons hardened for MySQL string IDs.
- `x-field` labels now programmatically associated (wrapped); labelled all
  search/filter/bulk/file controls; skip links + main landmarks on all layouts.

## [1.0.0] — 2026-10-12 (prior execution)

Core helpdesk SaaS: multi-tenancy, tickets, SLA engine, customers, assets,
invoicing, subscriptions, portal, reports, super-admin, API v1, TOTP MFA,
knowledge base, service catalogue, problem/change/incidents, CMDB, automation
engine, identity hardening, privacy tooling, backup + verified restore.
Suite: 116 passing tests.
