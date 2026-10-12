# SupportFlow Cloud — IT Support & Helpdesk SaaS

Multi-tenant helpdesk platform for MSPs, internal IT teams and freelance technicians.
Laravel 9 · Blade · Tailwind CSS (CDN) · Alpine.js · Three.js hero · SQLite/MySQL · Stripe-ready.

**Design:** “Precision Digital Operations” system (`docs/DESIGN_SYSTEM.md`) — midnight-navy
marketing site with a bespoke WebGL “Precision Service Engine” hero (static SVG fallback,
reduced-motion support), refined light/dark operational dashboards, SVG icon set.

**What works:** tenant workspaces with trial subscriptions, ticket queues with SLA engine,
customer portal, asset inventory, time tracking, service invoicing with PDF + payments,
SaaS plan billing (Stripe Checkout + webhooks, or manual mode), reports with CSV export,
platform super-admin, versioned REST API, TOTP two-factor auth, signed webhooks,
knowledge base, service catalogue with approvals, problem/change/major-incident
workflows, CMDB relationships, automation engine, scoped API tokens, audited
impersonation, privacy export/retention, backup + verified restore, `/up` health probe.

## Quick start (Windows + XAMPP)

```bat
cd "C:\xampp\htdocs\IT Support & Helpdesk SaaS"
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan supportflow:superadmin --name="Admin" --email="admin@example.com"
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://localhost:8000 · Register a workspace (14-day trial) · Sign in as super-admin at `/admin`.

Full guide: [`docs/INSTALLATION.md`](docs/INSTALLATION.md) · User guide: [`docs/USER_GUIDE.md`](docs/USER_GUIDE.md)

## Test

```bat
php artisan test
```

157 passing tests: tenant isolation, ticket workflows, SLA math, invoices, subscriptions,
portal restrictions, API, TOTP, page rendering, knowledge base, service catalogue,
problem/change/incidents, CMDB + automation, identity (MFA, tokens, impersonation),
privacy/ops, security regressions, performance, design system. See [`docs/TESTING.md`](docs/TESTING.md).

## Docs

| File | Contents |
|---|---|
| `docs/INSTALLATION.md` | Local setup (XAMPP + alternatives), mail, queues, scheduler |
| `docs/ARCHITECTURE.md` | Multi-tenancy, roles, request lifecycle |
| `docs/DATABASE.md` | Entities, relationships, migrations |
| `docs/USER_GUIDE.md` | Owner / admin / technician / customer workflows |
| `docs/SECURITY.md` | Controls, threats, privacy notes |
| `docs/BILLING.md` | SaaS subscriptions vs service invoices, Stripe setup |
| `docs/DEPLOYMENT.md` | Production deployment + pre-launch checklist |
| `docs/BACKUPS.md` | Backup, restore, retention |
| `docs/TESTING.md` | Suite results, how to run |
| `docs/API.md` | REST API reference + webhooks |
| `docs/PRODUCTION_READINESS/` | Traceability, gaps, standards mapping, release checklist, security/a11y/perf reports |
| `docs/OPERATIONS/` | Disaster recovery, backup runbook, incident response, deploy/rollback |
| `docs/PRIVACY_AND_DATA_GOVERNANCE.md` | Privacy design, rights handling |
| `docs/DATA_RETENTION_AND_DELETION.md` | Retention schedules, erasure policy |

## Configuration

Copy `.env.example` — every integration is optional and documented there:
`STRIPE_*` (online billing), `MAIL_*` (SMTP), `BRAND_*` (white-label rebrand),
`SEED_DEMO_DATA` (clearly-labelled demo content, local only).

No frontend build step: Tailwind/Alpine/Three.js load from CDN; custom styles live
in `public/css/precision.css` and the hero scene in `public/js/service-engine.js`.

## Security reporting

Found a vulnerability? Email the support address configured as `BRAND_SUPPORT_EMAIL`
(see `.env.example`) with details and reproduction steps. Please do not open public
issues for security reports. See `docs/SECURITY.md` and
`docs/PRODUCTION_READINESS/SECURITY_REVIEW.md` for the control baseline.
