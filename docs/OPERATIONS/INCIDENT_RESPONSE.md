# Incident response — SupportFlow Cloud

Contacts: platform owner (primary), on-call engineer, support inbox
(`BRAND_SUPPORT_EMAIL`). Report vulnerabilities per `SECURITY.md` (to be
published as `security.txt` on the marketing domain at staging).

## Severity

- SEV1: cross-tenant exposure, auth bypass, payment corruption, data loss.
- SEV2: major workflow down, mail/SLA pipeline down, backup failures.
- SEV3: degraded performance, single-tenant issue, docs errors.

## Procedure

1. **Detect** — `/up` monitor, queue/backlog alerts, `mail_events`, customer report.
   Correlation via `X-Request-ID` response/log header.
2. **Contain** — maintenance mode if needed; revoke tokens/grants; suspend tenant
   if it is the attack source; preserve logs (do NOT purge).
3. **Eradicate & recover** — hotfix per release process; restore from verified
   backup only if data integrity is in doubt.
4. **Communicate** — status notice for SEV1/2 within 1 h; affected tenants notified
   with facts only; breach assessment per `PRIVACY_AND_DATA_GOVERNANCE.md`.
5. **Learn** — post-mortem within 5 days; regression test for every bug found;
   update this file and the traceability matrix.

## Evidence preservation

Snapshot logs + DB before remediation; export relevant `audit_logs`/`auth_events`;
chain-of-custody note in the incident ticket (major-incident module may be used
internally with audience updates OFF for security incidents).
