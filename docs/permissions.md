# Permission matrix

The executable RBAC catalog lives in `config/crm.php`. It contains 45 permissions grouped by module and five immutable default role keys.

| Role | Data scope | Direct permissions | Purpose |
|---|---|---:|---|
| `super-admin` | `all` | 0 | Bypasses Laravel Gate checks; reserved for the platform owner |
| `admin` | `all` | 45 | Manages users, settings and all CRM records |
| `sales-manager` | `department` | 40 | Manages sales records within the user's department |
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
| Audit logs | A | A | R | — | — |

`RolePermissionSeeder` uses `findOrCreate` and `syncPermissions`, so rerunning it repairs the configured matrix without creating duplicates. It intentionally does not delete additional permissions that may be introduced by later modules.

Super Admin is implemented with `Gate::before`. Application code and policies must use `$user->can(...)`, `Gate`, middleware or authorization helpers for the bypass to apply; direct calls to `hasPermissionTo()` do not invoke Laravel Gate.

Role permissions answer **what** a user may do. The data scope answers **which records** the user may access. Policies and repository scopes implemented in P2-04 remain the authoritative backend boundary; hiding an action in Blade is never sufficient authorization.
