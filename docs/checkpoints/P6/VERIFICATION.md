# Báo cáo xác minh P6-07 — Activities và Tasks

> Trạng thái: kiểm thử tự động đạt, đang chờ chủ dự án kiểm thử thủ công và xác nhận checkpoint.

## 1. Kết quả kiểm thử tự động (Automated Test Suites)

Hệ thống đã trải qua 25 test cases kiểm thử tự động cho Giai đoạn 6 (107 assertions):

| Test Suite | Test Cases | Kết quả | Chi tiết |
|---|---|---|---|
| `ActivityDomainTest` | 4 | PASS ✅ | Polymorphic schema, Enum `ActivityType`, PostgreSQL BIGINT key, SoftDeletes |
| `ActivityTimelineTest` | 3 | PASS ✅ | Interactive Timeline Feed, CRUD hoạt động tương tác |
| `TaskDomainTest` | 4 | PASS ✅ | Task schema, polymorphic subject, HTML sanitization và CRUD |
| `TaskCollaborationTest` | 2 | PASS ✅ | Task Checklist management, Comments & Discussions |
| `TaskViewsTest` | 1 | PASS ✅ | Interactive Kanban board & Task status transition |
| `TaskCalendarReminderTest` | 2 | PASS ✅ | Calendar view & Scheduled Console Command `tasks:send-reminders` |
| `Phase6CheckpointTest` | 4 | PASS ✅ | E2E, data scope, subject scope và attachment authorization |
| `TaskMentionAndDetailTest` | 3 | PASS ✅ | Trang chi tiết, mention notification và không mở rộng quyền |
| `TaskDescriptionSanitizerTest` | 2 | PASS ✅ | Allowlist rich text, loại script/event/URL nguy hiểm và tương thích plain text |
| **Tổng cộng** | **25** | **100% PASS** | **107 assertions** |

## 2. Kết quả Quality Gates

- **Pint Code Formatting**: PASS ✅ (325 files clean, 0 style issues).
- **PHPStan Static Analysis**: PASS ✅ (`[OK] No errors`).
- **Vite production build**: PASS ✅ (`vite build`, 730 modules transformed).
- **npm production audit**: PASS ✅ (`found 0 vulnerabilities`).
- **PostgreSQL Key Strategy**: Khóa chính `BIGINT` tự tăng áp dụng đồng bộ trên tất cả các bảng `activities`, `tasks`, `task_checklists`, `task_comments`.

## 3. Danh sách tính năng hoàn thành

- **P6-01**: Activity polymorphic domain & seeders.
- **P6-02**: Activity timeline CRUD tương tác trên Lead, Company, Contact, Opportunity.
- **P6-03**: Task domain & CRUD quản lý công việc có Data Scope & permissions.
- **P6-04**: Task collaboration (Checklists có tiến độ %, Comments thảo luận & Tệp đính kèm).
- **P6-05**: Task dual views (Danh sách & Bảng Kanban 4 cột kéo thả mượt mà).
- **P6-06**: Task & Activity Calendar view và Console Command/Scheduler phát thông báo nhắc hạn tự động.
- **P6-07**: Phase 6 Checkpoint nghiệm thu toàn diện.

## 4. Các điểm đã sửa khi rà soát P6-07

- Task và Activity tiếp tục áp dụng data scope trước filter/list/detail/calendar.
- Viewer giữ read-only kể cả khi bị gán nhầm permission ghi.
- `@mention` không cấp quyền xem hoặc sửa Task; chỉ người đã có quyền xem mới nhận notification.
- Attachment authorize theo Task/Company/Contact/Opportunity cha ở cả Livewire và Service.
- Reminder dùng transaction + row lock để không gửi lặp.
- Subject và assignee của Task được kiểm tra data scope ở backend.
- Dùng Quill `2.0.2` từ npm/Vite, không CDN; backend sanitize allowlist trước lưu/đọc và chỉ render HTML đã làm sạch.

## 5. Cơ chế vận hành Hệ thống Thông báo (Notification & Scheduler Engine)

- **Notification đích danh**: Lệnh `tasks:send-reminders` phát `TaskReminderNotification` lưu trữ trực tiếp vào cơ sở dữ liệu `notifications` dành riêng cho tài khoản được phân công (`assigned_to`).
- **Dropdown 🔔 Topbar**: Tích hợp `<livewire:notification-menu />` hiển thị danh sách thông báo chưa đọc, số badge màu đỏ và nút đánh dấu đã đọc.
- **Tự động hóa ngầm**:
  - **Môi trường thật (Production)**: Cấu hình Linux Cron Job `* * * * * cd /path-to-project && php artisan schedule:run` quét tự động mỗi phút.
  - **Môi trường Local**: Chạy lệnh `docker compose exec app php artisan schedule:work` để lắng nghe ngầm.

## 6. Checklist kiểm thử thủ công

1. Đăng nhập Sales và xác nhận chỉ thấy Task do mình tạo/được phân công.
2. Đăng nhập Sales Manager và xác nhận thấy Task của cùng phòng ban, không thấy phòng ban khác.
3. Đăng nhập Viewer và xác nhận xem được nhưng không sửa/xóa/check checklist.
4. Tạo Task, chọn nhiều người tham gia và kiểm tra trang chi tiết.
5. Mention một người tham gia Task; xác nhận chuông thông báo xuất hiện và mở đúng Task.
6. Chạy `php artisan tasks:send-reminders` hai lần; xác nhận chỉ có một thông báo cho cùng reminder.
7. Thử đổi ID attachment/Task ngoài scope; hệ thống phải trả 403/404 và không lộ dữ liệu.
8. Tạo/sửa mô tả bằng tiêu đề, in đậm/nghiêng/gạch chân, danh sách, trích dẫn và liên kết; tải lại trang để xác nhận định dạng được giữ nguyên.
