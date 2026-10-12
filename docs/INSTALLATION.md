# Installation — SupportFlow Cloud

## Requirements

| Component | Version |
|---|---|
| PHP | 8.0.2+ (tested on 8.0.30, XAMPP) |
| Composer | 2.x |
| Database | SQLite (default, zero-config) or MySQL/MariaDB 10.4+ |
| Node/npm | Optional — UI uses Tailwind/Alpine CDNs, no build step required |
| Extensions | openssl, pdo, mbstring, tokenizer, xml, ctype, json, bcmath, fileinfo, curl |

## Local setup (Windows + XAMPP)

```bat
cd "C:\xampp\htdocs\IT Support & Helpdesk SaaS"
composer install
copy .env.example .env
php artisan key:generate
```

Edit `.env`:

```ini
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE="C:/xampp/htdocs/IT Support & Helpdesk SaaS/database/database.sqlite"
MAIL_MAILER=log
```

(Dotenv note: quote the path and use forward slashes — backslashes are escape
characters inside quoted dotenv values.)

```bat
php artisan migrate --seed
php artisan storage:link
php artisan supportflow:superadmin --name="Platform Admin" --email="admin@example.com"
php artisan serve --host=127.0.0.1 --port=8000
```

Visit `http://localhost:8000`. Register a workspace, or sign in as super-admin and open `/admin`.

### Using MySQL/MariaDB instead (XAMPP phpMyAdmin)

1. Create database `supportflow` (utf8mb4).
2. `.env`: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`,
   `DB_DATABASE=supportflow`, `DB_USERNAME=root`, `DB_PASSWORD=` (XAMPP default blank).
3. `php artisan migrate --seed`.

## Mail

- Default `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log` — ideal for development.
- For real delivery: `MAIL_MAILER=smtp` + host/port/username/password/encryption,
  plus `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME`.
- On XAMPP, use a real SMTP account (or local Mailpit). MercuryMail is not recommended.

## Queues & scheduler

- Default `QUEUE_CONNECTION=sync` executes notifications inline (fine for small teams).
- For `database` queues: `php artisan queue:table && php artisan migrate`,
  set `QUEUE_CONNECTION=database`, run `php artisan queue:work`.
- Scheduler (SLA evaluation every 5 min, daily reminders): add a cron/Task Scheduler entry:
  `* * * * * php artisan schedule:run` (see `app/Console/Kernel.php`).

## Storage

- Public disk (`storage/app/public`, avatars, logos): `php artisan storage:link`.
- Private disk (`storage/app/private`, attachments): never symlinked; served only via
  authorised `attachments/{id}` downloads.

## Frontend

No build step: Tailwind CSS and Alpine.js load from CDN with a local-friendly design
(no dependency on `npm run dev`). `package.json`/`vite.config.js` remain from scaffolding
and are unused.

## Demo data (local only)

```bat
set SEED_DEMO_DATA=true && php artisan db:seed
```

Seeds a `demo-msp` tenant with labelled demo records. Never enabled in production
(`DatabaseSeeder` refuses non-local environments).

## Troubleshooting

| Symptom | Fix |
|---|---|
| `Database file at path [laravel] does not exist` | Set absolute sqlite `DB_DATABASE` (quoted, forward slashes) |
| `The environment file is invalid` | Check quoting/escapes in `.env` |
| 500 on marketing pages | Run `php artisan migrate --seed` (plans table required) |
| Mails not arriving | `MAIL_MAILER=log` only writes to the log file by design |
| Stripe buttons do “manual” changes | Set `STRIPE_SECRET` (see `docs/BILLING.md`) |
| Tests fail on `plans` | Suite uses `:memory:` sqlite; run `php artisan test` as documented |
