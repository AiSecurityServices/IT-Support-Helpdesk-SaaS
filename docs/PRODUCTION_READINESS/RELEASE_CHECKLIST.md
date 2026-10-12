# Release checklist — SupportFlow Cloud

## Application gates (all evidenced 2026-10-12)

- [x] `php artisan test` — 108 passed, 0 failed (incl. isolation, ITSM, identity, security, perf)
- [x] `php artisan migrate:fresh --seed` path verified; `migrate:status` clean
- [x] `php vendor/bin/pint --test` clean (run after edits)
- [x] `php artisan route:list` — no conflicts; 239+ routes render (PagesRenderTest)
- [x] Backup created AND restore-verified in isolation (`supportflow:backup-verify` PASSED)
- [x] Secret scan clean (CI step + manual grep); `.env`/DBs git-ignored
- [x] Traceability, gap, standards, security, a11y, perf docs written

## Pre-launch (owner-gated, staging)

- [ ] Staging deploy per `docs/DEPLOYMENT.md` + `docs/OPERATIONS/DEPLOYMENT_ROLLBACK.md`
- [ ] MySQL migration run + suite green on MySQL
- [ ] Stripe test-mode lifecycle (Journey F) with real test keys
- [ ] SMTP + SPF/DKIM/DMARC + bounce webhook wired (`EMAIL_DELIVERABILITY.md`)
- [ ] Scheduler cron + queue worker + `/up` monitor + alert thresholds
- [ ] Manual assistive-tech pass (`ACCESSIBILITY_REPORT.md`)
- [ ] Independent pen test booked; findings triaged (P0 blocks)
- [ ] RPO/RTO signed off; backup encryption-at-rest confirmed on host
- [ ] Privacy docs reviewed by counsel (templates are not advice)

## Emergency patch process

Hotfix branch → CI → staging smoke (Journeys A–G) → backup → deploy → migrate →
`/up` + smoke → tag release → changelog entry. Rollback per runbook (code revert +
same-timestamp DB restore; down-migrations are NOT a recovery method).
