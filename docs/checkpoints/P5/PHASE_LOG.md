# Giai đoạn 5 — Pipelines và Opportunities

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Quản lý pipeline có cấu hình, opportunity lifecycle, Kanban và realtime.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P5-01 | ✅ Pipeline và stage schema | `feature/p5-01-pipeline-domain` | P2-08 | Schema/model/factory/test Pipeline và Stage với PostgreSQL BIGINT tự tăng |
| P5-02 | Quản lý pipeline/stage | `feature/p5-02-pipeline-management` | P5-01 | Quản lý danh sách, cấu hình stage, sắp xếp và quy định bảo vệ |
| P5-03 | Opportunity schema và domain | `feature/p5-03-opportunity-domain` | P4-02, P5-01 | Schema/model/factory/test Opportunity liên kết Company/Contact/Pipeline/Stage |
| P5-04 | Opportunity CRUD và weighted value | `feature/p5-04-opportunity-crud` | P5-03 | Dịch vụ, Repository, Policy và Livewire CRUD cho Opportunity |
| P5-05 | Stage transition và history | `feature/p5-05-stage-transition-history` | P5-04 | Chuyển stage có lưu lịch sử immutable và kiểm tra version conflict |
| P5-06 | Opportunity Kanban | `feature/p5-06-opportunity-kanban` | P5-05 | Giao diện Kanban kéo thả Livewire + Alpine + SortableJS |
| P5-07 | Realtime private broadcast | `feature/p5-07-opportunity-realtime` | P5-06 | Đồng bộ Kanban realtime qua Reverb private channel |
| P5-08 | Close won/lost workflow | `feature/p5-08-opportunity-close` | P5-05 | Quy trình đóng cơ hội Won/Lost có bắt buộc lý do và rule reopen |
| P5-09 | Lead conversion integration và checkpoint | `feature/p5-09-lead-conversion-checkpoint` | P3-09, P4-02, P5-01..P5-08 | Tích hợp chuyển đổi Lead và checkpoint nghiệm thu Giai đoạn 5 |

---

### Nhật ký feature P5-01 — Pipeline và stage schema

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_25_000001_create_pipelines_table.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`), các trường `name`, `code`, `is_default`, `is_active`, Data Scope (`owner_id`, `department_id`), audit fields và `softDeletes`.
- Tạo migration `2026_07_25_000002_create_pipeline_stages_table.php` khóa chính `BIGINT` tự tăng (`id`), `pipeline_id` (foreignKey cascadeOnDelete), `position`, `probability` (0-100%), `color`, `is_won`, `is_lost`, `is_system`, `softDeletes`.
- Tạo Eloquent Model `Pipeline` (scopes `scopeActive`, `scopeDefault`, quan hệ `stages`, `owner`, `department`, `createdBy`, `updatedBy`) và `PipelineStage` (scopes `scopeOrdered`, `scopeOpen`, quan hệ `pipeline`).
- Tạo `PipelineFactory`, `PipelineStageFactory` hỗ trợ dữ liệu mẫu.
- Tạo `DemoPipelineSeeder` khởi tạo quy trình mặc định **"Quy trình Bán hàng Standard"** (`is_default = true`) với 6 giai đoạn tiêu chuẩn (Liên hệ ban đầu 10%, Phân tích nhu cầu 30%, Báo giá 50%, Thương lượng 80%, Won 100%, Lost 0%). Đăng ký vào `DatabaseSeeder`.
- Viết `PipelineDomainTest` kiểm thử 3 test cases đạt 100% PASS (20 assertions).

File chính:

- `database/migrations/2026_07_25_000001_create_pipelines_table.php`
- `database/migrations/2026_07_25_000002_create_pipeline_stages_table.php`
- `app/Models/Pipeline.php`
- `app/Models/PipelineStage.php`
- `database/factories/PipelineFactory.php`
- `database/factories/PipelineStageFactory.php`
- `database/seeders/DemoPipelineSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/PipelineDomainTest.php`
