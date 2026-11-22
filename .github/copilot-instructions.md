# MarinaX — Repository Engineering Instructions

## Grounding and Safety
- Inspect repository files, current behavior, Git status, and project documentation before changing anything.
- Treat repository evidence as authoritative; do not invent stack, architecture, APIs, schema, or conventions.
- Preserve existing behavior and user changes. Avoid unrelated edits and dependencies.
- Never reveal secrets, tokens, credentials, or private data. Do not copy sensitive values into reports.
- Never use production data or run destructive operations against production.

## Workflow and Coordination
- The Manager is the single user-facing orchestrator. Follow the workflow defined in `.github/agents/manager.agent.md`.
- Architecture and task planning belong to Architect; implementation belongs to assigned specialists; independent review belongs to Reviewer; validation belongs to QA.
- Every task must have a bounded scope, inputs, outputs, ownership, dependencies, and acceptance criteria.
- Use `docs/API_CONTRACTS.md` and other shared docs as coordination artifacts. Record agreed changes before parallel work begins.
- Avoid concurrent edits to the same file. When ownership overlaps, sequence work or assign one integrator.
- Parallelism, delegation, worktrees, branch creation, commits, pull requests, and tool execution are capabilities of the host runtime—not guaranteed by agent prompts. Confirm available tools before claiming actions occurred.

## Git
- Do not commit directly to `main`.
- Do not force-push, merge, delete branches/worktrees, discard changes, or rewrite history without explicit approval.
- Before creating a branch/worktree, inspect status and ensure user changes remain untouched.
- Integrate reviewed branches only after required checks; retain work until integration is verified.
- Prefer PRs or equivalent review records when the host supports them.

## Quality
- Run relevant tests, lint, static analysis, build, and security checks only if the repository supports them and the environment permits.
- Record exact commands, outcomes, and limitations. Never report a check as passed unless it ran successfully.
- Security review is proportionate and required for every feature; specialist escalation is mandatory for security-sensitive changes.
- Performance review is evidence-based when runtime/query/assets/rendering may be affected.
- SEO review applies only to public, indexable pages.
- QA validates acceptance criteria and regression risks; Reviewer independently reviews the code and design.

## Documentation
- Update the relevant shared docs when decisions or contracts change.
- Avoid creating redundant project-context/status documents. If existing `PROJECT_CONTEXT.md` or `DEVELOPMENT_STATUS.md` are present, preserve and update them when appropriate.
- Keep docs concise, specific, and derived from repository evidence.
