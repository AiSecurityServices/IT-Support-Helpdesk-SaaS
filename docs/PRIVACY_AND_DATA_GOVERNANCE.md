# Privacy & data governance — SupportFlow Cloud

This documents product behaviour, not legal advice. No regulatory compliance is
declared; counsel must review before contractual commitments.

## Personal data (categories → purpose → basis pointer)

| Category | Purpose | Notes |
|---|---|---|
| Account identity (name, email, phone, avatar) | Authentication, support | Minimised; avatar optional |
| Support content (tickets, messages, attachments) | Service delivery | Customer-visible vs internal separation enforced |
| Device/asset identifiers | Managed IT service | Access-controlled; no covert monitoring |
| Billing records (invoices, payments) | Contract + legal retention | Never hard-deleted by retention jobs |
| Security logs (auth events, audit) | Integrity, abuse response | 12-month retention, restricted access |
| Shopping/telemetry | — | None collected |

## Privacy by design/default

Tenant isolation everywhere (tested); least-privilege roles; customer portal sees
only own organisation; internal notes never cross the boundary; exports scoped;
request IDs carry no PII; logs exclude secrets/bodies/card data.

## Rights handling

- **Access/export**: self-serve JSON (`/privacy/export`); tenant owners request
  full workspace exports via support.
- **Correction**: profile + record edit screens.
- **Erasure**: closure flow + `supportflow:retention`; financial/security records
  preserved per retention schedule (see `DATA_RETENTION_AND_DELETION.md`).
- **Breach**: assess → contain → notify per `OPERATIONS/INCIDENT_RESPONSE.md`;
  evidence preserved; ICO timelines are counsel's call.

## International transfers

Single-region hosting assumed; no residency guarantees offered. If tenants need
residency, host per-region stacks — do not promise what the architecture cannot
prove (see `KNOWN_LIMITATIONS.md`).
