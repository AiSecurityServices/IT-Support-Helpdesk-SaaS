# Data retention & deletion — SupportFlow Cloud

## Schedules (defaults, configurable by policy)

| Data | Retention | Deletion |
|---|---|---|
| Pending invitations | 7-day validity; purged 30 days after expiry | `supportflow:retention` |
| Notifications (read) | 365 days | retention command |
| Automation runs | 180 days | retention command |
| Mail/bounce events | 365 days | retention command |
| Auth events | 365 days | retention command |
| Closed tenant workspaces | 90-day grace, then erasure on request | manual + verified |
| Backups | 30 daily / 12 weekly (host policy) | host lifecycle |

## Never auto-deleted

Invoices, payments, billing events, audit logs — legitimate legal/security
retention duties. Erasure requests against these are answered with retention
justification, not deletion.

## Deletion behaviour

Soft-deletes retain operational history; `supportflow:retention --dry-run`
reports before acting; database backups expire with the host lifecycle (note the
backup window when promising erasure dates).
