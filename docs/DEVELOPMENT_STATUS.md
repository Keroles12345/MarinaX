# MarinaX Development Status

## Snapshot

Status reviewed on 2026-11-18. This is a repository-based status report, not a
production-readiness certification. No issue tracker or project status file
was found that identifies work items as actively in progress; therefore, an
explicit current in-progress feature is **unknown**.

## Implemented features

The following are represented in application code and/or feature tests:

- Multi-tenant records, tenant context, Eloquent tenant scopes, tenant-aware
  query-builder filtering, and cross-tenant isolation tests.
- Separate Super Admin, admin/accounting, and staff session access.
- System permission management and route permission mapping.
- Trip/hotel/region/company/price management and staff region access.
- WebP trip image storage and trip content fields (description, includes,
  excludes, FAQs).
- Tenant website API key authentication, expiry, revocation, scopes, and
  last-used timestamp.
- Tenant API catalog, booking create/read/status endpoints, and payment/refund
  request endpoints.
- Booking lifecycle transitions and status history.
- Booking request idempotency, scoped to tenant and keyed with an HMAC hash.
- Payment persistence, idempotent intent/refund records, and signed,
  idempotent webhook processing.
- Queued email notification classes and per-event/recipient deduplication
  records.
- Accounting workflows and statements, account tree, supplier/driver/resource
  management, employee custody, and accounting tests.
- OpenAPI 3.0.3 YAML and a Swagger UI HTML page.
- GitHub Actions workflow for PHP 8.3, tests, and Pint.

## In progress

- **Explicitly tracked in-progress features:** unknown; no repository project
  board or status metadata was identified.
- Payment provider integration is not active; only the interface and
  unconfigured adapter exist (see below).
- WhatsApp delivery is not implemented; only an interface seam exists.

## Known limitations and bugs

- `PaymentGateway` is bound to `UnconfiguredPaymentGateway`; payment intent and
  refund requests return HTTP 503 until a real adapter is implemented and
  configured. Do not treat test/provider-independent persistence as real
  payment processing.
- `WhatsAppTransport` has no configured implementation.
- The OpenAPI document covers the public tenant website API, docs endpoints,
  and payment webhook, but does not define the session-authenticated dashboard
  JSON routes declared under `/api/*` in `routes/web.php`.
- Swagger UI's JavaScript and stylesheet are fetched from `unpkg.com`; the
  interactive page depends on that external CDN being available.
- A prior `php artisan view:cache` run failed because Laravel could not resolve
  the `<x-icon>` component used in
  `resources/views/staff/layout/footer.blade.php`. The component was not found
  in the repository's view source during this review. Recheck this before
  relying on full-project view precompilation.
- Booking lifecycle status (`booking_status`) coexists with legacy operation
  and payment status fields. The lifecycle service explicitly checks
  compatibility with recorded payment balances; future changes should keep
  those representations synchronized.

## Technical debt and pending improvements

The following are either listed in the repository README roadmap or directly
implied by verified integration boundaries:

- Implement and configure a real payment provider and provider-specific
  webhook adapters.
- Add booking reminders and configurable notification channels.
- Provide WhatsApp transport only if/when an integration is selected.
- Define and publish contracts for the internal accounting/dashboard JSON API.
- Define API request-log retention and administrative booking workflows.
- Expand authorization, accounting, rate-limit, and payment tests as needed.
- Select a compatible static-analysis tool and establish a baseline.
- Confirm complete Blade view compilation after resolving the missing icon
  component.

No evidence was found for an actively running production deployment or an
automatic deployment workflow. The inspected CI workflow runs tests and a
selected set of Pint checks on pushes and pull requests; it does not deploy.

## Verification performed for this status

- `php artisan route:list --path=api -v`: inspected registered API routes and
  route middleware.
- `php artisan test`: **74 passed, 777 assertions**, duration 21.92 seconds.
- Test database configuration in `phpunit.xml`: in-memory SQLite.
- CI configuration in `.github/workflows/tests.yml`: PHP 8.3, Composer install,
  Laravel tests, and Pint on selected files.

These results describe the current local repository/test environment only.
They do not certify production configuration, live database migrations,
provider credentials, or deployment behavior.
