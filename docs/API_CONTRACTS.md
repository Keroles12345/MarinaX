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
