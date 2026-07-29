# SalesFlow CRM — Product & Engineering Requirements

## 1. Trạng thái tài liệu

- Phiên bản: `2.2`
- Hiệu lực từ: `30/07/2026`
- Trạng thái: **nguồn yêu cầu chuẩn của dự án (canonical source of truth)**
- Phạm vi áp dụng: mọi feature từ `P3-09` trở đi và mọi phần code cũ được chỉnh sửa lại.
- Nguồn tổng hợp: `SalesFlow CRM Design.pdf`, các quyết định kiến trúc đã triển khai và yêu cầu trực tiếp của chủ dự án.

Tài liệu này quy định **hệ thống phải làm gì**, **code phải được tổ chức ra sao** và **một feature đạt điều kiện hoàn thành khi nào**. `PROJECT_PHASES.md` chỉ dùng để chia feature và ghi nhật ký triển khai; không được dùng để thay thế requirement.

### 1.1 Thứ tự ưu tiên khi có xung đột

1. Yêu cầu mới nhất được chủ dự án xác nhận trực tiếp.
2. Tài liệu `docs/requirements.md` này.
3. Phạm vi feature và dependency trong `PROJECT_PHASES.md`.
4. Tài liệu thiết kế gốc `SalesFlow CRM Design.pdf`.
5. Các tài liệu kỹ thuật khác trong `docs/`.

Không tự chọn một yêu cầu thấp hơn khi phát hiện xung đột. Phải ghi rõ xung đột, quyết định được áp dụng và cập nhật tài liệu liên quan trong cùng feature.

### 1.2 Các quyết định đã chốt thay cho PDF gốc

| Mã | Quyết định chuẩn |
|---|---|
| DEC-001 | Khóa chính application domain dùng PostgreSQL `BIGINT` tự tăng, không dùng UUID/ULID. |
| DEC-002 | Múi giờ nghiệp vụ và hiển thị là `Asia/Ho_Chi_Minh`; timestamp phải có cách lưu/đọc nhất quán và được test. |
| DEC-003 | Flux UI bản miễn phí là component library chính. Chỉ dùng Flux Pro khi có license hợp lệ. |
| DEC-004 | Không cài package theo suy đoán. Chỉ cài khi feature có nhu cầu thực tế, đã kiểm tra package và nêu rõ lý do. |
| DEC-005 | Audit toàn hệ thống là immutable trên UI; chỉ Super Admin hoặc Admin thuộc phòng IT được xem. |
| DEC-006 | Mật khẩu demo chỉ được điền sẵn khi `APP_ENV=local`; tuyệt đối không xuất hiện ngoài local. |
| DEC-007 | UI và thông báo người dùng dùng tiếng Việt; tên class, method, biến và schema dùng tiếng Anh rõ nghĩa. |
| DEC-008 | Giữ cây thư mục hiện tại theo technical layer; không bắt buộc chuyển domain code vào `app/Modules/<Module>`. |
| DEC-009 | Form tạo/sửa Lead sử dụng full-page thay vì modal do form dài nhiều trường và cần URL riêng; các tác vụ phân công/chuyển trạng thái/xóa/khôi phục/xung đột trùng lặp sử dụng modal. |
| DEC-010 | Không cài package ngoài bừa bãi để tránh phình mã nguồn, chỉ dùng các thư viện core và Flux UI Free đã được phê duyệt. |
| DEC-011 | `config/crm.php` là catalog quyền duy nhất. Route, Policy/Gate, Livewire action và sidebar không được dùng tên quyền ngoài catalog; mọi route CRM xác thực phải có `can:` middleware hoặc một backend boundary được khai báo và kiểm thử. |

## 2. Mục tiêu sản phẩm

SalesFlow CRM là CRM SaaS monolith được tách boundary nghiệp vụ rõ ràng nhưng giữ cấu trúc source theo technical layer hiện tại, quản lý toàn bộ vòng đời bán hàng:

```text
Lead → phân công → chăm sóc/đánh giá → Company + Contact
     → Opportunity → Pipeline → Won/Lost → báo cáo
```

Hệ thống phải vượt mức CRUD cơ bản bằng phân quyền theo phạm vi dữ liệu, workflow có lịch sử, audit, file private, queue, realtime, import/export và báo cáo dữ liệu thật.

### 2.1 Người dùng mục tiêu

- Nhân viên kinh doanh.
- Trưởng nhóm/Quản lý kinh doanh.
- Quản trị viên hệ thống.
- Người xem báo cáo theo quyền.

### 2.2 Module sản phẩm

| Mã | Module | Trách nhiệm chính |
|---|---|---|
| MOD-AUTH | Authentication | Đăng nhập, xác minh email, reset/đổi mật khẩu, phiên đăng nhập, khóa tài khoản. |
| MOD-RBAC | Users & RBAC | User, Department, Role, Permission, data scope và policy. |
| MOD-LEAD | Leads | Tiếp nhận, phân loại, phân công, trạng thái, trùng lặp, chuyển đổi, import/export. |
| MOD-CUSTOMER | Companies & Contacts | Hồ sơ khách hàng, quan hệ, người phụ trách, timeline, duplicate và file. |
| MOD-SALES | Pipelines & Opportunities | Pipeline cấu hình, stage, opportunity, Kanban, won/lost và forecast. |
| MOD-WORK | Activities & Tasks | Timeline, lịch, task, checklist, comment, reminder và notification. |
| MOD-ANALYTICS | Dashboard & Reports | KPI, funnel, doanh thu, hiệu suất, forecast và export. |
| MOD-PLATFORM | Platform | Global search, attachments, notifications, audit, import/export và vận hành. |

## 3. Công nghệ và giới hạn

### 3.1 Backend

- PHP `8.3+`, Laravel stable hiện tại của dự án.
- PostgreSQL, Redis, Eloquent ORM.
- Laravel Queue, Horizon, Scheduler, Reverb, Broadcasting và Sanctum.
- Fortify cho authentication backend.
- Pest, Laravel Pint và Larastan/PHPStan.
- Spatie Permission và Spatie Activity Log.

### 3.2 Frontend

- Blade, Livewire 3, Flux UI, Alpine.js, Tailwind CSS và Vite.
- SortableJS chỉ cài khi triển khai Kanban/reorder.
- Chart.js hoặc ApexCharts chỉ chọn một khi bắt đầu dashboard/report.
- Flatpickr chỉ dùng nếu Flux UI không đáp ứng date picker cần thiết.

### 3.3 Không sử dụng

- Vue, React, Inertia.js, jQuery, Bootstrap hoặc AdminLTE.
- Package không rõ nguồn gốc hoặc package chưa có use case.
- Mã Flux Pro khi chưa có license.
- Business logic trong Blade hoặc JavaScript phía trình duyệt.

## 4. Kiến trúc chuẩn

### 4.1 Luồng xử lý

```text
Web UI:
Route → Livewire Component → Service/Action → Repository → Model → PostgreSQL

API/Webhook/Download:
Route → Form Request → Controller → Service/Action → Repository → Model
      → API Resource/Response

Async:
Service/Action → Event → Listener → Queue Job → Notification/Email/Export
```

Mọi side effect phụ thuộc dữ liệu đã commit phải phát sau transaction. Workflow có nguy cơ chạy lặp phải idempotent.

### 4.2 Trách nhiệm từng layer

| Layer | Được làm | Không được làm |
|---|---|---|
| Route | Khai báo endpoint, middleware, route model binding | Nghiệp vụ, query dữ liệu |
| Livewire | UI state, filter/sort/page, modal, validation, authorization, gọi use case | Query dài, transaction, email, workflow phức tạp |
| Livewire Form | State, validation, normalize, fill/reset | Repository, authorization, transaction |
| Controller | API, webhook, export/download hoặc page boundary rất mỏng | Nghiệp vụ, query phức tạp |
| Form Request | Validate/authorize HTTP input | Persist dữ liệu |
| Service | Điều phối nhiều use case hoặc dependency | Trở thành god class |
| Action | Một use case cụ thể, transaction khi phù hợp | Nhiều trách nhiệm không liên quan |
| Repository | Query, scope, filter, sort, pagination, eager load, persistence | Authorization, notification, HTTP response |
| Model | Mapping dữ liệu, relation, cast, local invariant nhỏ | Workflow/transaction phức tạp |
| Policy | Quyền trên backend và object/data scope | Chỉ phục vụ ẩn/hiện UI |
| DTO | Dữ liệu typed giữa các layer | Query hoặc side effect |
| Event | Sự kiện nghiệp vụ đã xảy ra | Thực hiện công việc nặng |
| Listener/Job | Side effect, tác vụ nền, retry/backoff | Tin tưởng dữ liệu client chưa kiểm tra |

### 4.3 Cây thư mục chuẩn

```text
app/
├── Actions/
├── Broadcasting/
├── Data/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Listeners/
├── Livewire/
│   ├── Forms/
│   └── <Module>/
├── Models/
├── Modules/                  # Thư mục dự phòng hiện có, chưa dùng cho code mới
├── Policies/
├── Providers/
├── Repositories/
│   ├── Contracts/
│   └── Eloquent<Name>Repository.php
├── Services/
│   └── Authorization/
├── Shared/                   # Thư mục dự phòng hiện có
└── Support/

resources/views/
├── components/
├── layouts/
├── livewire/<module>/
├── emails/
└── pdf/
```

Quy tắc áp dụng:

- Model đặt trong `app/Models`; Service trong `app/Services`; Policy trong `app/Policies`.
- Repository contract đặt trong `app/Repositories/Contracts`; Eloquent implementation giữ trực tiếp trong `app/Repositories` theo cấu trúc hiện tại.
- Use-case Action đặt trong `app/Actions`; DTO/data object đặt trong `app/Data`; Enum/Event/Listener/Exception đặt trong layer cùng tên.
- UI Livewire đặt trong `app/Livewire/<Module>` và view trong `resources/views/livewire/<module>`.
- `app/Modules` và `app/Shared` hiện là thư mục dự phòng; không đưa code mới vào đó nếu chưa có quyết định kiến trúc mới từ chủ dự án.
- Không di chuyển code hiện tại giữa các cây thư mục chỉ để khớp PDF gốc.
- Không tạo sẵn toàn bộ folder/file mẫu. Chỉ tạo file khi feature có nhu cầu thật.
- Interface chỉ tạo khi có boundary cần thay thế hoặc repository/query phức tạp; không tạo interface hình thức.

### 4.4 Routing và API

- Web route nghiệp vụ dùng middleware `auth`, `verified` và `account.active`.
- URL chính giữ theo module: `/dashboard`, `/leads`, `/companies`, `/contacts`, `/pipelines`, `/opportunities`, `/activities`, `/tasks`, `/reports` và `/settings/*`.
- API có version dưới `/api/v1` cho mobile tương lai, webhook, tích hợp và automation.
- Không tạo API chỉ để Livewire gọi lại chính Laravel khi server action trực tiếp đã đủ.
- API input dùng Form Request; output public dùng Resource/Collection; mọi endpoint có rate limit và authorization phù hợp.

## 5. UI/UX Design System

### 5.1 Ngôn ngữ thiết kế

- CRM SaaS hiện đại, tối giản, chuyên nghiệp và dễ đọc dữ liệu.
- Khoảng trắng rõ ràng, card bo góc vừa phải, shadow nhẹ, màu trạng thái nhất quán.
- Desktop, tablet và mobile đều phải sử dụng được.
- Light, dark và system mode; lưu lựa chọn, không nháy theme khi tải.
- Không thay đổi pattern UI tùy ý giữa các module.
- Dùng semantic HTML, label/aria phù hợp, focus visible, thứ tự tab hợp lý và màu có độ tương phản đọc được.

### 5.2 App shell bắt buộc

```text
Sidebar | Topbar
        | Breadcrumb
        | Page title + primary actions
        | Filters / context actions
        | Main content
```

Sidebar phải có icon, active state, collapse, submenu khi cần, mobile drawer và lưu trạng thái local. Topbar phải dành vị trí cho sidebar toggle, global search, quick create, notifications, user menu và dark-mode toggle.

### 5.3 Thứ tự chọn component

1. Dùng Flux UI nếu component đáp ứng.
2. Kết hợp Flux UI với Tailwind cho layout.
3. Tự xây Blade + Alpine + Tailwind khi Flux Free thiếu component.
4. Chỉ đề xuất package mới sau khi chứng minh ba lựa chọn trên không đủ.

Không bọc Flux component bằng wrapper không tạo thêm giá trị nghiệp vụ hoặc khả năng tái sử dụng thật.

### 5.4 Page states bắt buộc

Mỗi màn hình tương tác phải xử lý phù hợp:

- Initial loading và loading khi thay đổi dữ liệu.
- Skeleton cho vùng dữ liệu chính.
- Empty state khi chưa có dữ liệu.
- Empty-filter state và nút xóa/reset filter.
- Error state có cách thử lại.
- Disabled/processing state chống submit lặp.
- Success/error feedback; validation đặt gần field.
- Confirmation cho hành động nguy hiểm.
- Responsive và keyboard/focus state.

### 5.5 Data table

Table nghiệp vụ phải cân nhắc và triển khai theo phạm vi feature:

- Search, filter, allowlisted sort, pagination và per-page.
- URL query synchronization; reload/back/forward không mất state.
- Bulk selection chỉ nhận ID actor đang được phép xem.
- Bulk action phải re-authorize ở backend.
- Responsive, empty/loading states, sticky header khi phù hợp.
- Không tải toàn bộ dữ liệu rồi filter ở frontend.

### 5.6 Modal

- Dùng Flux Modal cho create/edit nhanh, assignment, conversion, close won/lost, quick task và confirmation.
- Modal có title, description, focus management, validation, loading và mobile layout.
- Không đóng khi đang submit nếu có nguy cơ mất dữ liệu.
- Reset state khi đóng.
- Modal mở từ backend phải bind state bằng `wire:model` hoặc API Flux chính thức và có Livewire test cho trạng thái mở/đóng.
- Form dài hoặc cần URL chia sẻ có thể dùng page riêng nếu feature ghi rõ lý do/trade-off.

### 5.7 Feedback

- Toast dùng cho kết quả ngắn: tạo, sửa, xóa, phân công, quyền hoặc lỗi kết nối.
- Callout/modal dùng cho nội dung cần đọc hoặc quyết định.
- Không chỉ đổi giao diện mà thiếu thông báo cho screen reader.

## 6. Authentication, authorization và data scope

### 6.1 Authentication

- Login/logout, forgot/reset password, email verification, đổi password và cập nhật hồ sơ.
- Active/locked state được kiểm tra khi login và trong middleware.
- Không tiết lộ tài khoản có tồn tại trong forgot-password flow.
- Session/cookie production phải secure, HttpOnly và SameSite phù hợp.

### 6.2 Role mặc định

- `super-admin`: Gate bypass có kiểm soát, toàn hệ thống.
- `admin`: quản trị hệ thống theo permission được seed.
- `sales-manager`: dữ liệu phòng ban và nghiệp vụ quản lý.
- `sales`: dữ liệu sở hữu và nghiệp vụ được giao.
- `viewer`: read-only trong phạm vi được cấp.

### 6.3 Enforcement

- Mỗi model nghiệp vụ có Policy hoặc authorization boundary tương đương.
- Ẩn nút ở UI không thay thế authorization backend.
- Data scope `all`, `department`, `owned`, `read-only` được áp dụng trước filter/search.
- Query global search, report, export, attachment và realtime channel cũng phải áp dụng data scope.
- Mutation phải kiểm tra cả permission và visibility của record.
- Không trả metadata của record ngoài scope qua duplicate detection hoặc error message.

### 6.4 Route, sidebar và permission catalog — `REQ-AUTH-NAV`

- `config/crm.php` là nguồn chuẩn duy nhất cho tên permission và role mapping; không tạo catalog quyền hard-code thứ hai trong Service, Blade hoặc test.
- Route list/create/report/settings và entry point toàn cục phải có `can:` middleware tương ứng, kể cả khi Livewire/Controller kiểm tra lại bên trong.
- Route theo một record phải authorize bằng Policy tại Controller/Livewire/Service trước khi đọc hoặc mutate dữ liệu. Route cá nhân như notification, phiên đăng nhập và trợ giúp phải giới hạn theo chính người đang đăng nhập.
- Route không dùng `can:` middleware phải được khai báo tại `crm.rbac.route_access_exceptions` kèm backend boundary cụ thể; route mới không được để ở trạng thái chưa phân loại.
- Sidebar chỉ hiển thị link khi cùng permission/Policy với route đích. Ẩn menu không thay thế việc trả `403` khi truy cập URL trực tiếp.
- Mỗi lần thêm, đổi hoặc xóa route/menu/permission phải quét **toàn bộ** route CRM và sidebar, không chỉ route của feature đang làm; test phải phát hiện permission không tồn tại, route chưa phân loại và menu lệch quyền.
- Các action Livewire có thể được gọi độc lập phải authorize lại theo quyền thao tác (`view`, `create`, `update`, `delete`, `assign`, `approve`...), không chỉ dựa vào kiểm tra lúc mở trang.

## 7. Yêu cầu chức năng

### 7.1 Leads — `REQ-LEAD`

- List/create/edit/detail, soft delete/restore, search/filter/sort/page và tags.
- Owner/department, assignment history, status history và audit.
- Trạng thái: `new`, `contacted`, `qualified`, `unqualified`, `converted`, `lost`.
- Priority: `low`, `medium`, `high`, `urgent`.
- Duplicate theo email/phone chuẩn hóa; cảnh báo trước lưu và không tự merge.
- Bulk assign/status/delete phải re-authorize và ghi audit.
- Conversion chỉ cho Lead đủ điều kiện, chống convert lặp và chạy transaction.
- Conversion đích tạo/ghép Company, Contact và Opportunity theo quyết định người dùng.
- Import/export của Lead chạy theo platform workflow, không xử lý file lớn trong web request.
- File private, notes và unified timeline phải được hoàn tất trước checkpoint Lead cuối.

### 7.2 Companies — `REQ-COMPANY`

- CRUD, soft delete/restore, owner/department và data scope.
- Tên, mã số thuế, website, email, phone, ngành, quy mô, doanh thu và địa chỉ.
- Quan hệ Contacts, Opportunities, activities, notes và attachments.
- Duplicate/merge phải an toàn, có preview quyết định và audit.

### 7.3 Contacts — `REQ-CONTACT`

- CRUD, Company relation, job title, birthday, preferred contact, owner và scope.
- Hỗ trợ thông tin liên hệ mở rộng khi use case yêu cầu.
- Timeline, notes, attachments và opportunities liên quan.
- Chuyển Company phải có authorization, history/audit và không làm mất relation.

### 7.4 Pipelines & Opportunities — `REQ-PIPELINE`

- Nhiều pipeline; stage có thứ tự, màu, probability mặc định, won/lost/system flags.
- Không xóa stage đang được sử dụng nếu chưa có migration decision.
- Opportunity liên kết Company/Contact/Pipeline/Stage/Owner.
- `weighted_value = amount × probability / 100` tính ở backend bằng decimal an toàn.
- Stage transition lưu immutable history, kiểm tra quyền và hỗ trợ version conflict.
- Kanban dùng Livewire + Alpine + SortableJS, optimistic UI và rollback khi backend lỗi.
- Không reload toàn trang; dữ liệu lớn tải theo cột/chunk.
- Realtime qua private Reverb channel, không broadcast dữ liệu nhạy cảm.
- Won/lost yêu cầu lý do/ngày phù hợp và có rule reopen rõ ràng.

### 7.5 Activities & Tasks — `REQ-WORK`

- Activity polymorphic cho Lead, Company, Contact và Opportunity.
- Loại cơ bản: call, meeting, email, note, task, demo, follow-up.
- Task có assignee, priority, deadline, status, checklist, comment, file và reminder.
- Trạng thái task: `todo`, `in_progress`, `blocked`, `completed`, `cancelled`.
- List, My Tasks, team tasks, overdue, completed, Kanban và calendar.
- Reminder/scheduler phải idempotent, có test time boundary và không gửi lặp.

### 7.6 Dashboard & Reports — `REQ-REPORT`

- KPI lấy dữ liệu thật và luôn áp data scope.
- Filter: preset ngày, custom range, department, user và pipeline.
- Lead source/status, conversion funnel, revenue, forecast, win rate, loss reason, sales cycle, task/activity performance và leaderboard.
- Chart có loading, empty, error, responsive và dark mode.
- Report lớn chạy queue, tạo file private, signed URL và tự hết hạn.

### 7.7 Import/Export — `REQ-IO`

```text
Upload → MIME/size check → Preview → Column mapping → Validate
       → Chunked jobs → Result/error file → Realtime notification
```

- File tạm/private, tên an toàn, không xử lý file lớn trong request.
- Retry/backoff, progress, row-level errors và idempotency.
- Duplicate strategy: skip, update hoặc create new theo lựa chọn có quyền.
- Mọi query export phải scoped; file tải qua signed/authorized route.

### 7.8 Notifications — `REQ-NOTIFY`

- Database, email và broadcast notification theo use case.
- Notification center: list, unread badge, mark read/all read và xóa theo policy.
- Assignment, stage/won/lost, task due/overdue, import/export completion và invitation.
- Private channel có authorization theo user/department/permission.

### 7.9 Audit — `REQ-AUDIT`

- Lưu actor, event/action, subject, old/new values, IP, user agent, timestamp và request ID.
- Bao phủ create/update/delete/restore, assignment, role/permission, import/export, file và workflow quan trọng.
- Mask password, token, secret và field nhạy cảm.
- Không sửa/xóa audit từ UI thông thường.
- List/search/filter/detail; realtime chỉ gửi payload tối thiểu cần thiết.
- Access tuân theo `DEC-005`.

### 7.10 Global search & attachments — `REQ-PLATFORM`

- Global search nhóm Lead/Company/Contact/Opportunity, debounce, keyboard navigation và permission-aware.
- Attachment polymorphic, private, MIME/size check, tên server-generated, preview ảnh và progress.
- Download/delete luôn authorize; production dùng S3/MinIO-compatible storage.

## 8. Database và dữ liệu

- Dùng `BIGINT` tự tăng theo `DEC-001` và foreign key cùng kiểu.
- Foreign key, unique constraint, soft delete và timestamps khi phù hợp.
- Tiền dùng `decimal`, không dùng float cho tính toán tài chính.
- Status/priority cố định dùng backed Enum và cast.
- Index theo query thực tế: normalized email/phone, status, owner, department, stage, closing date, created date và foreign keys.
- Không tạo index cho mọi cột; feature phải giải thích index mới phục vụ query nào.
- Migration rollback được khi khả thi; backfill tách rõ và an toàn với dữ liệu hiện hữu.
- Seeder demo idempotent, dữ liệu đủ trực quan nhưng không chứa secret production.
- Test dùng database cô lập, không chạm PostgreSQL local của developer.

## 9. Security và privacy

- CSRF, escaped output, validation server-side và mass-assignment protection.
- Không render raw HTML chưa sanitize.
- Rate limit cho auth, API, import/export và action nhạy cảm.
- File private, signed download, MIME/size allowlist và filename an toàn.
- Không log password/token/secret hoặc broadcast dữ liệu nhạy cảm.
- Không commit `.env`; production tắt debug và dùng secret thực.
- Nginx/middleware có security headers phù hợp.
- Exception production không trả stack trace; log có request ID, user ID, module, action và context không nhạy cảm.

### 9.1 Error handling và logging

- Dùng exception nghiệp vụ rõ nghĩa thay cho exception chung khi caller cần xử lý quyết định nghiệp vụ.
- UI chuyển exception đã biết thành thông báo tiếng Việt thân thiện; lỗi không xác định dùng message an toàn và request ID để tra cứu.
- Tách log channel/application context phù hợp cho application, queue, import, export, audit và realtime.
- Structured context ưu tiên `request_id`, `user_id`, subject ID, `job_id`, action và `duration_ms`.
- Không dùng log thay cho audit và không dùng audit thay cho operational log.

## 10. Queue, realtime và scheduler

- Job có timeout, retry, backoff, failure handling và structured log.
- Job idempotent khi có khả năng retry; không serialize model graph lớn.
- Horizon được bảo vệ; queue riêng cho import/export/notification khi cần.
- Scheduler chạy mỗi phút trong container riêng.
- Realtime dùng private channel, channel authorization và reconnect/error feedback.
- Client realtime phải deduplicate event và refresh dữ liệu server-authoritative.

## 11. Testing và quality gates

### 11.1 Test tối thiểu theo feature

- Unit: Enum, DTO, normalizer, calculation và business rule.
- Feature: route/middleware, Policy/data scope, persistence và transaction rollback.
- Livewire: render, validation, URL state, filter/page, modal, authorization và feedback.
- Queue/realtime: dispatch-after-commit, retry/failure, notification và channel authorization.
- File: upload validation, private download authorization và delete.
- Navigation/RBAC: mọi route xác thực có middleware hoặc boundary đã khai báo; permission route/sidebar tồn tại trong catalog; URL trực tiếp bị từ chối khi thiếu quyền.

Không chạy theo coverage hình thức; mọi happy path, validation path, permission denial và critical edge case phải được test.

### 11.2 Quality gates

Mỗi feature phải chạy trong Docker khi Docker khả dụng:

```bash
docker compose exec app php artisan test <targeted tests>
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --no-progress
docker compose exec vite npm run build
```

Feature có migration phải migrate/rollback hoặc fresh-migrate phù hợp. Feature thay package phải chạy audit và kiểm tra lockfile. Không đánh dấu hoàn tất nếu gate liên quan chưa đạt; blocker môi trường phải được ghi rõ, không báo pass suy đoán.

## 12. Docker, CI/CD và production

- Compose gồm app, nginx, postgres, redis, horizon, scheduler, reverb, mailpit và minio.
- Healthcheck, named volume, environment variables và development override.
- Local bind-mount source và Vite HMR; sửa code không yêu cầu rebuild image trừ dependency/system layer.
- Production image multi-stage, tối ưu autoloader/assets và chạy non-root khi khả thi.
- CI: Composer install/audit, npm ci/audit, Pest, Pint, PHPStan, frontend build và Docker image build.
- Production: HTTPS, secure cookie, config/view cache, queue, protected Horizon, Reverb riêng, backup, log rotation, monitoring, error tracking và health endpoint.

### 12.1 Demo data và tài liệu vận hành

- Seeder tạo Super Admin, Admin, Sales Manager, nhiều Sales, departments và dữ liệu demo xuyên suốt các module đã hoàn thành.
- Demo seed phải idempotent và đủ dữ liệu để nhìn rõ list/filter/dashboard; không dùng password demo trong production.
- README phải mô tả tính năng, tech stack, kiến trúc, cây thư mục, Docker, migration/seed, Horizon, Reverb, Scheduler, test, demo account, API, trade-off, roadmap và deployment.
- Tài liệu phải cập nhật trong cùng feature khi command, environment variable, schema, quyền hoặc luồng vận hành thay đổi.

## 13. Coding standards

- Tuân thủ PSR-12 và Laravel Pint; dùng `declare(strict_types=1)` khi phù hợp với codebase.
- Khai báo parameter/return/property type rõ ràng; dùng backed Enum thay chuỗi trạng thái rải rác.
- Ưu tiên constructor property promotion và readonly DTO/service khi state thực sự immutable.
- Không dùng magic number/string; đưa business constant về Enum/config/value object phù hợp.
- Method ngắn, một mức trừu tượng; tách use case thay vì tạo god Service/Component.
- Không lặp query/validation/business rule giữa nhiều entry point.
- Comment giải thích lý do, invariant hoặc trade-off; không diễn giải lại code hiển nhiên.
- Không over-engineer, không tạo interface/wrapper/base class khi chưa có nhu cầu thay thế hoặc tái sử dụng thật.
- Eager-load relation phục vụ view/list để tránh N+1; query nặng phải có giới hạn và kế hoạch index/cache.

## 14. Quy trình triển khai một feature

### 14.1 Bước 1 — Giải thích và dừng chờ xác nhận

Trước khi code feature mới, phải trình bày:

1. Mục đích nghiệp vụ.
2. Kết quả cuối người dùng nhìn thấy.
3. Phạm vi làm và không làm.
4. Dependency và rủi ro.
5. Requirement ID áp dụng.
6. Tiêu chí nghiệm thu thủ công.

Chỉ triển khai sau khi chủ dự án trả lời xác nhận như “OK, làm đi”.

### 14.2 Bước 2 — Triển khai đúng một feature

- Kiểm tra branch và dirty worktree; không ghi đè thay đổi không thuộc feature.
- Đọc toàn bộ requirement liên quan và section feature trong `PROJECT_PHASES.md`.
- Thiết kế flow/layer/file trước khi sửa code.
- Tái sử dụng Flux/component hiện có; không cài package ngoài phạm vi.
- Thêm migration, code, test và docs đúng nhu cầu thực tế.
- Không kéo refactor không liên quan vào feature.

### 14.3 Bước 3 — Xác minh và checkpoint

- Chạy targeted tests và các quality gate liên quan.
- Khi feature thay route, sidebar, Policy/Gate hoặc permission: chạy test đối chiếu toàn hệ thống route–sidebar–RBAC và cập nhật `crm.rbac.route_access_exceptions` nếu có boundary hợp lệ không dùng `can:`.
- Kiểm tra Docker logs nếu thay runtime/queue/realtime.
- Cập nhật `PROJECT_PHASES.md`: file, migration, command, test, manual checklist và quyết định.
- Dừng để chủ dự án kiểm thử; không tự sang feature tiếp theo.
- Đề xuất commit theo mẫu:

```text
feat(pX-YY): tiêu đề ngắn

- Thay đổi nghiệp vụ chính
- Boundary authorization/data scope
- Test và tài liệu đã bổ sung
```

## 15. Definition of Done

Một feature chỉ hoàn tất khi:

- Đúng phạm vi và requirement ID.
- Business logic nằm đúng technical layer hiện tại.
- Authorization và data scope được cưỡng chế ở backend.
- Route, sidebar và permission catalog đã đồng bộ theo `REQ-AUTH-NAV`; không còn quyền chết hoặc route chưa phân loại.
- Validation và transaction đúng; không có duplicate submit.
- UI có state cần thiết, responsive, dark mode và keyboard cơ bản.
- Audit/event/queue/realtime được áp dụng nếu use case yêu cầu.
- Test quan trọng đạt; Pint/PHPStan/build đạt hoặc có blocker môi trường được chứng minh.
- Migration/seed idempotent và dữ liệu local không bị phá ngoài chủ ý.
- Docs/checkpoint và manual test checklist được cập nhật.
- Có commit title/body đề xuất.
- Đã dừng để chủ dự án nghiệm thu.

## 16. Các khoảng lệch hiện tại và cách xử lý

| Mã gap | Hiện trạng | Quy tắc xử lý |
|---|---|---|
| GAP-UI-001 | App shell chưa hoàn chỉnh global search, quick create và notification center. | Hoàn thiện tại feature platform tương ứng; giữ slot/pattern nhất quán từ các UI mới. |
| GAP-UI-002 | Một số create/edit flow đang dùng full page thay vì modal như thiết kế gốc. | Feature tiếp theo chạm flow phải đánh giá modal; dùng full page chỉ khi ghi rõ lý do theo mục 5.6. |
| GAP-UI-003 | Một số màn cũ thiếu skeleton/error/empty-filter/feedback đầy đủ. | Bổ sung khi chạm màn hình và chốt toàn bộ ở P9-01. |
| GAP-LEAD-001 | Lead còn thiếu conversion hoàn chỉnh, attachments, import/export và bulk workflow cuối. | Theo dependency P3/P5/P8; không nhồi vào một feature. |
| GAP-FRONTEND-001 | SortableJS và chart library chưa được cài. | Đây là chủ ý theo DEC-004; chỉ cài ở P5-06 và P7 khi use case bắt đầu. |
| GAP-DOC-001 | `PROJECT_PHASES.md` đang trộn kế hoạch và nhật ký dài. | Requirement nằm ở file này; phase file chỉ tiếp tục làm checkpoint và lịch sử, không phát sinh quy tắc kiến trúc mới. |
| GAP-PLATFORM-001 | Request ID và structured operational log chưa có correlation chuẩn trong code nền tảng ban đầu. | P1-T01 bổ sung HTTP/log context; P2-T02 tiếp tục liên kết request ID first-class vào Audit database. |

Mỗi feature từ `P3-09` phải ghi requirement ID và gap ID liên quan trong phần nhật ký để tránh lệch thiết kế lặp lại.
