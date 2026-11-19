# MarinaX Project Context

## Snapshot

This document describes the repository as inspected on 2026-11-18. The primary
source of truth for routes and schema is the code in `routes/`, `app/`, and
`database/migrations/`; README and OpenAPI documentation are supporting
references.

## Project overview

MarinaX is a Laravel business-management application for tour operators. It
combines company/tenant administration, trip and hotel operations, staff and
resource management, accounting, and a tenant-isolated API for company
websites.

## Technologies

- PHP `^8.3` and Laravel Framework `^13.0`.
- MySQL and SQLite are supported by the project configuration and tests.
- Composer manages PHP dependencies; Node.js/npm and Vite build frontend assets.
- Blade templates render the server-side dashboard and forms.
- Frontend dependencies include Tailwind CSS 4, Axios, Chart.js, and SortableJS.
- Other PHP packages include Spatie Laravel Permission, DomPDF, OpenSpout, and
  SimpleSoftwareIO QR Code.
- PHPUnit 12 and Laravel Pint are used for tests and formatting.
- GD is a declared PHP extension requirement, including for image processing.

## Architecture

The application is a conventional Laravel monolith:

1. `routes/web.php` registers browser pages, session-backed dashboard JSON
   endpoints, and role-protected management actions.
2. `routes/api.php` registers API documentation, payment webhooks, and the
   versioned tenant website API.
3. Controllers validate and coordinate HTTP requests.
4. Services hold business workflows such as booking status transitions,
   payment handling, notifications, accounting setup, and image storage.
5. Eloquent models map domain records; middleware establishes authentication,
   authorization, and tenant context.
6. Blade and JavaScript provide dashboard interfaces; Vite is used to build
   frontend assets.

## Main modules

- **Platform/Super Admin:** tenants, tenant users, system permissions, API keys,
  and platform dashboard.
- **Administration:** trips, hotels, companies, prices, suppliers, regions,
  extras, users, permissions, and operational resources.
- **Staff operations:** bookings/operations, tickets, daily operations,
  assignments, and staff access restrictions.
- **Accounting:** accounts, journals, ledger, trial balance, budgets, income
  statement, balance sheet, cash flow, audit, exports, backups, and employee
  custody.
- **Tenant website API:** trip/catalog data, booking creation and lookup,
  booking status changes, and payment/refund requests.
- **Payments and notifications:** provider interface, webhook processing, and
  queued email notifications. The default payment adapter is intentionally
  unconfigured.

## Important folders

| Path | Purpose |
|---|---|
| `app/Http/Controllers/` | Browser, dashboard, API, staff, and Super Admin controllers |
| `app/Http/Middleware/` | Authentication, role/permission checks, tenant context, and API-key/scope checks |
| `app/Models/` | Eloquent models and tenant-scoping concerns |
| `app/Services/` | Booking, payment, notification, image, accounting, and other workflows |
| `app/Contracts/` | Payment gateway and WhatsApp transport interfaces |
| `app/Support/` | Tenant context/database helpers, scopes, permissions, and dashboard helpers |
| `routes/` | Web, API, and console route definitions |
| `database/migrations/` | Relational schema and incremental schema changes |
| `database/seeders/` | Default/test data seeders |
| `resources/views/` | Blade pages for admin, staff, Super Admin, and shared UI |
| `resources/js/`, `resources/css/` | Frontend JavaScript and CSS sources |
| `public/` | Public assets, OpenAPI contract, and interactive API documentation HTML |
| `tests/Feature/`, `tests/Unit/` | Feature and unit test suites |
| `.github/workflows/` | GitHub Actions workflows |
| `.github/agents/` and `.github/copilot-instructions.md` | Repository agent roles and coding guidance |

## Authentication and authorization

- Laravel's `web`, `staff`, `admin`, and `superadmin` guards are session guards
  backed by the `users` provider.
- Staff, company admin/accounting, and Super Admin access use separate
  middleware/guard checks. Super Admin users are distinguished by
  `is_super_admin`.
- Accounting dashboard routes require an accounting role and system-permission
  checks; some actions add stricter role checks.
- Tenant website API requests use an `Authorization` Bearer API key with the
  `mx_live_` prefix. The database stores a SHA-256 hash and prefix, plus
  expiration and scope data; raw keys are displayed after generation rather
  than retrieved from the database.
- API scopes are checked separately from API-key authentication.
- Payment webhooks do not use tenant API keys; they use an HMAC-SHA256 request
  signature configured per provider.
- `spatie/laravel-permission` is installed and the User model uses its roles
  trait. The application also has its own system-permission middleware and
  mapping.

## Database and tenant architecture

- The application uses one configured relational database; migrations define
  tenants and tenant-owned business records in that database.
- Tenant-owned tables carry `tenant_id`. `BelongsToTenant` applies a global
  Eloquent scope and assigns the current tenant on create; `TenantDatabase`
  applies tenant filters to selected query-builder tables.
- `TenantContext` is initialized from authenticated users by web middleware or
  from the authenticated API key, then cleared after request handling.
- The Super Admin context is intentionally not an ordinary tenant context.
- Currencies and shared account-chart definitions are treated as shared data
  in the application code; tenant-specific business records and account
  details remain scoped.
- `database/migrations/` contains the authoritative schema history. As
  inspected, it contains 71 migration files.
- The test configuration uses an in-memory SQLite database.

## Frontend/backend communication

- Browser screens are predominantly server-rendered Blade templates with
  standard Laravel form submissions and session/CSRF behavior.
- Dashboard JavaScript calls JSON endpoints under `/api/*`; these routes are
  declared in `routes/web.php` and use the web session and accounting
  authorization middleware, rather than tenant API-key authentication.
- The company website integration uses `/api/v1/*` JSON endpoints and
  tenant-scoped Bearer API keys.
- Vite builds the files listed in `vite.config.js`. Swagger UI is loaded from
  the Swagger UI CDN by `public/api-documentation.html`.

## Existing features verified in the repository

- Tenant/company and user administration with separate Super Admin access.
- Tenant-scoped trips, hotels, images, regions, prices, companies, and
  operation records.
- Trip descriptions, includes/excludes, FAQs, and ordered image galleries;
  uploaded trip images are resized and converted to WebP by the image service.
- API key expiration, revocation/rotation, scopes, last-use tracking, and
  request/authentication logging.
- Website booking creation with server-side price calculation and optional
  idempotency-key replay protection.
- Booking status transition validation and history.
- Payment records, idempotency, refund tracking, and signed webhook event
  processing; provider execution is not configured by default.
- Queued booking/payment email notification paths and duplicate-delivery
  records.
- Accounting statements, journals, account management, resource revenue
  assignments, exports, and backups.
- Region-based staff access and system-level permission administration.

For exact routes and request contracts, see [API_CONTRACTS.md](./API_CONTRACTS.md).
