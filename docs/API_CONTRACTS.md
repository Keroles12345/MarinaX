# API Contracts

Use this document as a coordination source of truth for API changes. Do not document guessed endpoints. Update the contract before parallel frontend/backend work begins; mark proposals versus implemented/verified contracts.

## Contract template

For each endpoint, record:
- Status: PROPOSED / IMPLEMENTED / VERIFIED
- Method and path:
- Purpose:
- Authentication and authorization:
- Request: headers, params, body, validation and examples (no secrets)
- Success response: status and schema/example
- Error responses: status, code/message and validation shape
- Side effects/idempotency:
- Pagination/filtering:
- Compatibility/versioning:
- Owner and tests:

## Existing API inventory

Not verified from the supplied configuration-only archive. Populate from routes/controllers and API tests in the full repository.

## Tenant backup and public ticket contracts

### Tenant backup endpoints

- Status: VERIFIED
- Methods and paths: `POST /api/backup`, `GET /api/backups`, `GET /api/backups/{name}`, `POST /api/restore/{name}`, `POST /api/restore-file`
- Authentication and authorization: authenticated accounting administrator; each file operation is scoped to the initialized tenant.
- Purpose: create, list, download, and restore the active tenant's accounting backups.
- Compatibility: response shapes, snapshot format, and backup names remain unchanged. Backups are stored under a tenant-specific local directory. Snapshot rows that identify another tenant are rejected before creating a safety backup or changing data. Existing files in the former shared root directory remain untouched and are not exposed through tenant endpoints because their ownership cannot be verified.

### Public ticket verification

- Status: VERIFIED
- Method and path: `GET /ticket/{token}`
- Purpose: display a ticket to a bearer of its existing unique, unguessable token.
- Authentication and authorization: anonymous access remains available only through the ticket token; ticket and operation queries use the token's tenant context.
- Compatibility: public links using numeric ticket IDs are no longer supported and return not found. Newly generated ticket QR codes use the token. Authenticated staff PDF and download routes keep their existing paths and tenant-scoped model binding.
