# Security Rules

## Baseline
- Use least privilege and enforce authorization server-side for each action/object.
- Validate input at trust boundaries; encode output and avoid unsafe rendering.
- Use parameterized queries/ORM safely; never concatenate untrusted SQL.
- Protect CSRF/session/cookie settings and verify webhooks.
- Prevent SSRF, path traversal, unsafe uploads, mass assignment and insecure deserialization.
- Protect secrets with environment/secret stores; never commit or log credentials, tokens, private data or raw sensitive request bodies.
- Apply rate limiting and abuse protections where appropriate.
- Keep tenant/customer data isolated where multi-tenancy exists; verify object-level access.
- Avoid exposing stack traces or internal secrets in user-facing errors.
- Keep dependencies reviewed; use available audit tooling.
- Never weaken security controls to make a test pass.

## Review and test expectations
For each feature, assess security proportional to risk. High-risk surfaces include auth, authorization, tenant isolation, personal data, payments, webhooks, uploads, secrets, external network calls and infrastructure.
Include negative tests for unauthorized access, invalid inputs, boundary conditions and cross-tenant/object access where applicable.

## Safety
No production attacks, destructive tests, data deletion, security-control bypass or access outside authorization. Findings must contain evidence and severity, not sensitive values. Security review cannot certify an application as secure; scope and limitations must be stated.
