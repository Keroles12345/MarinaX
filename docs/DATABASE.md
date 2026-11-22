# Database Design and Migration Safety

> No schema files or database definitions were included in the supplied archive. This is a safety guide and inventory placeholder, not a description of the actual schema.

## Schema inventory (complete from repository evidence)
- Engine/version:
- Tables/entities:
- Relationships and foreign keys:
- Constraints and indexes:
- Sensitive data:
- Tenant boundaries (if applicable):

## Change checklist
- Confirm current schema, models, migration history and data assumptions.
- Define columns, types, nullability/defaults, constraints, FK behavior and indexes.
- Check duplicate/existing data before adding uniqueness or constraints.
- Assess locks, table size, backfill strategy and compatibility with old/new application versions.
- Prefer reversible, staged migrations where practical; document rollback and limitations.
- Test migration on disposable/local data and test integrity/relationships.
- Never run destructive migrations or production data operations without explicit human approval.
