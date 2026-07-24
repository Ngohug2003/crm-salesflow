# Giai đoạn 5 — Pipelines và Opportunities

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Quản lý pipeline có cấu hình, opportunity lifecycle, Kanban và realtime.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P5-01 | ✅ Pipeline và stage schema | `feature/p5-01-pipeline-domain` | P2-08 | Schema/model/factory/test Pipeline và Stage với PostgreSQL BIGINT tự tăng |
| P5-02 | ✅ Quản lý pipeline/stage | `feature/p5-02-pipeline-management` | P5-01 | Quản lý danh sách, cấu hình stage, sắp xếp và quy định bảo vệ |
| P5-03 | ✅ Opportunity schema và domain | `feature/p5-03-opportunity-domain` | P4-02, P5-01 | Schema/model/factory/test Opportunity liên kết Company/Contact/Pipeline/Stage |
| P5-04 | Opportunity CRUD và weighted value | `feature/p5-04-opportunity-crud` | P5-03 | Dịch vụ, Repository, Policy và Livewire CRUD cho Opportunity |
| P5-05 | Stage transition và history | `feature/p5-05-stage-transition-history` | P5-04 | Chuyển stage có lưu lịch sử immutable và kiểm tra version conflict |
| P5-06 | Opportunity Kanban | `feature/p5-06-opportunity-kanban` | P5-05 | Giao diện Kanban kéo thả Livewire + Alpine + SortableJS |
| P5-07 | Realtime private broadcast | `feature/p5-07-opportunity-realtime` | P5-06 | Đồng bộ Kanban realtime qua Reverb private channel |
| P5-08 | Close won/lost workflow | `feature/p5-08-opportunity-close` | P5-05 | Quy trình đóng cơ hội Won/Lost có bắt buộc lý do và rule reopen |
| P5-09 | Lead conversion integration và checkpoint | `feature/p5-09-lead-conversion-checkpoint` | P3-09, P4-02, P5-01..P5-08 | Tích hợp chuyển đổi Lead và checkpoint nghiệm thu Giai đoạn 5 |

---

### Nhật ký feature P5-01 — Pipeline và stage schema

Trạng thái: **hoàn tất triển khai**.

- Tạo migration `2026_07_25_000001_create_pipelines_table.php` và `2026_07_25_000002_create_pipeline_stages_table.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`).
- Tạo Eloquent Model `Pipeline` và `PipelineStage`.
- Tạo `PipelineFactory`, `PipelineStageFactory` và `DemoPipelineSeeder` quy trình chuẩn 6 giai đoạn.
- Viết `PipelineDomainTest` kiểm thử 3 test cases đạt 100% PASS (20 assertions).

---

### Nhật ký feature P5-02 — Quản lý pipeline/stage

Trạng thái: **hoàn tất triển khai**.

- Tạo `PipelineFilterData` DTO, `PipelineRepository` & `EloquentPipelineRepository`.
- Tạo `PipelinePolicy` cưỡng chế phân quyền 5 vai trò và Data Scope.
- Tạo `PipelineManagementService` thực thi trong `DB::transaction()`, quản lý thứ tự `position`, bảo vệ stage system và ghi log kiểm toán.
- Tạo Livewire Components `PipelineList`, `PipelineEditor`, `PipelineDetail` và bật menu Sidebar.
- Viết `PipelineManagementTest` kiểm thử 6 test cases đạt 100% PASS (17 assertions).

---

### Nhật ký feature P5-03 — Opportunity schema và domain

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_25_000003_create_opportunities_table.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`), số tiền `amount` (`decimal(15, 2)`), liên kết `pipeline_id`, `stage_id`, `company_id`, `contact_id`, `lead_id`, `owner_id`, `department_id`, `expected_close_date`, `actual_close_date`, `lost_reason`, `is_won`, `is_lost`, `softDeletes`.
- Tạo Eloquent Model `Opportunity` với các quan hệ đa hướng (`pipeline`, `stage`, `company`, `contact`, `lead`, `owner`, `department`, `attachments`), thuộc tính tính toán `weighted_value` (`amount * probability / 100`) và local scopes (`scopeWon`, `scopeLost`, `scopeOpen`).
- Bổ sung quan hệ `opportunities(): HasMany` trong model `Company` và `Contact`.
- Tạo `OpportunityFactory` và `DemoOpportunitySeeder` tạo 5 Cơ hội bán hàng mẫu ở các giai đoạn khác nhau. Đăng ký vào `DatabaseSeeder`.
- Viết `OpportunityDomainTest` kiểm thử 4 test cases đạt 100% PASS (18 assertions).

File chính:

- `database/migrations/2026_07_25_000003_create_opportunities_table.php`
- `app/Models/Opportunity.php`
- `app/Models/Company.php`
- `app/Models/Contact.php`
- `database/factories/OpportunityFactory.php`
- `database/seeders/DemoOpportunitySeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/OpportunityDomainTest.php`
