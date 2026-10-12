# Database — SupportFlow Cloud

Migrations: `database/migrations/2024_01_01_000001…000010` (plus Laravel defaults).
All reversible (`down()` implemented). Run `php artisan migrate`; fresh installs and
upgrades use the same path (no destructive rewrites).

## Entities & relationships

- **Tenancy:** `tenants` (settings JSON, status, trial/suspension timestamps) ·
  `tenant_user` (membership + role) · `tenant_invitations` (token, expiry).
- **SaaS billing:** `plans` (prices, seats, limits, features) · `subscriptions`
  (status, interval, provider refs) · `billing_events` (idempotent webhook log).
- **Users:** `users` (+`tenant_id`, `role`, `is_super_admin`, avatar, `customer_id`
  for portal users, TOTP secret, activity flags).
- **Organisation:** `departments` · `teams` · `team_user` · `skills` · `skill_user`.
- **Customers:** `customers` (soft deletes, per-customer `sla_policy_id`) ·
  `contacts` · `locations`.
- **Tickets:** `categories` · `tickets` (per-tenant `number` unique, status/priority,
  assignment, SLA refs, satisfaction) · `ticket_messages` (`public|internal`) ·
  `ticket_watchers` · `ticket_histories` (audit of every change) · `attachments`
  (polymorphic, private disk) · `saved_replies` · `custom_field_definitions`.
- **SLA:** `sla_policies` (JSON targets + working hours) · `holidays` · `sla_events`
  (started/paused/resumed/warned/breached/met/escalated — idempotent alerting).
- **Assets & field work:** `assets` (lifecycle states, warranty) · `asset_histories` ·
  `remote_sessions` (provider links, consent, audit) · `time_entries`
  (billable flag, rates, running/logged/approved/invoiced states).
- **Service billing:** `invoices` (per-tenant numbers, decimal-safe totals) ·
  `invoice_items` (linked to tickets/time) · `payments` (unique provider refs,
  duplicate-reference protection).
- **Platform:** `notifications` (in-app) · `audit_logs` · `import_logs` ·
  `webhook_endpoints` + `webhook_deliveries`.

## Integrity rules

- Foreign keys + tenant-scoped unique constraints (`tickets.number`, `invoices.number`,
  `assets.asset_tag`, customer names, category names, holiday dates).
- Money: `decimal(12,2)` columns, `bcadd/bcmul` arithmetic in `Invoice::recalculate()`,
  server-side validation of every total and payment.
- Important operations wrapped in transactions (registration, ticket create/transition/
  merge, invoice issue, payment recording, plan changes, imports).
- Soft deletes (`customers`, `contacts`, `tickets`, `assets`, `invoices`) retain history;
  customer “delete” archives first.

## Seeders

- `PlanSeeder` — Starter £19 / Growth £49 / Professional £99 (always safe to re-run).
- `DemoSeeder` — labelled demo tenant; only runs when `SEED_DEMO_DATA=true` in a
  `local`/`testing` environment. Never seeds admin passwords.
