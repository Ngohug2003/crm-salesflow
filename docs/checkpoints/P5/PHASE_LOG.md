# Giai đoạn 5 — Pipelines và Opportunities

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Quản lý pipeline có cấu hình, opportunity lifecycle, Kanban và realtime.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P5-01 | ✅ Pipeline và stage schema | `feature/p5-01-pipeline-domain` | P2-08 | Schema/model/factory/test Pipeline và Stage với PostgreSQL BIGINT tự tăng |
| P5-02 | ✅ Quản lý pipeline/stage | `feature/p5-02-pipeline-management` | P5-01 | Quản lý danh sách, cấu hình stage, sắp xếp và quy định bảo vệ |
| P5-03 | Opportunity schema và domain | `feature/p5-03-opportunity-domain` | P4-02, P5-01 | Schema/model/factory/test Opportunity liên kết Company/Contact/Pipeline/Stage |
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

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo `PipelineFilterData` DTO đóng gói bộ lọc quy trình.
- Xây dựng `PipelineRepository` & `EloquentPipelineRepository` thực thi Data Scope isolation và duy nhất cờ `is_default`. Đăng ký binding trong `RepositoryServiceProvider`.
- Tạo `PipelinePolicy` cưỡng chế phân quyền `pipelines.view`, `pipelines.create`, `pipelines.update`, `pipelines.delete`, `pipelines.manage` kết hợp Data Scope.
- Tạo `PipelineManagementService` xử lý CRUD Quy trình & Giai đoạn trong `DB::transaction()`, tự động sắp xếp thứ tự `position`, bảo vệ giai đoạn hệ thống (`is_system = true`) và ghi log kiểm toán qua `SystemAuditService`.
- Tạo các Livewire Components & Blade views Full-width:
  - `PipelineList` (`/pipelines`): Danh sách quy trình, tìm kiếm, lọc trạng thái, phân trang, toggle kích hoạt và chọn mặc định nhanh.
  - `PipelineEditor` (`/pipelines/create`, `/pipelines/{id}/edit`): Form tạo/sửa quy trình và quản lý giai đoạn (thêm stage, sửa tên/mã/xác suất %/màu sắc, di chuyển thứ tự lên/xuống, xóa stage không phải system).
  - `PipelineDetail` (`/pipelines/{id}`): Trang xem chi tiết Quy trình bán hàng và sơ đồ giai đoạn.
- Cấu hình routes `/pipelines/*` trong `routes/web.php` và bật menu **Quy trình bán hàng** trên Sidebar.
- Viết `PipelineManagementTest` kiểm thử 6 test cases đạt 100% PASS (17 assertions).

File chính:

- `app/Data/PipelineFilterData.php`
- `app/Repositories/Contracts/PipelineRepository.php`
- `app/Repositories/EloquentPipelineRepository.php`
- `app/Providers/RepositoryServiceProvider.php`
- `app/Policies/PipelinePolicy.php`
- `app/Services/PipelineManagementService.php`
- `app/Livewire/Pipelines/PipelineList.php` & `pipeline-list.blade.php`
- `app/Livewire/Pipelines/PipelineEditor.php` & `pipeline-editor.blade.php`
- `app/Livewire/Pipelines/PipelineDetail.php` & `pipeline-detail.blade.php`
- `config/crm.php`
- `routes/web.php`
- `resources/views/layouts/partials/_sidebar.blade.php`
- `tests/Feature/PipelineManagementTest.php`
