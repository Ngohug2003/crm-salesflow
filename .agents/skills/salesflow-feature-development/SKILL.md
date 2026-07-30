---
name: salesflow-feature-development
description: Deliver SalesFlow CRM features consistently against the repository's canonical product and engineering requirements. Use when explaining, planning, implementing, reviewing, fixing, or completing a SalesFlow feature identified by a phase code such as P3-09, when changing module architecture or UI flows, or when updating PROJECT_PHASES.md after feature work.
---

# SalesFlow Feature Development

Apply the repository requirement, current technical-layer architecture, Flux UI patterns, quality gates, and user checkpoint workflow to every SalesFlow feature.

## Load project context

1. Locate the repository root.
2. Read `docs/requirements.md` completely before taking feature actions.
3. Read the active feature entry and its dependencies in `PROJECT_PHASES.md`.
4. Read only the additional architecture/database/permission docs relevant to the feature.
5. Inspect the current branch, worktree changes, existing implementation, tests, routes, schema, and dependencies before proposing edits.

Resolve conflicts in this order:

1. Latest confirmed user instruction.
2. `docs/requirements.md`.
3. `PROJECT_PHASES.md`.
4. Original design PDF.
5. Other project documents.

Preserve the confirmed auto-incrementing PostgreSQL `BIGINT` strategy even though the original PDF mentions UUID/ULID.

## Handle a new feature request

On the first request for a new feature, do not write implementation code. Explain in Vietnamese:

- Business purpose.
- User-visible target outcome.
- In-scope and explicitly out-of-scope work.
- Dependencies, risks, and relevant requirement IDs.
- Expected layers/files without inventing unused abstractions.
- Manual acceptance checklist.

Then stop for explicit approval. Treat a later response such as “OK, làm đi” as authorization to implement that feature.

Do not repeat this approval pause for a narrow bug fix inside the already approved feature unless the fix materially expands scope.

## Design before editing

Map each use case to the smallest valid flow:

```text
Livewire → Service/Action → Repository → Model
API → Form Request → Controller → Service/Action → Repository → Resource
Async → Event → Listener/Job → Notification or external side effect
```

Preserve the current tree: put Models in `app/Models`, Services in `app/Services`, repository contracts and implementations in `app/Repositories`, Policies in `app/Policies`, Actions in `app/Actions`, DTO/data objects in `app/Data`, and Enums/Events/Listeners/Exceptions in their existing technical layers. Keep Livewire presentation state under `app/Livewire/<Module>` and views under `resources/views/livewire/<module>`. Do not move domain code into `app/Modules` or reorganize existing layers unless the user explicitly changes this decision.

Before editing, verify:

- Policy and data-scope boundary.
- The complete route–sidebar–permission matrix, not only the active module: every authenticated CRM route must use a matching `can:` middleware or be documented in `crm.rbac.route_access_exceptions`; every sidebar condition must match the target route and every permission name must exist in `config/crm.php`.
- For RBAC changes, preserve the split defined by `DEC-012`: permission identifiers/defaults are code-owned in `config/crm.php`, effective Role–Permission assignments are database-owned, and routine seeders must preserve roles carrying a customization marker.
- Validation and normalization boundary.
- Transaction, row locking, idempotency, and after-commit needs.
- Audit, history, event, queue, realtime, and file-storage needs.
- For Opportunity stage changes, inspect Detail, Kanban, editor, Won/Lost and Reopen together; every mutation must pass the shared transition/exit-criteria boundary and trigger after-commit automation idempotently.
- Existing Flux UI components and reusable project components.
- Required loading, empty, error, processing, confirmation, responsive, dark, and keyboard states.
- Database query and index justified by the use case.

Do not install a package, create an interface, or add a shared abstraction without a demonstrated requirement.

## Implement one feature

1. Preserve unrelated and user-owned changes in a dirty worktree.
2. Add only migrations, domain/application code, UI, routes, tests, and docs required by the feature.
3. Keep business logic out of Livewire, controllers, Blade, repositories, and large model methods.
4. Enforce authorization on the backend; UI visibility is only a convenience.
5. When routes, navigation, Gate/Policy, roles, or permissions change, scan the **entire application route list and entire sidebar**. Remove dead permission names, keep `config/crm.php` as the single catalog, authorize Livewire actions independently, and update the route-access exception inventory only for a real record-policy/personal boundary.
6. Apply data scope before search, filters, aggregates, exports, duplicate lookup, and realtime payloads.
7. Use Flux UI Free first; build missing UI with Blade, Alpine, and Tailwind when necessary.
8. Use Vietnamese labels/messages and clear English identifiers.
9. Add tests for success, validation, denied permission/data scope, and critical edge cases.

Do not begin the next feature in the same turn.

## Verify proportionately

Run targeted tests first, then the relevant gates from `docs/requirements.md`:

```bash
docker compose exec app php artisan test <targeted tests>
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --no-progress
docker compose exec vite npm run build
```

For schema changes, test migration/backfill/rollback as appropriate. For runtime, queue, or realtime changes, inspect affected Docker services and logs. Never claim a gate passed when it did not run; report a proven environment blocker separately from a code failure.

For any route/sidebar/RBAC change, also run the repository navigation authorization test. It must prove that all authenticated CRM routes are classified, direct URL access is denied without permission, sidebar abilities match target routes, and no referenced permission is missing from `config/crm.php`.

## Close the feature checkpoint

Update `PROJECT_PHASES.md` with:

- Requirement and gap IDs addressed.
- Files created/changed.
- Migration/schema decisions.
- Architecture and security decisions.
- Commands run and exact results.
- Manual acceptance steps.
- Remaining out-of-scope items.

Return a concise Vietnamese handoff containing the outcome, verification results, manual test steps, known limitations, and a suggested commit:

```text
feat(pX-YY): tiêu đề ngắn

- Thay đổi nghiệp vụ chính
- Authorization/data-scope boundary
- Test và tài liệu
```

Stop for the user's manual test and confirmation.
