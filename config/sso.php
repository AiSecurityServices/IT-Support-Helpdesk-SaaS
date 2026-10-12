<?php

// SSO / enterprise identity federation — GATED, NOT IMPLEMENTED.
//
// Status: separately gated enterprise capability. Reason: no identity provider
// credentials, no test IdP, and no staging SSO round-trip is available in this
// environment; shipping an untested SSO login path risks permanent owner
// lockout, which is explicitly out of scope until verified.
//
// When enabled in future, this file holds:
//   'oidc' => ['enabled' => false, 'issuer' => null, 'client_id' => null,
//              'client_secret' => null, 'scopes' => ['openid','profile','email']],
//   'saml' => ['enabled' => false, ...],
//   'scim' => ['enabled' => false, 'bearer_token' => null],
//   'enforce_for_tenant_ids' => [],
//
// Non-goals until then: claiming SSO/SCIM support in marketing or contracts.
// See docs/ENTERPRISE_IDENTITY.md.

return [
    'driver' => env('SSO_DRIVER', 'none'), // none|oidc|saml — 'none' until verified
    'enabled' => false,
];
