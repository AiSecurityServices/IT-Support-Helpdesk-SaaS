# API reference — SupportFlow Cloud (`/api/v1`)

Authenticated with Laravel Sanctum personal-access tokens. Every response is
tenant-scoped and policy-checked; cross-tenant IDs return 404.

## Authentication

1. Sign in to the workspace (a token UI is intentionally minimal — issue via tinker
   or a future settings screen): `$user->createToken('name')->plainTextToken`.
2. Send `Authorization: Bearer <token>` + `Accept: application/json`.
3. Rate limit: 60/min (`throttle:api`).

## Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/user` | Current user summary |
| GET | `/api/v1/tickets?status=&priority=` | Paginated ticket list (staff) |
| POST | `/api/v1/tickets` | Create ticket (`subject`, `description`, `customer_id?`, `priority?`, `category_id?`; source=`api`) |
| GET | `/api/v1/tickets/{id}` | Ticket + messages (customers see public only) |
| POST | `/api/v1/tickets/{id}/replies` | Public reply (`body`) |
| GET | `/api/v1/customers` | Paginated customers (staff) |
| GET | `/api/v1/customers/{id}` | Customer + contacts |
| GET | `/api/v1/assets` | Paginated assets (staff) |
| GET | `/api/v1/assets/{id}` | Asset detail |

Example:

```bash
curl -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -d '{"subject":"VPN down","description":"…","priority":"high"}' \
  https://support.example.com/api/v1/tickets
```

## Outgoing webhooks (tenant-configured, Professional plan)

Events `ticket.assigned`, `ticket.replied`. Deliveries are HMAC-signed
(`X-SupportFlow-Signature: sha256=…`), carry `X-Idempotency-Key`, time out after
10s, and are logged in `webhook_deliveries` (failures visible in `/admin/health`).
