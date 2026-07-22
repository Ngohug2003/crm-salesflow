# SalesFlow CRM — Kế hoạch chuẩn hóa kỹ thuật

> Tài liệu này quản lý các feature sửa lệch kỹ thuật được phát hiện sau checkpoint P3-08. Đây không phải roadmap nghiệp vụ thay thế `PROJECT_PHASES.md`; sau khi hoàn tất từng feature kỹ thuật phải dừng để chủ dự án kiểm thử và xác nhận.

Ngày lập kế hoạch: **23/07/2026**.

## 1. Mục đích

- Sửa các khoảng lệch kiến trúc trước khi tiếp tục mở rộng nghiệp vụ từ P3-09.
- Giữ nguyên cây thư mục technical-layer hiện tại: Controller, Livewire, Form, Service, Repository, Policy và Model.
- Không gom nhiều thay đổi khó kiểm soát vào một branch.
- Mỗi feature phải có phạm vi, dependency, test tự động, checklist thủ công và checkpoint riêng.
- Không cài package mới nếu chưa có use case bắt buộc và chưa được chủ dự án xác nhận.

## 2. Quy tắc thực hiện

1. Chỉ làm đúng **một feature kỹ thuật** tại một thời điểm.
2. Trước khi code phải giải thích mục đích, vấn đề hiện tại, mục tiêu và phạm vi không làm.
3. Không đổi cấu trúc dự án sang `app/Modules` và không tạo generic `BaseRepository`.
4. Authorization, data scope, validation và duplicate guard phải được cưỡng chế ở backend.
5. Migration mới phải an toàn với dữ liệu hiện có và có đường rollback hợp lý.
6. Không đánh dấu hoàn tất nếu test liên quan, toàn bộ test, Pint, PHPStan và frontend build chưa đạt hoặc chưa ghi rõ blocker môi trường.
7. Sau khi hoàn tất phải cập nhật mục nhật ký của feature trong file này và dừng để chủ dự án kiểm thử.
8. Chỉ chuyển feature kế tiếp khi có xác nhận rõ ràng.

## 3. Cảnh báo tài liệu nguồn

- `PROJECT_PHASES.md` đang trỏ đến `docs/requirements.md`, nhưng file requirement này không tồn tại trên branch `develop` tại thời điểm lập kế hoạch.
- Skill `.agents/skills/salesflow-feature-development/SKILL.md` cũng không tồn tại trên branch hiện tại.
- P3-T07 phải khôi phục hoặc hợp nhất đúng hai tài liệu trên từ branch/commit chứa bản chuẩn trước khi chốt checkpoint kỹ thuật.
- Trong thời gian chưa khôi phục, phạm vi trong file này dựa trên code hiện tại, `PROJECT_PHASES.md` và kết quả audit sau P3-08.

## 4. Đánh giá tổng quan

| Hạng mục | Hiện trạng | Mức độ | Hướng xử lý |
|---|---|---:|---|
| Quy tắc Repository | Một số contract trả `Eloquent\Builder`; Service/Livewire có thể nối query; `LeadDirectoryService` query trực tiếp taxonomy | Cao | P3-T01 |
| Request ID | Chưa có request context thống nhất và response header | Cao | P3-T02 |
| Audit request ID | Audit chưa liên kết chắc chắn với request tạo ra thay đổi | Cao | P3-T02 |
| Logging | Chủ yếu dùng channel mặc định; thiếu structured context và quy tắc redaction chung | Trung bình | P3-T02 |
| Quản lý phiên đăng nhập | Có database session nhưng chưa có màn xem/thu hồi phiên | Cao | P3-T03 |
| Lead duplicate/trash | Cảnh báo duplicate và soft delete/restore đã có; Service vẫn có thể bị gọi mà không qua preflight từ UI; restore chưa xử lý xung đột duplicate | Cao | P3-T04 |
| App shell | Sidebar/dark mode/navigation đã có; global search, quick create, notification và user menu chưa hoàn chỉnh | Trung bình | P3-T05 |
| UI states | Loading/empty/success chưa đồng nhất; thiếu skeleton và error/retry chuẩn | Trung bình | P3-T06 |
| Modal/form | Chưa có quy tắc dùng modal/full page được ghi và áp dụng thống nhất | Trung bình | P3-T06 |
| Testing | Có test feature tốt nhưng thiếu test cho request context, session management và ranh giới Repository mới | Cao | P3-T07 tổng kiểm |
| Tài liệu | README/requirement có nguy cơ lệch hoặc thiếu trên branch hiện tại | Cao | P3-T07 |
| Phase log | `PROJECT_PHASES.md` trộn roadmap với nhật ký triển khai và đã quá dài | Trung bình | P3-T07 |

## 5. Danh sách feature kỹ thuật

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái | Kết quả cần đạt |
|---|---|---|---|---|---|
| P3-T01 | Chuẩn hóa Repository boundary | `feature/p3-t01-repository-boundary` | P3-08 | Chưa bắt đầu | Repository sở hữu toàn bộ query; Service/Livewire không nhận Builder |
| P3-T02 | Request context, Audit và Logging | `feature/p3-t02-request-context-logging` | P3-T01 | Chưa bắt đầu | Một request ID xuyên suốt response, log và audit |
| P3-T03 | Quản lý phiên đăng nhập | `feature/p3-t03-session-management` | P3-T02 | Chưa bắt đầu | Xem và thu hồi session đúng ownership, có xác nhận mật khẩu và audit |
| P3-T04 | Hoàn thiện Lead duplicate/trash | `feature/p3-t04-lead-duplicate-lifecycle` | P3-T01, P3-T02 | Chưa bắt đầu | Duplicate guard nằm ở backend service; restore xử lý xung đột an toàn |
| P3-T05 | Chuẩn hóa App shell | `feature/p3-t05-app-shell` | P3-T02 | Chưa bắt đầu | Layout responsive, điều hướng mượt và slot platform nhất quán |
| P3-T06 | UI states, modal và form | `feature/p3-t06-ui-foundation` | P3-T05 | Chưa bắt đầu | Loading/empty/error/success và modal/form có pattern dùng lại |
| P3-T07 | Quality gate, tài liệu và phase log | `feature/p3-t07-quality-docs` | P3-T01..P3-T06 | Chưa bắt đầu | Test tổng đạt; tài liệu đúng code; phase log được tách gọn |

Luồng dependency:

```text
P3-T01 Repository boundary
    └── P3-T02 Request context + Audit + Logging
          ├── P3-T03 Session management
          ├── P3-T04 Lead duplicate/trash
          └── P3-T05 App shell
                    └── P3-T06 UI states/modal/form
                              └── P3-T07 Quality/docs
                                        └── P3-09 Conversion
```

---

## P3-T01 — Chuẩn hóa Repository boundary

### Mục đích

Ngăn query Eloquent bị phân tán sang Livewire và Service, bảo đảm data scope luôn được áp dụng tại một nơi có thể kiểm thử.

### Hiện trạng và đánh giá

- `LeadRepository` đang public các method trả `Builder`: `visibleTo`, `filteredVisibleTo`, `trashedVisibleTo`.
- Code gọi Repository có thể nối thêm `where`, `find`, `count`, dẫn đến Repository không còn sở hữu toàn bộ query.
- `LeadDirectoryService` query trực tiếp `LeadSource` và `Tag`.
- Repository hiện đã làm tốt filter, sort, pagination và data scope; cần refactor boundary, không viết lại toàn bộ.

Đánh giá: **cần sửa trước khi thêm P3-09** để conversion không tiếp tục phụ thuộc query bị rò rỉ.

### Phạm vi triển khai

- Contract Repository chỉ trả Model, Collection, Paginator, DTO, scalar hoặc boolean.
- Đưa builder dùng chung thành private method trong Eloquent Repository.
- Bổ sung method có ý nghĩa nghiệp vụ như `countVisibleTo`, `findVisibleOrFail`, `paginateTrashedVisibleTo`.
- Tạo `LeadSourceRepository` và `TagRepository` khi taxonomy cần query dùng lại.
- Chuyển Livewire sang gọi Service thay vì tự lấy Repository cho các use case nghiệp vụ.
- Transaction tiếp tục thuộc Service; Repository chỉ đọc/ghi dữ liệu.

### Ngoài phạm vi

- Không tạo generic `BaseRepository`.
- Không thay ORM hoặc thay đổi schema Lead.
- Không đổi data scope/permission matrix.

### File dự kiến

- `app/Repositories/Contracts/LeadRepository.php`
- `app/Repositories/EloquentLeadRepository.php`
- `app/Repositories/Contracts/LeadSourceRepository.php`
- `app/Repositories/EloquentLeadSourceRepository.php`
- `app/Repositories/Contracts/TagRepository.php`
- `app/Repositories/EloquentTagRepository.php`
- `app/Providers/RepositoryServiceProvider.php`
- `app/Services/LeadDirectoryService.php`
- Các Livewire Lead đang gọi trực tiếp `LeadRepository`
- `tests/Feature/LeadRepositoryTest.php`
- Test kiến trúc mới cho Repository boundary

### Tiêu chí nghiệm thu

- Không có Repository contract nào public `Eloquent\Builder`.
- Không còn `Model::query()` trong Service thuộc phạm vi Lead.
- Data scope vẫn áp dụng cho list, detail, trash và duplicate.
- Không phát sinh N+1 ở danh sách Lead và thùng rác.
- Toàn bộ test Lead hiện có vẫn đạt.

### Checklist thủ công

1. Đăng nhập lần lượt Super Admin, Sales Manager, Sales và Viewer.
2. Kiểm tra danh sách, tìm kiếm, lọc, chi tiết và thùng rác vẫn đúng phạm vi.
3. Xác nhận user phòng A không nhìn thấy Lead phòng B.
4. Kiểm tra pagination và sort không thay đổi hành vi.

### Checkpoint

Dừng sau P3-T01 để chủ dự án kiểm thử list/detail/trash theo đủ role trước khi làm request context.

---

## P3-T02 — Request context, Audit và Logging

### Mục đích

Cho phép truy vết một thao tác từ HTTP response đến application log và audit database bằng cùng một mã request.

### Hiện trạng và đánh giá

- Chưa có middleware cấp request ID.
- `SystemAuditService` chưa lưu request ID chuẩn.
- Log đang dùng cấu hình Laravel chung và chưa có context thống nhất.
- Audit đã sanitize password/token và đã realtime; đây là nền tảng tốt để mở rộng.

Đánh giá: **ưu tiên cao**, nên làm trước session và các module nghiệp vụ mới.

### Phạm vi triển khai

- Tạo `AssignRequestId` middleware và `RequestContext` dùng trong toàn request.
- Chấp nhận inbound `X-Request-ID` chỉ khi đúng format/độ dài; nếu không thì sinh UUID mới.
- Gắn `request_id`, `user_id`, route và method vào log context.
- Trả `X-Request-ID` trong response.
- Thêm cột `request_id` có index vào `activity_log` hoặc một cơ chế first-class tương đương.
- `SystemAuditService` tự lấy request ID từ context; caller không truyền thủ công.
- Trang lỗi hiển thị mã tra cứu nhưng không lộ stack trace ở production.
- Cấu hình structured logging cho application/security; queue/import chỉ tạo khi use case bắt đầu.
- Redact password, token, cookie, authorization header, secret và session ID.

### Ngoài phạm vi

- Không lưu toàn bộ request body vào log/audit.
- Không triển khai hệ thống log tập trung bên ngoài trong feature này.
- Không cài monitoring package khi chưa chốt nhà cung cấp.

### File dự kiến

- `app/Http/Middleware/AssignRequestId.php`
- `app/Support/RequestContext.php`
- `bootstrap/app.php`
- Migration bổ sung `request_id` cho `activity_log`
- Model audit tùy chỉnh nếu Spatie Activity cần ánh xạ cột mới
- `app/Services/SystemAuditService.php`
- `config/logging.php`
- Error views/exception configuration liên quan
- Test request ID, logging context và audit correlation

### Tiêu chí nghiệm thu

- Mọi HTTP response có `X-Request-ID`.
- Request ID hợp lệ từ client được giữ lại; giá trị không hợp lệ bị thay mới.
- Audit tạo trong request có cùng request ID với response.
- Tìm audit theo request ID được hỗ trợ.
- Log và audit không chứa dữ liệu nhạy cảm.
- Audit realtime không bị gián đoạn.

### Checklist thủ công

1. Mở DevTools và xác nhận response có `X-Request-ID`.
2. Sửa một Lead rồi tìm đúng audit bằng request ID đó.
3. Gửi request ID không hợp lệ và xác nhận server sinh mã mới.
4. Gây một lỗi validation; xác nhận log có request ID nhưng không có password/token.

### Checkpoint

Dừng sau P3-T02 để chủ dự án kiểm tra response header, audit filter và log trước khi làm session management.

---

## P3-T03 — Quản lý phiên đăng nhập

### Mục đích

Cho người dùng biết tài khoản đang đăng nhập ở đâu và chủ động thu hồi phiên không còn tin cậy.

### Hiện trạng và đánh giá

- `SESSION_DRIVER=database` và bảng `sessions` đã có `user_id`, IP, user agent, payload, last activity.
- Chưa có route/UI quản lý session.
- Tài khoản inactive bị logout ở request kế tiếp, nhưng chưa chủ động xóa tất cả session khi khóa tài khoản.

Đánh giá: **thiếu chức năng bảo mật người dùng**, cần bổ sung trước production.

### Phạm vi triển khai

- Tạo trang `/settings/sessions` cho user hiện tại.
- Hiển thị thiết bị/trình duyệt, IP đã che, hoạt động cuối và dấu hiệu phiên hiện tại.
- Thu hồi một phiên khác.
- Đăng xuất tất cả phiên khác sau khi xác nhận mật khẩu.
- Hỗ trợ đăng xuất phiên hiện tại đúng chuẩn invalidate session và regenerate CSRF token.
- Khi khóa tài khoản hoặc đổi/reset mật khẩu, thu hồi session theo rule được chốt.
- Audit sự kiện revoke; chỉ lưu hash/đoạn nhận diện an toàn, không lưu session ID nguyên bản.
- Authorization bằng ownership ở backend, không tin session ID từ UI.

### Ngoài phạm vi

- Không cài package phân tích User-Agent ở bước đầu.
- Không xây trang Admin theo dõi session toàn hệ thống trong feature này.
- Không lưu vị trí địa lý từ IP.

### File dự kiến

- `app/Http/Controllers/SessionController.php`
- `app/Livewire/Settings/SessionManager.php`
- `app/Repositories/Contracts/SessionRepository.php`
- `app/Repositories/EloquentSessionRepository.php`
- `app/Services/SessionManagementService.php`
- `resources/views/settings/sessions.blade.php`
- `resources/views/livewire/settings/session-manager.blade.php`
- `routes/web.php`
- Navigation/user menu liên quan
- `tests/Feature/SessionManagementTest.php`

### Tiêu chí nghiệm thu

- User chỉ đọc và xóa session thuộc chính mình.
- Thu hồi session khiến thiết bị tương ứng bị logout ở request kế tiếp.
- Thao tác tất cả phiên khác yêu cầu xác nhận mật khẩu.
- Khóa tài khoản làm mất hiệu lực các session còn lại.
- Audit có actor, action, thời gian và request ID.

### Checklist thủ công

1. Đăng nhập cùng tài khoản bằng hai trình duyệt.
2. Xác nhận trang session hiển thị hai phiên và đánh dấu đúng phiên hiện tại.
3. Thu hồi phiên còn lại và kiểm tra trình duyệt kia bị logout.
4. Thử sửa request để xóa session user khác; phải bị từ chối.
5. Khóa user từ tài khoản Admin và xác nhận user bị logout.

### Checkpoint

Dừng sau P3-T03 để chủ dự án kiểm thử bằng ít nhất hai trình duyệt trước khi tiếp tục.

---

## P3-T04 — Hoàn thiện Lead duplicate/trash

### Mục đích

Đảm bảo duplicate guard không thể bị bỏ qua ngoài Livewire và restore không tạo xung đột dữ liệu âm thầm.

### Hiện trạng và đánh giá

- Đã normalize email/số điện thoại và có index.
- Đã tìm duplicate active + trashed theo data scope.
- Livewire cảnh báo, cho mở Lead cũ hoặc `Vẫn lưu riêng`.
- Đã soft delete/restore, giữ tag/history và audit.
- Duplicate preflight chủ yếu được điều phối trong `LeadEditor`; caller mới như API/import có nguy cơ gọi save mà không kiểm tra.
- Restore chưa cảnh báo khi một Lead active khác đang dùng contact giống Lead trong trash.
- P3-08 chủ ý chưa tự động merge/ghi đè.

Đánh giá: nền tảng **đã tốt nhưng boundary chưa đủ an toàn**.

### Phạm vi triển khai

- Đưa duplicate decision contract xuống Service backend.
- Recheck candidate ngay trước transaction lưu.
- Chuẩn hóa quyết định: cancel, open existing, save separately, restore existing; merge chỉ định nghĩa contract nếu chưa đủ domain.
- `save separately` yêu cầu xác nhận gắn với signature mới nhất và lưu audit lý do/candidate IDs.
- Restore phải chạy duplicate preflight với active Lead.
- Xử lý owner inactive và duplicate conflict trong cùng transaction an toàn.
- Không để duplicate query lộ bản ghi ngoài data scope.
- Giữ hard delete ngoài UI.

### Ngoài phạm vi

- Không tự động merge Lead trong feature này nếu chưa chốt rule tag/history/converted records.
- Không thêm Company/Contact/Opportunity.
- Không đặt unique constraint tuyệt đối cho email/phone vì hệ thống cho phép lưu riêng có chủ ý.

### File dự kiến

- DTO/enum cho duplicate decision trong technical layer hiện tại
- `app/Services/DuplicateLeadService.php`
- `app/Services/LeadManagementService.php`
- `app/Services/LeadLifecycleService.php`
- `app/Repositories/Contracts/LeadRepository.php`
- `app/Repositories/EloquentLeadRepository.php`
- `app/Livewire/Leads/LeadEditor.php`
- `app/Livewire/Leads/LeadTrash.php`
- Các view modal duplicate/restore
- `tests/Feature/LeadDuplicateLifecycleTest.php`

### Tiêu chí nghiệm thu

- Gọi Service trực tiếp vẫn bắt buộc duplicate decision.
- Đổi contact sau cảnh báo làm confirmation cũ mất hiệu lực.
- Lưu riêng có reason và audit request ID.
- Restore có duplicate active phải dừng và hiển thị lựa chọn.
- Hai user khác data scope không nhìn thấy candidate của nhau.
- Tag, assignment/status history và audit được giữ nguyên khi trash/restore.

### Checklist thủ công

1. Tạo Lead trùng email khác hoa/thường và kiểm tra modal.
2. Tạo Lead trùng số với định dạng `+84`, `0084`, dấu cách/gạch.
3. Chọn lưu riêng, nhập lý do và kiểm tra audit.
4. Xóa một Lead, tạo Lead active cùng contact rồi thử restore Lead cũ.
5. Kiểm tra Sales phòng A không thấy duplicate thuộc phòng B.

### Checkpoint

Dừng sau P3-T04 để chủ dự án kiểm thử duplicate create/edit/restore trước khi chạm UI nền tảng.

---

## P3-T05 — Chuẩn hóa App shell

### Mục đích

Tạo layout dùng chung ổn định, chuyển trang mượt và sẵn slot cho tính năng platform mà không query nặng ở mọi request.

### Hiện trạng và đánh giá

- Sidebar responsive, dark mode, active navigation và `wire:navigate` đã có.
- Global search/quick create/notification mới là placeholder hoặc chưa hoàn chỉnh.
- Layout cần được tách thành component nhỏ để tránh một file chứa quá nhiều trách nhiệm.

Đánh giá: **không chặn nghiệp vụ**, nhưng nên chuẩn hóa trước khi thêm nhiều module.

### Phạm vi triển khai

- Tách app layout thành sidebar, topbar, user menu và page header.
- Chuẩn hóa desktop/mobile navigation và active state.
- Giữ sidebar/topbar ổn định qua Livewire navigation bằng pattern phù hợp.
- Thêm quick-create theo permission cho các action đã tồn tại.
- Global search và notification có slot rõ ràng; chưa có backend thì hiển thị trạng thái disabled có chủ ý.
- User menu có profile/session/help/logout.
- Hiển thị trạng thái kết nối realtime ở vị trí phù hợp.
- Không chạy query danh sách nghiệp vụ trực tiếp trong layout.

### Ngoài phạm vi

- Không triển khai global search backend đầy đủ.
- Không triển khai notification center nghiệp vụ trước phase tương ứng.
- Không thay Flux UI bằng component library khác.

### File dự kiến

- App layout hiện tại
- Component layout sidebar/topbar/user-menu/page-header
- Navigation config/helper nếu cần
- CSS/JS nhỏ phục vụ persistent navigation
- Test navigation theo role

### Tiêu chí nghiệm thu

- Chuyển Dashboard, Leads, Users, Departments và Audit không full reload.
- Không nháy theme hoặc giật layout rõ rệt.
- Mobile navigation và keyboard focus hoạt động.
- Link/action ẩn đúng permission; backend vẫn authorize độc lập.
- Không tăng query lặp theo số lượng menu.

### Checklist thủ công

1. Chuyển liên tục giữa Dashboard, Lead và Phòng ban.
2. Kiểm tra desktop/mobile và dark/light mode.
3. Đăng nhập đủ role để kiểm tra navigation.
4. Tắt Reverb và xác nhận trạng thái kết nối hiển thị hợp lý.
5. Kiểm tra logout và link quản lý session từ user menu.

### Checkpoint

Dừng sau P3-T05 để chủ dự án đánh giá độ mượt và navigation trước khi chuẩn hóa từng page state.

---

## P3-T06 — UI states, modal và form

### Mục đích

Đảm bảo các màn hình có cùng cách phản hồi khi tải, không có dữ liệu, lỗi, thành công và thao tác form.

### Hiện trạng và đánh giá

- Một số màn có loading, empty và success nhưng cách trình bày chưa đồng nhất.
- Skeleton, error/retry và filtered-empty chưa đầy đủ ở các màn cũ.
- Form ngắn, form dài và modal chưa có decision rule được ghi rõ.

Đánh giá: **cần chuẩn hóa theo pattern dùng lại**, không refactor giao diện hàng loạt thiếu kiểm soát.

### Phạm vi triển khai

- Tạo component state dùng lại: loading, skeleton, empty, filtered-empty, error/retry và success feedback.
- Dùng Flux UI Free trước; fallback Blade/Tailwind khi component không có trong bản Free.
- Chuẩn hóa `wire:loading`, `wire:target` và disable submit để chống thao tác lặp.
- Form ngắn/action xác nhận dùng modal.
- Form dài, nhiều section hoặc cần URL riêng được phép dùng full page và phải ghi lý do.
- Validation nằm trong Livewire Form Object; save/mutation nằm trong Service.
- Khi mở modal phải reset data/error; quản lý focus, Escape và tab order.
- Áp dụng trước cho Lead, User, Department và Audit đang có.

### Ngoài phạm vi

- Không redesign toàn bộ thương hiệu/màu sắc.
- Không thêm JavaScript framework mới.
- Không chuyển Lead create/edit dài sang modal chỉ để đồng nhất hình thức.

### File dự kiến

- `resources/views/components/ui/*`
- Livewire views của Lead/User/Department/Audit
- Livewire components và Form Objects liên quan
- Test component cho state, reset modal và duplicate submit

### Tiêu chí nghiệm thu

- Mỗi trang dữ liệu có loading, empty, filtered-empty và error/retry hợp lý.
- Submit hai lần không tạo mutation trùng.
- Modal không giữ dữ liệu/error từ lần mở trước.
- Lead form tiếp tục full page với lý do được ghi; action ngắn dùng modal.
- UI responsive, dark mode và keyboard cơ bản đạt.

### Checklist thủ công

1. Dùng network throttling để quan sát loading/skeleton.
2. Tìm kiếm giá trị không tồn tại để kiểm tra filtered-empty.
3. Gây lỗi backend có kiểm soát và thử Retry.
4. Mở/đóng modal nhiều lần để kiểm tra reset.
5. Double-click nút Save và xác nhận chỉ có một mutation.

### Checkpoint

Dừng sau P3-T06 để chủ dự án kiểm thử UI trên desktop/mobile và dark mode.

---

## P3-T07 — Quality gate, tài liệu và phase log

### Mục đích

Chốt toàn bộ technical checkpoint bằng test, tài liệu đúng với code và phase file dễ bảo trì.

### Hiện trạng và đánh giá

- Source hiện có nhiều Pest feature tests, nhưng cần bổ sung test cho các boundary mới.
- Phiên audit gần nhất không chạy lại được runtime vì WSL không có lệnh Docker/PHP.
- `README.md` và trạng thái phase có nguy cơ lệch code.
- `docs/requirements.md` cùng skill dự án đang thiếu trên branch `develop`.
- `PROJECT_PHASES.md` đang chứa cả roadmap và nhật ký rất dài.

Đánh giá: **bắt buộc hoàn tất trước P3-09** để có baseline tin cậy.

### Phạm vi triển khai

- Bổ sung test Repository boundary, request ID, audit correlation, logging redaction, session ownership/revoke, duplicate restore conflict, navigation và UI state quan trọng.
- Tạo một quality command chuẩn chạy test, Pint, PHPStan và frontend build.
- Khôi phục/hợp nhất `docs/requirements.md` và skill dự án từ nguồn chuẩn sau khi xác định commit/branch.
- Cập nhật README đúng checkpoint P3-T07.
- Cập nhật architecture, security, audit, session, duplicate flow và deployment commands.
- Rút gọn `PROJECT_PHASES.md` thành roadmap/status/dependency/checkpoint link.
- Chuyển nhật ký dài sang `docs/checkpoints/P2/*`, `docs/checkpoints/P3/*` mà không làm mất lịch sử.
- File này tiếp tục là backlog và checkpoint cho remediation; sau khi hoàn tất có thể archive dưới docs.

### Ngoài phạm vi

- Không xóa lịch sử feature cũ.
- Không sửa lại Git history.
- Không đánh dấu pass nếu lệnh chưa chạy thật.
- Không làm CI/CD production thay cho P9.

### File dự kiến

- Các test Feature/Unit liên quan P3-T01..P3-T06
- `composer.json` nếu thêm script `quality`
- `README.md`
- `docs/requirements.md`
- `docs/architecture.md`
- `docs/database.md`
- `docs/deployment.md`
- `docs/permissions.md`
- `docs/checkpoints/P2/*`
- `docs/checkpoints/P3/*`
- `PROJECT_PHASES.md`
- Skill dự án dưới `.agents/skills/` sau khi khôi phục nguồn chuẩn

### Quality commands dự kiến

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose exec app php artisan migrate:status
docker compose ps
```

Nếu môi trường không có Docker/PHP, phải ghi rõ blocker và không đánh dấu hoàn tất.

### Tiêu chí nghiệm thu

- Test liên quan và toàn bộ suite đạt.
- Pint, PHPStan và Vite build đạt.
- Không còn link tài liệu bị hỏng trong README/phase plan.
- Requirement và skill chuẩn có mặt trên branch.
- `PROJECT_PHASES.md` chỉ còn thông tin điều phối cần thiết; nhật ký cũ vẫn truy cập được.
- Mỗi P3-T01..P3-T07 có checkpoint, kết quả lệnh và manual checklist thực tế.

### Checklist thủ công

1. Chạy toàn bộ quality commands trong Docker.
2. Mở các link từ README và phase plan.
3. Kiểm tra ngẫu nhiên checkpoint P2/P3 sau khi tách file.
4. Chạy lại luồng Login → Session → Lead duplicate → Trash/Restore → Audit.
5. Xác nhận commit history không bị rewrite và dữ liệu local không bị xóa ngoài chủ ý.

### Checkpoint

Dừng sau P3-T07 để chủ dự án nghiệm thu toàn bộ technical remediation. Chỉ sau xác nhận mới bắt đầu P3-09.

---

## 6. Mẫu nhật ký phải cập nhật sau mỗi feature

````markdown
### Nhật ký P3-Txx — Tên feature

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Mục đích và kết quả:

- ...

File đã tạo/sửa:

- `...`

Migration/package:

- Không có, hoặc liệt kê rõ migration/package và lý do.

Lệnh đã chạy:

```bash
docker compose exec app php artisan test ...
```

Kết quả xác minh:

- Test riêng: ...
- Toàn bộ suite: ...
- Pint: ...
- PHPStan: ...
- Vite build: ...

Checklist thủ công:

1. ...

Commit đề xuất:

`(feature-p3-txx): tiêu đề ngắn`

Checkpoint P3-Txx: dừng tại đây để chủ dự án kiểm thử.
````

## 7. Definition of Done cho từng feature kỹ thuật

- Đúng phạm vi của một feature và không kéo theo feature kế tiếp.
- Backend cưỡng chế authorization, ownership, data scope và validation.
- Không làm rò rỉ Builder/secret/session ID qua layer hoặc log.
- Có test cho happy path, permission denied, validation và edge case quan trọng.
- Test liên quan, toàn bộ test, Pint, PHPStan và frontend build đạt hoặc có blocker được chứng minh.
- UI có loading, error và duplicate-submit guard nếu feature có tương tác.
- Audit và request ID được áp dụng cho mutation nếu use case yêu cầu.
- Tài liệu/checkpoint được cập nhật đúng kết quả thực tế.
- Có commit title/body đề xuất.
- Đã dừng để chủ dự án nghiệm thu.

## 8. Trạng thái hiện tại

Technical remediation đang ở trạng thái: **đã lập kế hoạch, chưa bắt đầu P3-T01**.

Feature bắt đầu đề xuất: **P3-T01 — Chuẩn hóa Repository boundary**.
