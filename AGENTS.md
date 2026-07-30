# SalesFlow CRM repository instructions

For every new feature, architecture change, or cross-module refactor:

1. Read `docs/requirements.md` completely. It is the canonical product and engineering requirement.
2. Read the relevant feature section in `PROJECT_PHASES.md`.
3. Use `.agents/skills/salesflow-feature-development/SKILL.md` for the delivery workflow.
4. Resolve conflicts in this order: latest confirmed user instruction, `docs/requirements.md`, `PROJECT_PHASES.md`, original PDF, other docs.
5. Preserve the confirmed PostgreSQL auto-incrementing `BIGINT` key strategy.
6. Preserve the current technical-layer tree: Models in `app/Models`, Services in `app/Services`, repository contracts/implementations in `app/Repositories`, Policies in `app/Policies`, and UI state in `app/Livewire/<Module>`. Do not move domain code into `app/Modules` unless the user explicitly changes this decision.
7. Before coding a new feature, explain its purpose, target outcome, scope, dependencies, requirement IDs, and manual acceptance checks; then stop for user approval.
8. Implement one feature at a time, run relevant quality gates, update the checkpoint, propose a commit title/body, and stop for manual testing.

Do not install packages, create broad abstractions, or refactor unrelated modules without a demonstrated requirement in the active feature.
