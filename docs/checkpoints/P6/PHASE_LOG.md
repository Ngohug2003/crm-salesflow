# Giai đoạn 6 — Activities và Tasks

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Timeline tương tác, công việc, lịch và nhắc hạn cho các đối tượng CRM.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P6-01 | ✅ Activity polymorphic domain | `feature/p6-01-activity-domain` | P3-10, P4-07, P5-09 | Schema/model/enum/factory/seeder/test Activity đa hình với PostgreSQL BIGINT |
| P6-02 | Activity timeline CRUD | `feature/p6-02-activity-timeline` | P6-01 | UI Timeline tương tác CRUD cho Lead, Company, Contact, Opportunity |
| P6-03 | Task domain và CRUD | `feature/p6-03-task-crud` | P6-01 | Schema/model/CRUD Task với priority, assignee, deadline, status |
| P6-04 | Checklist và comments | `feature/p6-04-task-collaboration` | P6-03 | Tải file đính kèm, checklist công việc và thảo luận comment |
| P6-05 | Task list và Kanban | `feature/p6-05-task-views` | P6-03, P6-04 | Chế độ xem Công việc dạng Danh sách và Bảng Kanban |
| P6-06 | Calendar và reminders | `feature/p6-06-calendar-reminders` | P6-03 | Lịch công việc, nhắc hạn tự động và scheduler |
| P6-07 | Activity/Task checkpoint | `feature/p6-07-activity-task-checkpoint` | P6-01..P6-06 | Checkpoint nghiệm thu toàn bộ Giai đoạn 6 |

---

### Nhật ký feature P6-01 — Activity polymorphic domain

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_26_000001_create_activities_table.php` khóa chính PostgreSQL `BIGINT` tự tăng (`id`), chỉ mục kép trên `(subject_type, subject_id)` và `(user_id, performed_at)`.
- Tạo Enum `ActivityType`: `Call`, `Meeting`, `Email`, `Note`, `Task`, `Demo`, `FollowUp`.
- Tạo Eloquent Model `Activity` hỗ trợ `subject(): MorphTo`, `user(): BelongsTo`, `creator(): BelongsTo` và xóa mềm `SoftDeletes`.
- Cập nhật quan hệ `activities(): MorphMany` cho 4 Eloquent Models: `Lead`, `Company`, `Contact`, `Opportunity`.
- Khởi tạo `ActivityFactory` và `DemoActivitySeeder`.
- Viết `ActivityDomainTest` kiểm thử 4 test cases đạt 100% PASS (20 assertions).

File chính:

- `database/migrations/2026_07_26_000001_create_activities_table.php`
- `app/Enums/ActivityType.php`
- `app/Models/Activity.php`
- `app/Models/Lead.php`, `Company.php`, `Contact.php`, `Opportunity.php`
- `database/factories/ActivityFactory.php`
- `database/seeders/DemoActivitySeeder.php`
- `tests/Feature/ActivityDomainTest.php`
