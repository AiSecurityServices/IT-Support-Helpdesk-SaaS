# Backup & restore runbook — SupportFlow Cloud

## Create a backup

```bash
php artisan supportflow:backup
php artisan supportflow:backup --dir=/mnt/offhost/backups
```

Output: `supportflow-YYYYMMDD-HHMMSS.zip` (0600) containing `database.sqlite`
(or `database.sql`), `storage-private/`, `storage-public/`, `manifest.json`
(app, timestamp, driver, git SHA, per-table row counts).

## Verify a backup (isolated — never touches production)

```bash
php artisan supportflow:backup-verify /path/to/supportflow-*.zip
```

Checks: manifest valid → all core tables present → row counts match manifest →
users/plans readable (login smoke) → storage files enumerated. Exit 1 on mismatch.

## Restore

1. `php artisan down`
2. Extract zip to a staging path; stop writers.
3. SQLite: replace `database/dev.sqlite` (or configured path). MySQL: `mysql db < database.sql`.
4. Restore `storage-private/` → `storage/app/private`, `storage-public/` → `storage/app/public`.
5. `php artisan migrate --force`, `php artisan storage:link`, `php artisan up`.
6. Smoke: login, dashboard metrics, attachment download, invoice PDF, `sla:evaluate`.

## Exercise log

| Date | Scope | Result |
|---|---|---|
| 2026-10-12 | Dev DB, isolated temp restore + verify | PASSED (63 tables, counts matched) |

## Rollback (releases)

Revert code to previous tag, restore the pre-deploy backup taken by CI, run
smoke tests. See `DEPLOYMENT_ROLLBACK.md`.
