# Permission matrix

Legend: **A** all records/manage, **O** owned/department records, **R** read-only, **—** denied. Super Admin bypasses checks with `Gate::before`.

| Capability | super-admin | admin | sales-manager | sales | viewer |
|---|---:|---:|---:|---:|---:|
| Users / roles / settings | A | A | R | — | — |
| Leads | A | A | A | O | R |
| Lead assign / convert | A | A | A | O | — |
| Companies / contacts | A | A | A | O | R |
| Opportunities | A | A | A | O | R |
| Change/close stage | A | A | A | O | — |
| Pipelines manage | A | A | R | — | — |
| Activities / tasks | A | A | A | O | R |
| Reports | A | A | A | R | R |
| Audit logs | A | A | R | — | — |

Permission names are seeded from `config/crm.php`; policies remain the authoritative backend boundary.

