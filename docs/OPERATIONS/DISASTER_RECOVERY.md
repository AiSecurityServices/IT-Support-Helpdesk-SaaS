# Disaster recovery — SupportFlow Cloud

## Objectives (proposed, owner to sign)

- RPO ≤ 24 h (nightly backups) · RTO ≤ 4 h (documented restore, tested path).
- Backup encryption at rest: host-managed volume encryption minimum; secret
  backup copies isolated from production credentials.

## Strategy

- Nightly `php artisan supportflow:backup` (DB + `storage/app/{private,public}` +
  manifest with row counts + git SHA), 0600 permissions, off-host copy.
- MySQL: `--single-transaction` dumps; SQLite: file copy with writers paused.
- Never rely on down-migrations for recovery — restore code + DB from the same
  timestamp, then `migrate --force` forward if code is newer.

## Failure playbooks

| Failure | Response |
|---|---|
| Database down | Maintenance mode, restore latest backup to standby, verify, cut over |
| Storage down | Serve degraded (no attachments), restore `storage-*` from backup |
| Mail provider down | Queue retries with backoff; `/up` + `mail_events` monitored; status notice |
| Queue stopped | `failed_jobs` + admin health alert; restart workers; replay idempotent jobs |
| Payment provider timeout | Webhooks retry; idempotency keys prevent double-processing |
| Cache down | App runs (file/array fallback); scheduler heartbeat stale → alert |

## Last restore test

2026-10-12, isolated temp dir, dev database: `supportflow:backup-verify` PASSED
(63 tables, manifest counts matched, users/plans readable, storage enumerated,
production untouched). Record each exercise in `BACKUP_RESTORE_RUNBOOK.md`.
