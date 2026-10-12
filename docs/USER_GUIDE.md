# User guide — SupportFlow Cloud

## Tenant owner (first user after registration)

1. Register at `/register` — a workspace + 14-day trial (Starter) is created.
2. **Settings**: organisation details, branding/logo, working hours, notification
   preference, invoice defaults (prefix, VAT, payment terms, hourly rate), categories,
   saved replies, custom fields, outgoing webhooks.
3. **Teams**: create teams, invite staff by email (`/technicians`). Invitations expire
   in 7 days; seat limits follow your plan.
4. **Subscription** (`/billing`): compare usage vs limits, change plan monthly/annual
   (Stripe Checkout when configured, otherwise recorded for manual invoicing), cancel
   (90-day retention, then deletion on request).

## Administrator / manager

- **Tickets**: queues (Mine, Unassigned, Team, Urgent, Overdue, Awaiting…, Resolved,
  Closed), filters, bulk assign/status/watch. Status transitions are validated
  (e.g. Closed → Open only). Merge duplicates; watchers get reply notifications.
- **SLA** (`/sla`, Growth+): policies per priority, working days/hours, holidays,
  pause-on-waiting, default policy. `php artisan sla:evaluate` (every 5 min) sends
  warnings at 80% and breaches with auto-escalation.
- **Customers**: companies, contacts (optional portal login provisioning), locations,
  linked tickets/assets/invoices, archive/restore, CSV import (duplicates skipped)
  and export.
- **Reports** (`/reports`): tickets, SLA compliance, workload, financial, assets,
  audit activity — all with date/customer/technician/team filters and CSV export.

## Technician

- Work **Dashboard** + team queues; update status, post public replies and strictly
  private internal notes; attach files (private storage, authorised downloads).
- **Time**: start/stop timer or manual entries, billable flag, hourly rate.
- **Assets**: register, assign, transfer, maintenance notes, warranty tracking.
- **Remote** (`/remote`): save approved-tool session links per ticket/asset, record
  customer consent, complete sessions. Links are never shown in reports.
- **Knowledge** (`/kb`): draft articles (internal by default); managers publish.
  Only Published + Public ever reaches customers.
- **Problems/Changes** (`/problems`, `/changes`): link incident tickets, record root
  cause/workarounds; changes need implementation + rollback plans and audited
  approval (you cannot approve your own).
- **CMDB** (`/cmdb`): link assets/customers/locations/users; impact view shows the
  blast radius and open tickets.
- **API tokens** (`/settings/tokens`): issue scoped, expiring tokens for
  integrations; service accounts are token-only.

## Customer (portal at `/portal`)

Dashboard, request list with search, new request (+attachments), public conversation,
own devices, invoices (view/download data, PDF via staff), profile. Internal notes,
costs, other customers and admin screens are unreachable (tested).
New: **Services** (request catalogue items with approval tracking), **Help articles**
(published public KB with feedback).

## Everyone: profile & two-factor

`/profile` — details, avatar upload, password change (signs out other sessions,
sends a security alert). Optional TOTP 2FA: Profile → Set up 2FA → enter the
secret key into any authenticator app (otpauth link provided; no QR-image
dependency) → verify the 6-digit code. Subsequent logins ask for the code.

## Platform super-admin (`/admin`)

Aggregate tenants (trial/active/past-due/suspended), suspend/reactivate with reason,
editable plans/limits, MRR from recorded billing data, job/webhook health, audit log.
No per-tenant record browsing — support access must be explicit and is logged.
Create via `php artisan supportflow:superadmin`.
