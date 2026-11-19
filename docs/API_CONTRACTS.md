# MarinaX API Contracts

## Scope and sources

This inventory was checked against `routes/api.php`, `routes/web.php`,
`app/Http/Controllers/Api/WebsiteController.php`,
`app/Http/Middleware/AuthenticateTenantApiKey.php`,
`app/Http/Middleware/RequireTenantApiScope.php`, and
`public/openapi.yaml`.

The repository has two distinct API surfaces:

1. **Tenant website API** at `/api/v1/*`, authenticated by tenant API key and
   documented in OpenAPI.
2. **Dashboard JSON API** at `/api/*`, registered inside the web route group
   and authenticated through a browser session plus role/system permissions.

The dashboard JSON routes are registered, but a complete request/response
contract for them is not available in the repository's OpenAPI document.
Payload details not explicitly documented below are marked **unknown** rather
than inferred.

Interactive documentation: `GET /api/documentation`  
OpenAPI YAML: `GET /api/openapi.yaml`

## Tenant website API

Base path: `/api/v1`

### Authentication, authorization, and rate limits

- Protected routes require a Bearer token in the `Authorization` header. The
  token is a tenant key with the `mx_live_` prefix.
- Key verification uses the tenant's stored prefix and SHA-256 key hash.
- Keys may expire; inactive tenants are rejected.
- Every protected route requires its scope, listed in the endpoint table.
- Tenant API routes have a limit of 60 requests per minute per client IP.
  `POST /api/v1/bookings` has an additional limit of 10 requests per minute
  per client IP.
- API-key errors: `401` for missing, invalid, or expired credentials;
  `403` for an inactive tenant or a missing scope; `429` for throttling.
- API request/authentication logs record request metadata and do not include
  the API secret.

### Endpoint inventory

| Method | Path | Scope | Purpose |
|---|---|---|---|
| GET | `/api/v1/trips` | `trips:read` | List active tenant trips |
| GET | `/api/v1/trips/{trip}` | `trips:read` | Read one active tenant trip |
| GET | `/api/v1/companies` | `companies:read` | List active tenant companies |
| GET | `/api/v1/currencies` | `currencies:read` | List active currencies |
| POST | `/api/v1/bookings` | `bookings:create` | Create a pending booking |
| GET | `/api/v1/bookings/{booking}` | `bookings:read` | Read a booking summary by booking reference |
| PATCH | `/api/v1/bookings/{booking}/status` | `bookings:manage` | Request a booking status transition |
| POST | `/api/v1/bookings/{booking}/payments` | `payments:create` | Request a payment intent |
| POST | `/api/v1/payments/{payment}/refund` | `payments:refund` | Request a refund |

### Catalog response shapes

Successful catalog endpoints return JSON with a top-level `data` property.

- `GET /trips` returns `data` as an array; `GET /trips/{trip}` returns one
  object in `data`.
- Trip objects include `id`, `name`, nullable `description` and `time`,
  nullable `region`, `includes`, `excludes`, `faqs`, `primary_image`, `images`,
  `hotels`, and `prices`. Prices are derived from active tenant companies and
  active currencies. Image values are public-storage URLs.
- `GET /companies` returns active companies with `id`, `name`, nullable
  `email`, and nullable `phone`.
- `GET /currencies` returns active currencies with `id`, `name`, `code`,
  nullable `symbol`, `exchange_rate`, and `is_default`.
- Trip-specific response schema and examples are also defined in
  `public/openapi.yaml`.

### Booking creation

`POST /api/v1/bookings` accepts JSON:

| Field | Validation |
|---|---|
| `trip_id` | Required integer; active trip in the current tenant |
| `company_id` | Required integer; active company in the current tenant |
| `currency_id` | Required integer; active currency |
| `hotel_id` | Nullable integer; when supplied, must be an active hotel assignment on the selected trip |
| `trip_date` | Required date on or after today |
| `customer.name` | Required string, max 255 |
| `customer.email` | Optional valid email, max 255 |
| `customer.phone` | Required string, max 50 |
| `customer.address` | Optional string, max 255 |
| `adults`, `children`, `infants` | Required integers, each from 0 to 100; total must be at least one |
| `remarks` | Optional string, max 2000 |

An optional `Idempotency-Key` header must be 8–255 characters when supplied.
The key is HMAC-hashed and associated with the tenant. A matching key and
normalized request replays the original response; reuse with a different
payload returns `409`. The amount is computed from configured server-side
prices, not trusted from the client.

Successful creation returns HTTP `201`:

```json
{
  "data": {
    "booking_id": "WEB-...",
    "status": "pending",
    "amount": "250.00",
    "currency": "EGP"
  }
}
```

Other verified outcomes include `422` for invalid input, no passenger, or
missing configured prices; `409` for idempotency-key payload conflict or an
in-progress duplicate; and `503` if there is no active tenant user available
to record the booking.

### Booking lookup and status changes

- `GET /api/v1/bookings/{booking}` returns `data` containing the booking
  reference, lifecycle status, trip/company/date, passenger counts, amount,
  paid/remaining amounts, payment status, and creation timestamp.
- `PATCH /api/v1/bookings/{booking}/status` accepts `status` and optional
  `reason` (max 500). Requested status values are `confirmed`, `paid`,
  `cancelled`, and `refunded`.
- Allowed transitions in the service are `pending` → `confirmed` or
  `cancelled`; `confirmed` → `paid` or `cancelled`; and `paid` → `refunded`.
  Paid/refunded transitions require matching recorded financial events.
- Status changes are transactional and recorded in
  `operation_status_history`. Invalid transitions/prerequisites return a
  validation error (`422`).

### Payments and refunds

- `POST /api/v1/bookings/{booking}/payments` accepts a positive numeric
  `amount` and requires an `Idempotency-Key` header (8–255 characters).
- `POST /api/v1/payments/{payment}/refund` accepts a positive numeric `amount`
  and also requires an `Idempotency-Key`.
- With a configured gateway, intent/refund requests return HTTP `202`; without
  one, the configured default adapter causes HTTP `503`.
- The request contract does not accept card numbers or CVVs.
- The provider name, redirect URL, and final settlement/refund behavior depend
  on the configured `PaymentGateway` implementation. A real provider is not
  installed in the current repository.

### Payment webhook

`POST /api/webhooks/payments/{provider}` is rate-limited to 60 requests per
minute per client IP. It does not require the tenant API key; it requires
`X-Payment-Signature`, which is lowercase hexadecimal HMAC-SHA256 of the exact
request body, using that provider's configured webhook secret.

JSON body fields:

| Field | Validation |
|---|---|
| `event_id` | Required string, max 191 |
| `type` | `payment.succeeded`, `payment.failed`, or `payment.refunded` |
| `transaction_reference` | Required string, max 191 |
| `amount` | Required numeric value greater than zero |

Webhook event IDs are unique per provider. Replaying the same event/payload is
idempotent; reusing the event ID with a different payload returns `409`.
Verified error statuses include `401` for signature failure, `404` for an
unknown transaction, `422` for invalid data/amount, and `503` when webhook
verification is not configured.

## Dashboard JSON API

These endpoints appear in `routes/web.php` under the accounting web-session
group, not in `routes/api.php`. They use the web middleware, accounting-role
checks, system-permission checks, and additional route-specific role
restrictions. They are not authenticated with tenant API keys.

The exact authentication experience is therefore a logged-in browser session
and its applicable CSRF behavior. Request and response schemas are **unknown**
unless stated by the controller implementation; this document does not infer
payloads from route names.

### Dashboard endpoints registered under `/api/*`

| Method | Path |
|---|---|
| GET | `/api/home` |
| GET, POST | `/api/accounts` |
| GET | `/api/accounts/tree` |
| PUT, DELETE | `/api/accounts/{id}` |
| POST | `/api/accounts/budget` |
| POST | `/api/accounts/import` |
| POST | `/api/accounts/wipe` |
| GET | `/api/next-movement-no` |
| GET, POST | `/api/journal` |
| PUT, DELETE | `/api/journal/{id}` |
| POST | `/api/journal/{id}/post` |
| POST | `/api/journal/{id}/unpost` |
| POST | `/api/journal/bulk-delete` |
| POST | `/api/journal/import` |
| GET | `/api/ledger` |
| GET | `/api/trial-balance` |
| GET | `/api/budget` |
| GET | `/api/income-statement` |
| GET | `/api/balance-sheet` |
| GET | `/api/cash-flow` |
| GET, POST | `/api/regions` |
| PUT, DELETE | `/api/regions/{id}` |
| POST | `/api/regions/{id}/activate` |
| POST | `/api/regions/{id}/deactivate` |
| GET | `/api/region-hotels` |
| GET | `/api/region-trips` |
| GET | `/api/trip-hotels` |
| PUT | `/api/trips/{id}/hotels` |
| PUT | `/api/trips/{id}/region` |
| PUT | `/api/hotels/{id}/region` |
| POST | `/api/settings` |
| POST | `/api/backup` |
| GET | `/api/backups` |
| GET | `/api/backups/{name}` |
| POST | `/api/restore/{name}` |
| POST | `/api/restore-file` |
| POST | `/api/me/password` |
| GET, POST | `/api/users` |
| PUT, DELETE | `/api/users/{id}` |
| POST | `/api/users/{id}/password` |
| POST | `/api/users/{id}/security` |
| PUT | `/api/users/{id}/regions` |

Dashboard JSON status codes and response formats vary by controller action;
there is no separate machine-readable contract for these routes in the
repository. Role and system-permission checks can further limit individual
operations beyond the shared accounting group.

## Documentation and integration dependencies

- The authoritative public API contract is `public/openapi.yaml` (OpenAPI
  3.0.3). The page at `/api/documentation` loads Swagger UI assets from
  `unpkg.com`.
- API server origin depends on the deployed `APP_URL`; the route paths above
  are relative to that origin.
- Tenant API keys are managed from the Super Admin UI. The full key is shown
  after generation and is not retrievable from the stored hash.
- Booking creation depends on active trips/companies/currencies, configured
  tenant prices, and an active tenant user.
- Payment requests depend on a real gateway implementation and
  `PAYMENT_PROVIDER`; webhook verification depends on the provider-specific
  `PAYMENT_WEBHOOK_SECRET` configuration. The default gateway is unconfigured.
- No request/response examples are asserted here for the internal dashboard
  API because a complete contract is unavailable in the repository.
