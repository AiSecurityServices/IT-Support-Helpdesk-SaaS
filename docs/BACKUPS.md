# Backups & recovery — SupportFlow Cloud

## What to back up

1. Database (nightly minimum; before every deploy and upgrade).
2. `storage/app/private` (attachments) and `storage/app/public` (avatars/logos).
3. `.env` (kept separately, encrypted — never in git).

Example (MySQL): `mysqldump --single-transaction supportflow | gzip > backup.sql.gz`.
SQLite: stop writers briefly, then copy `database/database.sqlite`.

## Retention (defaults — adapt to your policy)

- Daily database backups: 30 days. Weekly snapshots: 12 weeks.
- Cancelled workspaces: data retained 90 days, then deleted on request.
- `audit_logs`: 12 months. `billing_events`: 7 years (financial records).
- Attachments follow their parent record lifecycle.

## Restore

1. Put app in maintenance (`php artisan down`).
2. Restore database + storage snapshot from the same timestamp.
3. `php artisan migrate --force` (if code is newer), `php artisan up`.
4. Verify: login, dashboard metrics, attachment download, invoice PDF.

## Incident notes

- Log delivery failures surface in `/admin/health` (jobs + webhooks).
- Never log secrets, card data, tokens or private notes — notifications use
  `log` driver only in local development.
- Data-handling assumption: operators with database access can read all tenants;
  restrict accordingly and review `audit_logs` regularly.
