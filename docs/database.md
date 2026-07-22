# Database

The canonical ERD and rationale are in [architecture.md](architecture.md). PostgreSQL is the supported runtime database. Application domain tables use auto-incrementing `BIGINT` primary keys and indexed foreign keys; package tables retain their package-supported key format.

## Lead taxonomy

P3-01 introduces the reusable taxonomy tables used by the Lead module.

| Table | Business key | Main fields | Query indexes |
|---|---|---|---|
| `lead_sources` | unique `code` | `name`, `description`, `color`, `is_active`, `sort_order` | `(is_active, sort_order)` |
| `tags` | unique `slug` | `name`, `description`, `color`, `is_active`, `sort_order` | `(is_active, sort_order)`, `(is_active, name)` |

Both tables use auto-incrementing `BIGINT` identifiers. Source codes are stable uppercase integration keys; tag slugs are stable URL/filter keys. Names and presentation metadata may change without breaking references.

The `lead_tag` pivot is intentionally created in P3-02 together with `leads`. Creating it in P3-01 would reference a table that does not exist yet and make a clean migration fail. P3-02 adds unique `(lead_id, tag_id)` membership, cascading foreign keys and the Eloquent `Lead::tags()` and `Tag::leads()` relationships.

## Lead domain

P3-02 creates `leads` and the `lead_tag` many-to-many pivot.

| Area | Columns / behavior |
|---|---|
| Scope | nullable `owner_id`, `department_id` |
| Taxonomy | nullable `lead_source_id`, many-to-many tags |
| Contact | `full_name`, email, primary/secondary phone, company, job title, website, address |
| Lifecycle | status, priority, estimated value, notes, `converted_at` |
| Audit ownership | nullable `created_by`, `updated_by` |
| Retention | timestamps and Soft Delete |

All references from `leads` use `ON DELETE SET NULL`, preserving the business record if taxonomy, assignment or audit users are technically removed. The pivot uses a unique `(lead_id, tag_id)` pair and `ON DELETE CASCADE`. Soft deleting a Lead does not delete its pivot rows, so restoring the Lead restores its tag membership; force deleting it removes the pivot rows.

Lead status and priority are stored as indexed strings and cast to `LeadStatus` and `LeadPriority` backed enums. This keeps PostgreSQL queries portable while giving application code typed values. Email and phone are indexed but intentionally not unique because duplicate detection is a P3-08 business workflow.

P3-08 adds nullable indexed `email_normalized`, `phone_normalized` and `secondary_phone_normalized` columns. Email normalization is lowercase plus trim. Phone normalization removes formatting and maps Vietnamese `+84`/`0084` prefixes to a leading zero. These columns are intentionally not unique: duplicate candidates trigger an explicit UI decision and authorized users may confirm that two records are legitimately separate. The migration backfills existing Lead rows without modifying their business timestamps.

`owner_id` is the canonical assignment column so `DataScopeService` can apply owned and department scopes consistently. `converted_company_id` and `converted_contact_id` are deferred until their target tables exist; P5-09 will add those foreign keys with the conversion transaction.

## Lead demo data

P3-05 adds an idempotent `DemoLeadSeeder` for local development. It maintains 30 records identified by fixed `lead.demoNN@salesflow.test` addresses: 18 belong to the Sales department and 12 to Marketing. The dataset cycles through every Lead status and priority, assigns an active source and exactly two tags, and uses existing demo users as owners so policy and data-scope behavior can be inspected in the UI. Running the root `DatabaseSeeder` again updates these records rather than duplicating them.

## Lead workflow history

P3-07 adds immutable append-only workflow history alongside the general activity log.

| Table | Business history | Main query indexes |
|---|---|---|
| `lead_assignment_histories` | previous/new owner, previous/new department, actor and reason | `(lead_id, created_at)`, `(changed_by, created_at)` |
| `lead_status_histories` | nullable initial status, target status, actor and reason | `(lead_id, created_at)`, `(to_status, created_at)`, `(changed_by, created_at)` |

Both tables use auto-incrementing BIGINT identifiers. Deleting a user or department sets the corresponding historical reference to null while retaining the history row. Force deleting a Lead cascades its workflow history; ordinary Soft Delete retains it for restoration. Assignment/status history, Lead state and general audit are written in one transaction.
