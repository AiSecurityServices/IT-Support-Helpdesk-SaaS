# Deployment & rollback — SupportFlow Cloud

## Order

1. Announce maintenance window; `php artisan down --message="Scheduled maintenance"`.
2. `supportflow:backup` + `supportflow:backup-verify` (abort deploy on failure).
3. Deploy code (same tag everywhere); `composer install --no-dev --optimize-autoloader`.
4. `php artisan migrate --force` (expand-and-contract: migrations must be
   backward-compatible with the previous release still serving traffic).
5. `config:cache`, `route:cache`, `view:cache`; `storage:link`.
6. Restart queue workers (`queue:restart`); confirm scheduler cron active.
7. `php artisan up`; hit `/up` until 200; run smoke list below.
8. Tag release, append changelog, announce.

## Post-deploy smoke (staging AND production)

Register trial → create ticket → assign → public reply + internal note → resolve →
invoice → partial payment → PDF → `sla:evaluate` → webhook delivery → `/admin/health`.

## Rollback

1. `php artisan down`; revert code to previous tag.
2. Restore the pre-deploy backup (code + DB from the same timestamp).
3. `migrate --force` only forward; never `migrate:rollback` production data.
4. Smoke tests; `up`; incident note. Count any data written during the bad window
   and reconcile (subscriptions/invoices first).
