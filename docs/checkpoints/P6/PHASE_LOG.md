# Giai đoạn 6 — Activities và Tasks

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Timeline tương tác, công việc, lịch và nhắc hạn cho các đối tượng CRM.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P6-01 | ✅ Activity polymorphic domain | `feature/p6-01-activity-domain` | P3-10, P4-07, P5-09 | Schema/model/enum/factory/seeder/test Activity đa hình với PostgreSQL BIGINT |
| P6-02 | ✅ Activity timeline CRUD | `feature/p6-02-activity-timeline` | P6-01 | UI Timeline tương tác CRUD cho Lead, Company, Contact, Opportunity |
| P6-03 | ✅ Task domain và CRUD | `feature/p6-03-task-crud` | P6-01 | Schema/model/CRUD Task với priority, assignee, deadline, status |
| P6-04 | ✅ Checklist và comments | `feature/p6-04-task-collaboration` | P6-03 | Tải file đính kèm, checklist công việc và thảo luận comment |
| P6-05 | ✅ Task list và Kanban | `feature/p6-05-task-views` | P6-03, P6-04 | Chế độ xem Công việc dạng Danh sách và Bảng Kanban |
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

---

### Nhật ký feature P6-03 — Task domain và CRUD

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_27_000001_create_tasks_table.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`), composite indexes và quan hệ đa hình (`subject`).
- Tạo các Enum `TaskStatus` (`todo`, `in_progress`, `completed`, `cancelled`) và `TaskPriority` (`low`, `medium`, `high`, `urgent`).
- Tạo Eloquent Model `Task` với casts và quan hệ `subject(): MorphTo`, `assignee(): BelongsTo`, `creator(): BelongsTo`.
- Cập nhật quan hệ `tasks(): MorphMany` cho các Model `Lead`, `Company`, `Contact`, `Opportunity`.
- Tạo `TaskFilterData` DTO, `TaskRepository` contract & `EloquentTaskRepository` thực thi Data Scope isolation. Đăng ký binding trong `RepositoryServiceProvider`.
- Tạo `TaskPolicy` cưỡng chế phân quyền `tasks.view`, `tasks.create`, `tasks.update`, `tasks.delete`.
- Tạo `TaskManagementService` quản lý CRUD Task trong `DB::transaction()` và ghi vết kiểm toán qua `SystemAuditService`.
- Nâng cấp `CustomerTimelineService` tổng hợp hiển thị các bản ghi `Task` trên Customer Timeline feed.
- Tạo Livewire component `TaskList` và Blade view `task-list.blade.php`:
  - Giao diện quản lý danh sách công việc, lọc theo tiêu đề, trạng thái, mức độ ưu tiên, người thực hiện.
  - Modal tạo/chỉnh sửa công việc mượt mà sử dụng Tailwind & Alpine.js transition.
  - Chức năng đánh dấu hoàn thành nhanh qua checkbox.
- Khai báo route `/tasks` và thêm menu "Công việc (Tasks)" trên Sidebar navigation.
- Viết `TaskDomainTest` kiểm thử 3 test cases đạt 100% PASS (15 assertions).

File chính:

- `database/migrations/2026_07_27_000001_create_tasks_table.php`
- `app/Enums/TaskStatus.php` & `TaskPriority.php`
- `app/Models/Task.php`
- `app/Data/TaskFilterData.php`
- `app/Repositories/Contracts/TaskRepository.php` & `EloquentTaskRepository.php`
- `app/Policies/TaskPolicy.php`
- `app/Services/TaskManagementService.php`
- `app/Livewire/Tasks/TaskList.php` & `task-list.blade.php`
- `tests/Feature/TaskDomainTest.php`

---

### Nhật ký feature P6-04 — Checklist và comments

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_28_000001_create_task_checklists_and_comments_tables.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`).
- Tạo các Eloquent Model `TaskChecklist` và `TaskComment` (hỗ trợ SoftDeletes cho bình luận).
- Cập nhật Model `Task` bổ sung các quan hệ `checklists()`, `comments()`, `attachments()`.
- Tạo `TaskChecklistService` quản lý các thao tác thêm, toggle hoàn thành, xóa hạng mục checklist trong `DB::transaction()` kèm Log Audit.
- Tạo `TaskCommentService` quản lý đăng bình luận và xóa thảo luận trong `DB::transaction()` kèm Log Audit.
- Tạo Livewire component `TaskDetailModal` và Blade view `task-detail-modal.blade.php`:
  - Giao diện Modal xem chi tiết Task tập trung.
  - Quản lý danh sách kiểm tra (Checklist) có thanh tiến độ `%` tự động tính toán.
  - Tích hợp `CustomerAttachmentManager` để quản lý tệp đính kèm trên Task.
  - Khu vực thảo luận & bình luận thời gian thực cho thành viên dự án.
- Bổ sung nút **Chi tiết** trên từng dòng Task ở danh sách `task-list.blade.php`.
- Viết `TaskCollaborationTest` kiểm thử 2 test cases đạt 100% PASS (7 assertions).

File chính:

- `database/migrations/2026_07_28_000001_create_task_checklists_and_comments_tables.php`
- `app/Models/TaskChecklist.php` & `TaskComment.php`
- `app/Services/TaskChecklistService.php` & `TaskCommentService.php`
- `app/Livewire/Tasks/TaskDetailModal.php` & `task-detail-modal.blade.php`
- `tests/Feature/TaskCollaborationTest.php`

---

### Nhật ký feature P6-05 — Task list và Kanban

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Bổ sung phương thức `updateStatus(User $actor, int $id, string $status)` trong `TaskManagementService` hỗ trợ đổi trạng thái trực tiếp.
- Tạo Livewire Component `TaskKanban` và Blade View `task-kanban.blade.php`:
  - Bảng Kanban 4 cột tương ứng theo các trạng thái công việc (`Cần làm`, `Đang làm`, `Hoàn thành`, `Đã hủy`).
  - Thẻ công việc (Task card) thiết kế trực quan với mã màu theo mức độ ưu tiên, hiển thị người thực hiện và hạn chót.
  - Các nút thao tác chuyển nhanh công việc giữa các cột Kanban.
  - Tích hợp Modal xem chi tiết Task (`TaskDetailModal`) mở trực tiếp từ thẻ Kanban.
- Khai báo route `/tasks/kanban` và bổ sung nút Toggle chuyển đổi chế độ xem (List / Kanban) trên tiêu đề trang Quản lý công việc.
- Viết `TaskViewsTest` kiểm thử 1 test case chuyển cột trạng thái Kanban đạt 100% PASS (6 assertions).

File chính:

- `app/Services/TaskManagementService.php`
- `app/Livewire/Tasks/TaskKanban.php` & `task-kanban.blade.php`
- `resources/views/livewire/tasks/task-list.blade.php`
- `routes/web.php`
- `tests/Feature/TaskViewsTest.php`



