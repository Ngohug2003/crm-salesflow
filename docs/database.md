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

`owner_id` is the canonical assignment column so `DataScopeService` can apply owned and department scopes consistently. `converted_company_id` and `converted_contact_id` are deferred until their target tables exist; P5-09 will add those foreign keys with the conversion transaction.
