# Deployment — SupportFlow Cloud

## Target hosting

Any PHP 8.0+ host (shared/VPS). Apache document root → `public/`. For XAMPP-based
Windows servers the same layout as development works.

## Pre-launch checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, fresh `APP_KEY` (`key:generate`)
- [ ] `APP_URL=https://…` + valid TLS certificate (HTTPS required)
- [ ] MySQL/MariaDB database + user with least privilege; `DB_*` set; `migrate --force`
- [ ] `php artisan storage:link`; `storage/` writable; private disk NOT web-accessible
- [ ] SMTP credentials verified (test password reset + ticket reply end to end)
- [ ] Stripe live keys + webhook registered + `stripe trigger` test in test mode first
- [ ] Queue: `QUEUE_CONNECTION=database` (after `queue:table`) + supervisor/worker
- [ ] Scheduler cron every minute (`schedule:run`) for SLA + reminders
- [ ] `config:cache`, `route:cache`, `view:cache` (never cache tenant data manually)
- [ ] Security headers at web-server level (HSTS, frame-options, content-type-options)
- [ ] Backups configured and restore-tested (see `docs/BACKUPS.md`)
- [ ] Super-admin created via command (no default passwords anywhere)
- [ ] `composer audit` reviewed; `php artisan test` green on the deploy artifact
- [ ] Error pages (403/404/500) verified; logs contain no secrets

## Environment (production `.env` essentials)

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://support.example.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=supportflow
DB_USERNAME=supportflow
DB_PASSWORD="strong-random-password"
MAIL_MAILER=smtp
MAIL_HOST=…
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
```

Never commit `.env`, `*.sqlite`, uploads, or keys. Rotate `APP_KEY` only with a plan
(TOTP secrets and encrypted values depend on it).

## Post-deploy verification

Register trial workspace → create ticket → reply as customer → resolve → invoice →
record payment → PDF download → run `sla:evaluate` → check `/admin/health`.
Rollback: restore code + database backup from the same timestamp (see `docs/BACKUPS.md`).
