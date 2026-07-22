# Permission matrix

The executable RBAC catalog lives in `config/crm.php`. It contains 45 permissions grouped by module and five immutable default role keys.

| Role | Data scope | Direct permissions | Purpose |
|---|---|---:|---|
| `super-admin` | `all` | 0 | Bypasses Laravel Gate checks; reserved for the platform owner |
| `admin` | `all` | 45 | Manages users, settings and all CRM records |
| `sales-manager` | `department` | 39 | Manages sales records within the user's department |
| `sales` | `owned` | 30 | Manages records owned by the user |
| `viewer` | `read-only` | 8 | Reads allowed CRM modules without mutation permissions |

Legend: **A** all records/manage, **D** department records, **O** owned records, **R** read-only, **—** denied.

| Capability | super-admin | admin | sales-manager | sales | viewer |
|---|---:|---:|---:|---:|---:|
| Users / roles / settings | A | A | R | — | — |
| Leads | A | A | D | O | R |
| Lead assign / convert | A | A | D | O | — |
| Companies / contacts | A | A | D | O | R |
| Opportunities | A | A | D | O | R |
| Change/close stage | A | A | D | O | — |
| Pipelines manage | A | A | R | — | — |
| Activities / tasks | A | A | D | O | R |
| Reports | A | A | D | R | R |
| Audit logs | A | A (IT only) | — | — | — |

`RolePermissionSeeder` uses `findOrCreate` and `syncPermissions`, so rerunning it repairs the configured matrix without creating duplicates. It intentionally does not delete additional permissions that may be introduced by later modules.

Super Admin is implemented with `Gate::before`. Application code and policies must use `$user->can(...)`, `Gate`, middleware or authorization helpers for the bypass to apply; direct calls to `hasPermissionTo()` do not invoke Laravel Gate.

Role permissions answer **what** a user may do. The data scope answers **which records** the user may access. Policies and repository scopes implemented in P2-04 remain the authoritative backend boundary; hiding an action in Blade is never sufficient authorization.

## Backend data-scope enforcement

P2-04 implements the reusable authorization path below:

1. Laravel Gate/Policy checks the required permission, such as `users.view` or `users.update`.
2. `DataScopeResolver` resolves the user's strongest role scope from `config/crm.php`.
3. `DataScopeService` checks whether one record is visible or writable and applies the equivalent filter to repository queries.
4. Controllers and Livewire actions call the policy before reading or mutating data. Blade `@can` only improves the interface; it is not the security boundary.

| Scope | Query visibility | Mutation rule |
|---|---|---|
| `all` | All records | Allowed when the required permission is present |
| `department` | Records whose `department_id` equals the actor's department | Allowed only inside that department and with the required permission |
| `owned` | Records whose owner is the actor; for users the ownership column is `id` | Allowed only for owned records and with the required permission |
| `read-only` | All records allowed by the module's view permission | Always blocks create, update and delete, even if a write permission is assigned accidentally |

When a user has several roles, the resolver selects the broadest configured scope in this order: `all`, `department`, `owned`, `read-only`. A user with no recognized role receives `read-only` as the safe default.

The current foundation is exercised on `UserPolicy`, `DepartmentPolicy` and `EloquentUserRepository`. A sales manager may view Departments because the role has `users.view`, but Department creation, editing, activation and deletion additionally require `settings.manage`; therefore those actions remain read-only for that role. Future CRM repositories should call `DataScopeService::apply()` with their owner and department columns, and their policies should combine the module permission with `DataScopeService::allows()`.

P3-03 applies this rule to `EloquentLeadRepository`: all, department, owned and read-only scopes are added before Lead filters or pagination. P3-04 adds the separate permission boundary through `LeadPolicy`; request consumers must use both layers.

## Lead policy

| Ability | Required permission | Additional rule |
|---|---|---|
| `viewAny` | `leads.view` | — |
| `view` | `leads.view` | Lead must be inside data scope |
| `create` | `leads.create` | Scope must be writable |
| `update` | `leads.update` | Writable and inside data scope |
| `delete` | `leads.delete` | Writable and inside data scope |
| `restore` | `leads.delete` | Writable and checked against retained owner/department |
| `assign` | `leads.assign` | Writable and inside data scope |
| `convert` | `leads.convert` | Writable and inside data scope |
| `forceDelete` | none | Denied by policy; only Super Admin Gate bypass succeeds |

`leads.view-all` and `leads.update-all` do not bypass `DataScopeService`. For Sales Manager they mean access beyond personal ownership while remaining inside the manager's department. Sales has no `leads.assign`, so assignment remains a manager/admin action. Restore intentionally reuses `leads.delete` because the immutable permission catalog defines both directions of the soft-delete lifecycle under the same capability.

P3-06 enforces assignment again inside `LeadManagementService`, not only through form visibility. A Sales user creating a Lead is the narrow exception: the service ignores an empty owner and self-assigns the new Lead, but rejects any different owner. Sales Manager can assign only an active user visible inside the same department. Admin/Super Admin can assign an active visible user globally. On update, every owner change requires `leads.assign`, and `department_id` is derived from the resulting owner so a crafted request cannot create an inconsistent scope pair.

## Role assignment safeguards

P2-07 permits multiple roles per user; `DataScopeResolver` continues to choose the broadest effective scope. Every managed user must have at least one configured role.

- Only a `super-admin` may assign, remove or edit a user carrying the `super-admin` role. A regular `admin` may assign `admin`, `sales-manager`, `sales` and `viewer`.
- A regular `admin` is also denied by `UserPolicy::delete` for a Super Admin target, so a future deletion endpoint cannot bypass the same protection.
- The system must retain at least one active user carrying either `super-admin` or `admin`. Locking or demoting the last such user is rejected inside the same database transaction as the account update.
- User attributes and roles are updated atomically. Successful create/update operations write an `activity_log` entry with actor, target, old/new name, email, department, active state and roles.
- Audit properties never contain a plaintext password or password hash; they only contain a `password_changed` boolean.
- Audit viewing is restricted to `super-admin`, or an `admin` carrying `audit-logs.view` whose department code is `IT`. This condition is enforced by `AuditLogPolicy`, not only by navigation visibility.
- Only `super-admin` may add, remove or edit IT department members, preventing a regular admin from granting itself audit access.

The executable acceptance matrix for these rules is in `tests/Feature/AuthorizationCheckpointTest.php`. The manual checkpoint procedure and seeded test accounts are documented in `docs/authorization-checkpoint.md`.
