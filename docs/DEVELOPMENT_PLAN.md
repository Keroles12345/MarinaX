# Hybrid Engineering Workflow

## Status
This is the workflow baseline. Feature plans should be appended or maintained as separate task documents only when useful; avoid turning this file into a stale task log.

## Standard phases
1. Requirements: inspect behavior and clarify blockers.
2. Planning: Architect design, contracts, task/dependency map, criteria, risk and rollback.
3. Approval: obtain human approval for significant architecture, schema, destructive or high-risk plans.
4. Execution: specialists implement owned tasks; parallel tasks require disjoint files, agreed contracts and isolated workspaces.
5. Integration: inspect diffs, compatibility, resolve conflicts safely and build.
6. Review: independent Reviewer; proportionate Security always; Performance/SEO when applicable.
7. QA: acceptance criteria, functional/integration/regression/edge tests; run available quality checks.
8. Final approval: evidence-based report and explicit human approval for deployment/destructive/critical actions.

## Task ledger template
| ID | Task | Owner | Files | Depends on | Parallel? | Status | Evidence |
|---|---|---|---|---|---|---|
| T-1 | Example placeholder; replace for each feature | | | | | Planned | |

## Worktree strategy
- Base branch: confirm with user/repository; do not assume `main` is the correct base.
- Feature integration branch: `feature/<slug>`.
- Worker branches: `feature/<slug>-backend`, `-frontend`, `-database` (adjust to task names).
- Worktrees use separate paths; verify tools, repository status and branch names first.
- Never have two workers edit the same file.
- Review each branch diff and checks before integration.
- Do not force-push, delete worktrees/branches, or discard changes without approval.
- Cleanup only after confirming changes are safely integrated; ask before cleanup if authorization is unclear.

## Host capability fallback
If the Copilot runtime cannot invoke agents in parallel, run agents sequentially. If it cannot manage Git worktrees, provide commands for the user or use separate clones only with approval. Do not represent instructions as completed actions.
