# Giai đoạn 5 — Pipelines và Opportunities

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Quản lý pipeline có cấu hình, opportunity lifecycle, Kanban và realtime.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P5-01 | ✅ Pipeline và stage schema | `feature/p5-01-pipeline-domain` | P2-08 | Schema/model/factory/test Pipeline và Stage với PostgreSQL BIGINT tự tăng |
| P5-02 | ✅ Quản lý pipeline/stage | `feature/p5-02-pipeline-management` | P5-01 | Quản lý danh sách, cấu hình stage, sắp xếp và quy định bảo vệ |
| P5-03 | ✅ Opportunity schema và domain | `feature/p5-03-opportunity-domain` | P4-02, P5-01 | Schema/model/factory/test Opportunity liên kết Company/Contact/Pipeline/Stage |
| P5-04 | ✅ Opportunity CRUD và weighted value | `feature/p5-04-opportunity-crud` | P5-03 | Dịch vụ, Repository, Policy và Livewire CRUD cho Opportunity |
| P5-05 | ✅ Stage transition và history | `feature/p5-05-stage-transition-history` | P5-04 | Chuyển stage có lưu lịch sử immutable và kiểm tra version conflict |
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

Trạng thái: **hoàn tất triển khai**.

- Tạo migration `2026_07_25_000003_create_opportunities_table.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`), số tiền `amount` (`decimal(15, 2)`).
- Tạo Eloquent Model `Opportunity` với các quan hệ đa hướng và thuộc tính tính toán `weighted_value`.
- Tạo `OpportunityFactory` và `DemoOpportunitySeeder`.
- Viết `OpportunityDomainTest` kiểm thử 4 test cases đạt 100% PASS (18 assertions).

---

### Nhật ký feature P5-04 — Opportunity CRUD và weighted value

Trạng thái: **hoàn tất triển khai**.

- Tạo `OpportunityFilterData` DTO đóng gói bộ lọc cơ hội bán hàng.
- Xây dựng `OpportunityRepository` & `EloquentOpportunityRepository` thực thi Data Scope isolation và tính toán tổng số tiền & Giá trị dự báo (`summarizeVisible`).
- Tạo `OpportunityPolicy` cưỡng chế phân quyền kết hợp Data Scope.
- Tạo `OpportunityManagementService` xử lý CRUD Cơ hội bán hàng và `OpportunityList`, `OpportunityEditor`, `OpportunityDetail` views.
- Viết `OpportunityCrudTest` kiểm thử 6 test cases đạt 100% PASS (18 assertions).

---

### Nhật ký feature P5-05 — Stage transition và history

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_25_000004_create_opportunity_stage_histories_table.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`), `opportunity_id`, `from_stage_id`, `to_stage_id`, `user_id`, `notes`, `duration_seconds` (tự động lưu thời gian dừng ở stage cũ) và `created_at`.
- Tạo `StaleOpportunityException` phục vụ kiểm soát xung đột ghi đồng thời (Version Conflict / Concurrent Edit Protection).
- Tạo Eloquent Model `OpportunityStageHistory` và bổ sung quan hệ `stageHistories(): HasMany` trong `Opportunity`.
- Xây dựng `OpportunityStageTransitionService` thực thi trong `DB::transaction()` với pessimistic lock (`lockForUpdate()`), kiểm tra quyền `opportunities.change-stage`, kiểm tra xung đột version `expectedCurrentStageId`, tự động tính toán `duration_seconds`, ghi bản ghi Lịch sử immutable và phát log kiểm toán `SystemAuditService`.
- Nâng cấp `CustomerTimelineService` hiển thị các mốc chuyển Stage cùng thông tin thời gian tạm dừng ở Stage cũ trực tiếp trên Dòng thời gian.
- Cập nhật Livewire component `OpportunityDetail` hỗ trợ người dùng nhấp trực tiếp vào các ô Stage trên thanh tiến trình để chuyển Stage nhanh có xác nhận.
- Viết `OpportunityStageTransitionTest` kiểm thử 3 test cases đạt 100% PASS (12 assertions).

File chính:

- `database/migrations/2026_07_25_000004_create_opportunity_stage_histories_table.php`
- `app/Exceptions/StaleOpportunityException.php`
- `app/Models/OpportunityStageHistory.php`
- `app/Models/Opportunity.php`
- `app/Services/OpportunityStageTransitionService.php`
- `app/Services/CustomerTimelineService.php`
- `app/Livewire/Opportunities/OpportunityDetail.php` & `opportunity-detail.blade.php`
- `tests/Feature/OpportunityStageTransitionTest.php`
