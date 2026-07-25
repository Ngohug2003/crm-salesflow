# Giai đoạn 6 — Activities và Tasks

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Timeline tương tác, công việc, lịch và nhắc hạn cho các đối tượng CRM.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P6-01 | ✅ Activity polymorphic domain | `feature/p6-01-activity-domain` | P3-10, P4-07, P5-09 | Schema/model/enum/factory/seeder/test Activity đa hình với PostgreSQL BIGINT |
| P6-02 | ✅ Activity timeline CRUD | `feature/p6-02-activity-timeline` | P6-01 | UI Timeline tương tác CRUD cho Lead, Company, Contact, Opportunity |
| P6-03 | Task domain và CRUD | `feature/p6-03-task-crud` | P6-01 | Schema/model/CRUD Task với priority, assignee, deadline, status |
| P6-04 | Checklist và comments | `feature/p6-04-task-collaboration` | P6-03 | Tải file đính kèm, checklist công việc và thảo luận comment |
| P6-05 | Task list và Kanban | `feature/p6-05-task-views` | P6-03, P6-04 | Chế độ xem Công việc dạng Danh sách và Bảng Kanban |
| P6-06 | Calendar và reminders | `feature/p6-06-calendar-reminders` | P6-03 | Lịch công việc, nhắc hạn tự động và scheduler |
| P6-07 | Activity/Task checkpoint | `feature/p6-07-activity-task-checkpoint` | P6-01..P6-06 | Checkpoint nghiệm thu toàn bộ Giai đoạn 6 |

---

### Nhật ký feature P6-01 — Activity polymorphic domain

Trạng thái: **hoàn tất triển khai**.

- Tạo migration `2026_07_26_000001_create_activities_table.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`).
- Tạo Enum `ActivityType`: `Call`, `Meeting`, `Email`, `Note`, `Task`, `Demo`, `FollowUp`.
- Tạo Eloquent Model `Activity` hỗ trợ `subject(): MorphTo`, `user(): BelongsTo`.
- Cập nhật quan hệ `activities(): MorphMany` cho `Lead`, `Company`, `Contact`, `Opportunity`.
- Viết `ActivityDomainTest` kiểm thử 4 test cases đạt 100% PASS (20 assertions).

---

### Nhật ký feature P6-02 — Activity timeline CRUD

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo `ActivityFilterData` DTO, `ActivityRepository` & `EloquentActivityRepository` thực thi Data Scope isolation. Đăng ký binding trong `RepositoryServiceProvider`.
- Tạo `ActivityPolicy` cưỡng chế phân quyền `activities.view`, `activities.create`, `activities.update`, `activities.delete` và Data Scope.
- Tạo `ActivityManagementService` xử lý CRUD Activity trong `DB::transaction()` và ghi vết kiểm toán qua `SystemAuditService`.
- Nâng cấp `CustomerTimelineService` tổng hợp hiển thị các bản ghi `Activity` trên Dòng thời gian.
- Nâng cấp Livewire component `CustomerTimelineFeed` và Blade view:
  - Bổ sung nút bấm **+ Tạo Hoạt động** và Modal nhập thông tin tương tác (Loại, Tiêu đề, Thời gian, Thời lượng, Địa điểm, Mô tả).
  - Bộ lọc hoạt động theo loại (`ActivityType`).
  - Nút bấm Sửa / Xóa mềm (Soft delete) bản ghi hoạt động tương tác trực tiếp trên Dòng thời gian.
- Viết `ActivityTimelineTest` kiểm thử 3 test cases đạt 100% PASS (9 assertions).

File chính:

- `app/Data/ActivityFilterData.php`
- `app/Repositories/Contracts/ActivityRepository.php` & `EloquentActivityRepository.php`
- `app/Policies/ActivityPolicy.php`
- `app/Services/ActivityManagementService.php`
- `app/Services/CustomerTimelineService.php`
- `app/Livewire/Customers/CustomerTimelineFeed.php` & `customer-timeline-feed.blade.php`
- `tests/Feature/ActivityTimelineTest.php`
