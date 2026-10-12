# Enterprise identity (SSO/SCIM) — SupportFlow Cloud

Status: **separately gated enterprise capability — not implemented, not advertised.**

## Why gated

No IdP credentials, no test identity provider and no staging round-trip exist in
this environment. Shipping an unverified SSO login path risks permanent owner
lockout (explicitly out of scope). SCIM without lifecycle tests risks mass
deprovisioning accidents.

## Plan (when commissioned)

1. OIDC first (single protocol), then SAML 2.0; `config/sso.php` holds the settings.
2. Always preserve password + recovery-code login for owners (break-glass); SSO
   errors must degrade to local login, never lock out.
3. SCIM: provisioning/deprovisioning behind per-tenant flags with dry-run diffs.
4. Enterprise entitlement gating + per-tenant enforcement settings.
5. Full Journey coverage: enrol, login, lockout-recovery, offboarding, audit.

## Current compensating controls

Mandatory super-admin MFA, tenant MFA enforcement, recovery codes, auth-event
history, invitation lifecycle, token revocation, audited impersonation — all
tested in `IdentityTest`.
