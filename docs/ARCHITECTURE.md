# MarinaX Architecture

> Evidence boundary: the provided archive contains GitHub Copilot instructions, agent definitions and one CI workflow—not application source. This document records the engineering workflow architecture. Application architecture must be filled in from repository inspection; do not infer it from this configuration snapshot.

## Engineering layers

1. **Planning:** Manager orchestrates; Architect produces implementation plans and contracts without coding.
2. **Execution:** Backend, Frontend and Database specialists own bounded implementation tasks. Security, Performance and SEO provide specialist work within their scopes.
3. **Quality & Integration:** Manager integrates; Reviewer independently reviews; QA validates acceptance criteria and tests.

## Ownership and coordination

- Each task has one primary owner and explicit file scope.
- Shared contracts and decisions are recorded before parallel execution.
- No concurrent edits to a file. Shared-file work is serialized or assigned to one integrator.
- Worktrees/branches are preferred for independent parallel work, but only if host tools support them.
- Agent prompts do not create runtime capabilities; report unavailable actions and provide a manual fallback.

## Application architecture inventory

Complete after inspecting the actual source:
- Runtime/framework and versions:
- Entry points/routes:
- Major modules:
- Persistence and data stores:
- External integrations:
- Authentication/authorization:
- Deployment/runtime topology:
- Tests/build/lint tools:

## Change flow

Requirements → design/approval → task contracts → isolated execution → integration → independent review → QA and quality checks → human release approval.
