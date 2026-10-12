# Architecture — SupportFlow Cloud

## Stack

Laravel 9 (PHP 8.0-compatible) · Blade + Tailwind CDN + Alpine.js · Eloquent ORM ·
SQLite (dev) / MySQL (prod) · Laravel DomPDF · Stripe PHP SDK · Sanctum API tokens ·
Pragmarx Google2FA (TOTP).

## Multi-tenancy (single database, `tenant_id` scoping)

Hierarchy: **platform → tenant workspace → departments/teams → technicians →
customer companies → contacts/portal users → tickets/assets/invoices**.

- Every tenant-owned table carries `tenant_id` with foreign key + index.
- `BelongsToTenant` trait registers the `TenantScope` global scope (auto-filters
  every query) and stamps `tenant_id` on creation.
- `SetTenantContext` middleware (`tenant`) resolves the workspace from the
  authenticated user, sets timezone/locale, and locks out suspended tenants.
- Route-model binding therefore 404s on cross-tenant IDs (existence is not leaked);
  policies (`TicketPolicy`, `TenantRecordPolicy`) add a second explicit layer.
- Super-admin platform context (`platform_context` session flag, `superadmin`
  middleware) bypasses scoping for aggregate screens only — never for record access.
- The API (`auth:sanctum` + `tenant` middleware) enforces the same scoping;
  every endpoint re-checks `tenant_id` before authorising.

Background jobs and scheduled commands (`sla:evaluate`) iterate per-tenant with
`withoutTenantScope()` + explicit `where('tenant_id', …)` and construct a
per-tenant `SlaCalculator`, so calendars never bleed across workspaces.

## Request lifecycle (web)

`web` group → `auth` → `verified` → `tenant` (context) → optional `role:…` /
`plan:feature` gates → controller (policies via `$this->authorize`) →
`TicketService`/`SlaService` transactions → notifications (queued) + signed webhooks.

## Key services

| Service | Responsibility |
|---|---|
| `TicketService` | Validated transitions, history, assignment, merge, notifications |
| `SlaService` + `SlaCalculator` | Business-calendar deadlines, pauses, warnings/breaches, escalation |
| `AttachmentService` | Validated private-disk uploads |
| `ProvisioningService` | Per-workspace defaults for new tenants |
| `WebhookService` | HMAC-signed outgoing webhooks with delivery logs |

## Roles

`owner` (org + billing) · `admin` (operations config) · `manager` (teams, SLA, reports) ·
`technician` (assigned/team tickets, time, assets) · `customer` (portal only) ·
platform `is_super_admin` (SaaS administration).

Permissions are enforced in controllers, policies, jobs, exports and the API —
never by hiding UI elements alone.

## Branding / white-label

`config/branding.php` + `BRAND_*` env vars control product name, company info,
colours, currency, timezone and support contacts. Tenants override name, logo,
accent colour and support email per workspace in Settings → Branding.
