# Database

The canonical ERD and rationale are in [architecture.md](architecture.md). PostgreSQL is the supported runtime database. Application domain tables use auto-incrementing `BIGINT` primary keys and indexed foreign keys; package tables retain their package-supported key format.

## Lead taxonomy

P3-01 introduces the reusable taxonomy tables used by the Lead module.

| Table | Business key | Main fields | Query indexes |
|---|---|---|---|
| `lead_sources` | unique `code` | `name`, `description`, `color`, `is_active`, `sort_order` | `(is_active, sort_order)` |
| `tags` | unique `slug` | `name`, `description`, `color`, `is_active`, `sort_order` | `(is_active, sort_order)`, `(is_active, name)` |

Both tables use auto-incrementing `BIGINT` identifiers. Source codes are stable uppercase integration keys; tag slugs are stable URL/filter keys. Names and presentation metadata may change without breaking references.

The `lead_tag` pivot is intentionally created in P3-02 together with `leads`. Creating it in P3-01 would reference a table that does not exist yet and make a clean migration fail. P3-02 must add unique `(lead_id, tag_id)` membership and cascading foreign keys, then expose the Eloquent `Lead::tags()` and `Tag::leads()` relationships.
