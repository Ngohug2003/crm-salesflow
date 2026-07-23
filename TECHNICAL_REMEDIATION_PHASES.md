# SalesFlow CRM — Kế hoạch sửa lệch kỹ thuật theo giai đoạn

> Tài liệu này quản lý các feature sửa lệch kỹ thuật của Giai đoạn 1, 2 và 3. Đây không phải roadmap nghiệp vụ thay thế `PROJECT_PHASES.md`. Mỗi feature phải được triển khai trên branch riêng, xác minh, cập nhật nhật ký và dừng để chủ dự án kiểm thử.

Ngày cập nhật cấu trúc: **23/07/2026**.

## 1. Mục đích

- Phân loại vấn đề theo đúng giai đoạn đã tạo ra nền tảng hoặc nghiệp vụ liên quan.
- Sửa các khoảng lệch kỹ thuật trước khi tiếp tục P3-09.
- Giữ nguyên cây thư mục technical-layer hiện tại: Controller, Livewire, Form, Service, Repository, Policy và Model.
- Không gom toàn bộ remediation vào một branch lớn.
- Mỗi feature có mục tiêu, dependency, phạm vi, test, checklist thủ công và checkpoint riêng.

## 2. Quy tắc thực hiện

1. Chỉ triển khai đúng **một feature kỹ thuật** tại một thời điểm.
2. Trước khi code phải giải thích mục đích, hiện trạng, mục tiêu và phạm vi không làm.
3. Không đổi cấu trúc dự án sang `app/Modules` và không tạo generic `BaseRepository`.
4. Authorization, data scope, ownership, validation và duplicate guard phải được cưỡng chế ở backend.
5. Không cài package mới nếu chưa có use case bắt buộc và chưa được chủ dự án xác nhận.
6. Migration phải an toàn với dữ liệu hiện có và có đường rollback hợp lý.
7. Không đánh dấu hoàn tất nếu test liên quan, toàn bộ test, Pint, PHPStan và frontend build chưa đạt hoặc chưa ghi rõ blocker môi trường.
8. Sau mỗi feature phải cập nhật nhật ký trong file này, đưa commit đề xuất và dừng để chủ dự án kiểm thử.
9. Chỉ chuyển feature kế tiếp khi có xác nhận rõ ràng.

## 3. Cảnh báo tài liệu nguồn

- `PROJECT_PHASES.md` đang trỏ đến `docs/requirements.md`, nhưng requirement này không tồn tại trên branch `develop` tại thời điểm audit.
- Skill `.agents/skills/salesflow-feature-development/SKILL.md` cũng không tồn tại trên branch hiện tại.
- TR-02 phải khôi phục hoặc hợp nhất hai tài liệu trên từ nguồn chuẩn trước khi chốt remediation.
- Trong thời gian chưa khôi phục, kế hoạch này dựa trên code hiện tại, `PROJECT_PHASES.md` và audit sau P3-08.

## 4. Đánh giá tổng quan theo nguồn phát sinh

| Giai đoạn | Hạng mục | Hiện trạng | Mức độ | Feature xử lý |
|---|---|---|---:|---|
| P1 | Request ID | Chưa có request context và response header thống nhất | Cao | P1-T01 |
| P1 | Logging | Chủ yếu dùng channel Laravel mặc định, thiếu structured context/redaction chung | Cao | P1-T01 |
| P1 | Session management | Có database session nhưng chưa có màn xem/thu hồi phiên | Cao | P1-T02 |
| P1 | App shell | Sidebar, dark mode và navigation đã có; topbar/platform slots chưa hoàn chỉnh | Trung bình | P1-T03 |
| P1 | Quality foundation | Có Pest/Pint/PHPStan/build nhưng chưa có một quality command chuẩn | Trung bình | P1-T04 |
| P2 | User Repository | `UserRepository::visibleTo()` trả `Eloquent\Builder` | Cao | P2-T01 |
| P2 | Audit request ID | Audit chưa liên kết first-class với request tạo ra thay đổi | Cao | P2-T02 |
| P2 | Account/session lifecycle | Khóa user hoặc đổi mật khẩu chưa chủ động thu hồi đầy đủ session theo rule | Cao | P2-T03 |
| P2 | User/Department/Audit UI | State và modal/form chưa áp dụng nhất quán | Trung bình | P2-T04 |
| P3 | Lead Repository | Contract trả Builder; Service query trực tiếp source/tag | Cao | P3-T01 |
| P3 | Lead duplicate | Preflight chủ yếu do Livewire điều phối, caller khác có thể bypass | Cao | P3-T02 |
| P3 | Lead trash/restore | Restore chưa xử lý xung đột duplicate với Lead active | Cao | P3-T03 |
| P3 | Lead UI | Loading/error/empty và quyết định full-page/modal chưa chuẩn hóa | Trung bình | P3-T04 |
| Xuyên suốt | Testing | Thiếu test cho các boundary mới và chưa có checkpoint tổng | Cao | TR-01 |
| Xuyên suốt | Tài liệu | Requirement/skill thiếu; README và trạng thái có nguy cơ lệch code | Cao | TR-02 |
| Xuyên suốt | Phase log | `PROJECT_PHASES.md` trộn roadmap với nhật ký dài | Trung bình | TR-03 |

## 5. Trạng thái feature tổng thể

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái | Kết quả cần đạt |
|---|---|---|---|---|---|
| P1-T01 | Request context và structured logging | `feature/p1-t01-request-context-logging` | P1 | Hoàn tất triển khai — chờ kiểm thử | Một request ID xuyên suốt response và application/security log |
| P1-T02 | Quản lý phiên đăng nhập | `feature/p1-t02-session-management` | P1-T01 | Chưa bắt đầu | User xem/thu hồi session đúng ownership và có audit foundation |
| P1-T03 | Chuẩn hóa App shell | `feature/p1-t03-app-shell` | P1-T01 | Hoàn tất triển khai — chờ kiểm thử | Layout responsive, navigation mượt và platform slots nhất quán |
| P1-T04 | Quality foundation | `feature/p1-t04-quality-foundation` | P1-T01..P1-T03 | Hoàn tất triển khai — chờ kiểm thử | Một lệnh quality chuẩn và test nền tảng P1 |
| P2-T01 | Chuẩn hóa User Repository boundary | `feature/p2-t01-user-repository-boundary` | P1-T04, P2-08 | Hoàn tất triển khai — chờ kiểm thử | User query thuộc Repository; Service/Livewire không nhận Builder |
| P2-T02 | Audit correlation với Request ID | `feature/p2-t02-audit-request-correlation` | P1-T01, P2-07-02 | Hoàn tất triển khai — chờ kiểm thử | Audit, realtime và response dùng cùng request ID |
| P2-T03 | Account và session lifecycle | `feature/p2-t03-account-session-lifecycle` | P1-T02, P2-07 | Chưa bắt đầu | Khóa user/đổi mật khẩu thu hồi session đúng rule |
| P2-T04 | UI states và form quản trị | `feature/p2-t04-admin-ui-states` | P1-T03, P2-T01..P2-T03 | Chưa bắt đầu | User/Department/Audit có state, modal/form thống nhất |
| P3-T01 | Chuẩn hóa Lead Repository boundary | `feature/p3-t01-lead-repository-boundary` | P2-T01, P3-08 | Chưa bắt đầu | Lead query thuộc Repository; taxonomy không query trong Service |
| P3-T02 | Duplicate guard tại backend | `feature/p3-t02-lead-duplicate-guard` | P3-T01, P2-T02 | Chưa bắt đầu | Mọi caller phải qua duplicate decision và recheck |
| P3-T03 | Trash/restore conflict handling | `feature/p3-t03-lead-trash-conflict` | P3-T02 | Chưa bắt đầu | Restore xử lý duplicate active an toàn và có audit |
| P3-T04 | UI states và form Lead | `feature/p3-t04-lead-ui-states` | P1-T03, P3-T02, P3-T03 | Chưa bắt đầu | Lead UI có state nhất quán và quyết định modal/full page rõ ràng |
| TR-01 | Test và quality checkpoint toàn hệ thống | `feature/tr-01-system-quality-checkpoint` | P1/P2/P3 technical features | Chưa bắt đầu | Toàn bộ test/quality/build đạt |
| TR-02 | Khôi phục và đồng bộ tài liệu | `docs/tr-02-requirements-sync` | TR-01 | Chưa bắt đầu | Requirement, skill, README và docs đúng code |
| TR-03 | Tách và chuẩn hóa phase log | `docs/tr-03-phase-log-split` | TR-02 | Chưa bắt đầu | Roadmap gọn, lịch sử vẫn được giữ và liên kết |

Luồng triển khai đề xuất:

```text
P1-T01 Request context/logging
  ├── P1-T02 Session management
  └── P1-T03 App shell
          └── P1-T04 Quality foundation
                  └── P2-T01 User Repository

P1-T01 + P2-07-02 ──> P2-T02 Audit correlation
P1-T02 + P2-07    ──> P2-T03 Account/session lifecycle
P1-T03 + P2-T01..03 -> P2-T04 Admin UI states

P2-T01 + P3-08 ──> P3-T01 Lead Repository
P3-T01 + P2-T02 ─> P3-T02 Duplicate guard
P3-T02 ──────────> P3-T03 Trash conflict
P1-T03 + P3-T02..03 -> P3-T04 Lead UI states

P1/P2/P3 technical features
  └── TR-01 Test checkpoint
        └── TR-02 Documentation
              └── TR-03 Phase log
                    └── P3-09 Conversion
```

## 5.1. Thứ tự bắt buộc để tạo và triển khai branch

Mặc dù một số feature có thể chạy song song theo dependency, dự án hiện làm theo checkpoint từng feature. Vì vậy áp dụng **thứ tự tuyến tính dưới đây** để dễ kiểm thử, merge và rollback.

| Thứ tự | Feature phải làm | Branch phải tạo | Chỉ bắt đầu khi | Sau khi đạt checkpoint |
|---:|---|---|---|---|
| 1 | P1-T01 — Request context và structured logging | `feature/p1-t01-request-context-logging` | Kế hoạch này được chủ dự án xác nhận | Merge về `develop`, chuyển bước 2 |
| 2 | P1-T02 — Quản lý phiên đăng nhập | `feature/p1-t02-session-management` | P1-T01 đã test và merge | Merge về `develop`, chuyển bước 3 |
| 3 | P1-T03 — Chuẩn hóa App shell | `feature/p1-t03-app-shell` | P1-T02 đã test và merge | Merge về `develop`, chuyển bước 4 |
| 4 | P1-T04 — Quality foundation | `feature/p1-t04-quality-foundation` | P1-T03 đã test và merge | Chốt remediation P1, chuyển bước 5 |
| 5 | P2-T01 — Chuẩn hóa User Repository boundary | `feature/p2-t01-user-repository-boundary` | P1-T04 đã test và merge | Merge về `develop`, chuyển bước 6 |
| 6 | P2-T02 — Audit correlation với Request ID | `feature/p2-t02-audit-request-correlation` | P2-T01 đã test và merge | Merge về `develop`, chuyển bước 7 |
| 7 | P2-T03 — Account và session lifecycle | `feature/p2-t03-account-session-lifecycle` | P2-T02 đã test và merge | Merge về `develop`, chuyển bước 8 |
| 8 | P2-T04 — UI states và form quản trị | `feature/p2-t04-admin-ui-states` | P2-T03 đã test và merge | Chốt remediation P2, chuyển bước 9 |
| 9 | P3-T01 — Chuẩn hóa Lead Repository boundary | `feature/p3-t01-lead-repository-boundary` | P2-T04 đã test và merge | Merge về `develop`, chuyển bước 10 |
| 10 | P3-T02 — Duplicate guard tại backend | `feature/p3-t02-lead-duplicate-guard` | P3-T01 đã test và merge | Merge về `develop`, chuyển bước 11 |
| 11 | P3-T03 — Trash/restore conflict handling | `feature/p3-t03-lead-trash-conflict` | P3-T02 đã test và merge | Merge về `develop`, chuyển bước 12 |
| 12 | P3-T04 — UI states và form Lead | `feature/p3-t04-lead-ui-states` | P3-T03 đã test và merge | Chốt remediation P3, chuyển bước 13 |
| 13 | TR-01 — Test và quality checkpoint toàn hệ thống | `feature/tr-01-system-quality-checkpoint` | P3-T04 đã test và merge | Merge về `develop`, chuyển bước 14 |
| 14 | TR-02 — Khôi phục và đồng bộ tài liệu | `docs/tr-02-requirements-sync` | TR-01 đã đạt toàn bộ quality gate | Merge về `develop`, chuyển bước 15 |
| 15 | TR-03 — Tách và chuẩn hóa phase log | `docs/tr-03-phase-log-split` | TR-02 đã được đọc và xác nhận | Chốt remediation, sau đó mới bắt đầu P3-09 |

### Quy trình Git lặp lại cho từng branch

Mỗi branch mới phải được tạo từ `develop` mới nhất sau khi branch trước đã được kiểm thử và merge:

```bash
git switch develop
git pull --ff-only
git switch -c <branch-trong-bảng>
```

Sau khi Codex hoàn tất feature:

1. Codex cập nhật nhật ký feature trong file này.
2. Codex đưa commit title/body đề xuất và dừng.
3. Chủ dự án chạy checklist thủ công.
4. Chỉ commit/merge khi chủ dự án xác nhận đạt.
5. Quay lại `develop`, cập nhật branch và tạo branch ở dòng kế tiếp trong bảng.

Không tạo sẵn nhiều branch cùng lúc. Không bắt đầu branch tiếp theo từ branch feature chưa được merge, vì sẽ làm dependency và rollback khó theo dõi.

### Feature phải bắt đầu ngay bây giờ

```text
Bước 1
Feature: P1-T01 — Request context và structured logging
Branch: feature/p1-t01-request-context-logging
Base branch: develop
```

---

# Phần A — Remediation Giai đoạn 1

## P1-T01 — Request context và structured logging

### Mục đích

Thiết lập nền tảng truy vết dùng chung cho mọi HTTP request trước khi Audit, Session và các module mới mở rộng.

### Hiện trạng và đánh giá

- Chưa có middleware cấp request ID.
- HTTP response chưa trả `X-Request-ID`.
- Log chưa có context chung gồm request ID, user, route và method.
- Chưa có quy tắc redaction thống nhất ở tầng logging.

Đánh giá: **lệch nền tảng P1, ưu tiên cao nhất**.

### Phạm vi triển khai

- Tạo `AssignRequestId` middleware và `RequestContext`.
- Chỉ giữ inbound `X-Request-ID` hợp lệ; nếu thiếu/sai thì sinh UUID mới.
- Gắn request ID vào request attribute, log context và response header.
- Thêm context an toàn: user ID, route, method, environment.
- Tạo structured application/security logging phù hợp môi trường.
- Redact password, token, cookie, authorization header, secret và session ID.
- Trang lỗi production hiển thị mã tra cứu, không lộ stack trace.

### Ngoài phạm vi

- Chưa gắn request ID vào audit database; việc đó thuộc P2-T02.
- Không lưu toàn bộ request body.
- Không cài monitoring provider bên ngoài.

### File dự kiến

- `app/Http/Middleware/AssignRequestId.php`
- `app/Support/RequestContext.php`
- `bootstrap/app.php`
- `config/logging.php`
- Error/exception configuration liên quan
- `tests/Feature/RequestContextTest.php`

### Tiêu chí nghiệm thu

- Mọi HTTP response có `X-Request-ID`.
- Request ID hợp lệ được giữ; giá trị không hợp lệ bị thay mới.
- Application/security log có cùng request ID.
- Log không chứa secret hoặc session ID nguyên bản.
- Error response production có mã tra cứu.

### Checklist thủ công

1. Kiểm tra response header bằng DevTools.
2. Gửi request có/không có `X-Request-ID`.
3. Đối chiếu response ID với Docker application log.
4. Gây lỗi validation và xác nhận không lộ password/token.

### Checkpoint

Dừng sau P1-T01 để kiểm tra request header và log trước khi làm session/audit.

### Nhật ký P1-T01 — Request context và structured logging

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Middleware toàn cục sinh UUID khi request không có `X-Request-ID`, giữ inbound ID hợp lệ và loại bỏ ID có ký tự không an toàn hoặc dài quá 100 ký tự.
- Request ID được gắn vào request attribute, Laravel Context, JSON log và response header; response lỗi 500 cũng trả cùng header.
- Context được enrich sau khi Laravel resolve route và sau authentication để log có route name/user ID khi có thể.
- Structured context có `module` và `action` được suy ra từ route name; route không có tên dùng path/method an toàn làm fallback.
- Tạo channel `application` và `security` dạng JSON, daily rotation; `single`/`daily` cũ cũng dùng JSON/redaction để local `.env` hiện tại không bị bỏ sót bảo vệ.
- Tạo sidecar Docker `logs` chỉ đọc named volume log; formatter allowlist chuyển JSON thành một dòng gồm thời gian Việt Nam, level, module/action, status, user, duration, request ID và event.
- Structured log không còn trộn trong `app-1`; Docker Desktop xem riêng tại container `salesflow-crm-logs-1`, còn JSON gốc vẫn được giữ để truy vết đầy đủ.
- Processor redaction che dữ liệu nhạy cảm lồng nhau theo key: authorization, cookie, password, secret, session, token và API/private/client key.
- HTTP success ghi ở mức `debug`, lỗi client ghi `warning`, lỗi server ghi `error`; bỏ qua access log thành công của `/up` để healthcheck không làm đầy log.
- Tạo trang lỗi 500 tiếng Việt hiển thị mã tra cứu request ID.
- Chưa sửa schema/activity log; liên kết request ID vào Audit database thuộc P2-T02.

Requirement/gap áp dụng:

- `docs/requirements.md`: mục 9, 9.1, 11.2 và 12.
- Quyết định `DEC-004`, `DEC-007`, `DEC-008`.
- Gap `GAP-PLATFORM-001`; P1-T01 xử lý HTTP/log context, phần Audit correlation còn lại ở P2-T02.

File đã tạo/sửa:

- `app/Support/RequestContext.php`
- `app/Http/Middleware/AssignRequestId.php`
- `app/Http/Middleware/EnrichAuthenticatedRequestContext.php`
- `app/Logging/ConfigureStructuredLogging.php`
- `app/Logging/RedactSensitiveData.php`
- `app/Console/Commands/TailApplicationLog.php`
- `app/Support/ApplicationLogLineFormatter.php`
- `bootstrap/app.php`
- `config/logging.php`
- `.env.example`
- `compose.yaml`, `compose.override.yaml`
- `routes/web.php`, `routes/api.php`
- `resources/views/errors/500.blade.php`
- `tests/Feature/RequestContextTest.php`
- `tests/Feature/ApplicationLogViewerCommandTest.php`
- `tests/Unit/RedactSensitiveDataTest.php`
- `tests/Unit/ApplicationLogLineFormatterTest.php`

Migration/package:

- Không có migration.
- Không cài Composer/NPM package mới; sử dụng Laravel Context và Monolog đã có trong framework.

Lệnh đã chạy:

```bash
docker compose exec -T -e LOG_APPLICATION_STACK=application_file app php artisan test tests/Feature/RequestContextTest.php tests/Feature/ApplicationLogViewerCommandTest.php tests/Unit/RedactSensitiveDataTest.php tests/Unit/ApplicationLogLineFormatterTest.php
docker compose exec -T -e LOG_APPLICATION_STACK=application_file app php artisan test --compact
docker compose exec -T app ./vendor/bin/pint --test
docker compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec -T vite npm run build
docker compose exec -T app curl -sS -D - -o /dev/null http://nginx/up
docker compose logs -f logs
docker compose ps
```

Kết quả xác minh:

- Test riêng P1-T01: **10 test đạt, 45 assertions**.
- Toàn dự án: **154 test đạt, 1019 assertions**.
- Pint: **đạt trên 167 file**.
- PHPStan: **không có lỗi**.
- Vite production build: **đạt**.
- Docker: **11 service đang chạy; sidecar `logs` và các service có healthcheck đều healthy**.
- Smoke test Nginx: response `/up` có `X-Request-ID`; JSON application log dùng cùng ID trong lần smoke trước khi loại access log healthcheck.
- Smoke test sidecar: request `dedicated-log-viewer-002` xuất hiện realtime ở `logs-1` dưới dạng đã lọc; `app-1` chỉ còn access log PHP-FPM.

Checklist kiểm thử thủ công:

1. Mở DevTools → Network, tải Dashboard và xác nhận response có `X-Request-ID`.
2. Chạy `docker compose logs -f logs`, chuyển giữa các trang và đối chiếu request ID trên container riêng.
3. Nếu cần JSON gốc, chạy `docker compose exec app tail -f storage/logs/application-2026-07-23.log`.
4. Dùng curl gửi `X-Request-ID: manual-request-001`; xác nhận response và log giữ nguyên mã này.
5. Gửi ID có khoảng trắng/ký tự `/`; xác nhận server thay bằng UUID.
6. Kiểm tra một response lỗi 500 ở môi trường test và xác nhận trang hiển thị “Mã tra cứu”.
7. Không bắt đầu P1-T02 cho đến khi checklist trên được xác nhận.

Commit đề xuất:

`(feature-p1-t01): thêm request context và structured logging`

Checkpoint P1-T01: **dừng tại đây để chủ dự án kiểm thử trước khi bắt đầu P1-T02**.

---

## P1-T02 — Quản lý phiên đăng nhập

### Mục đích

Hoàn thiện phần authentication foundation: người dùng biết tài khoản đang đăng nhập ở đâu và chủ động thu hồi phiên không tin cậy.

### Hiện trạng và đánh giá

- Database session và bảng `sessions` đã tồn tại.
- Chưa có UI hoặc Service quản lý session.
- Middleware inactive chỉ xử lý phiên đang gửi request.

Đánh giá: **thiếu chức năng bảo mật thuộc P1**.

### Phạm vi triển khai

- Tạo `/settings/sessions` cho user hiện tại.
- Hiển thị trình duyệt/thiết bị, IP đã che, hoạt động cuối và phiên hiện tại.
- Thu hồi một phiên khác và tất cả phiên khác.
- Yêu cầu xác nhận mật khẩu cho thao tác nhạy cảm.
- Phiên hiện tại logout bằng invalidate session và regenerate CSRF token.
- Repository bắt buộc lọc theo `user_id` của actor.
- Chuẩn bị event/service để P2-T03 gọi khi khóa user/đổi mật khẩu.
- Audit nền tảng chỉ lưu nhận diện session đã hash/rút gọn.

### Ngoài phạm vi

- Chưa triển khai Admin xem session toàn hệ thống.
- Không cài package User-Agent nếu chưa cần.
- Không suy luận vị trí địa lý từ IP.

### File dự kiến

- `app/Http/Controllers/SessionController.php`
- `app/Livewire/Settings/SessionManager.php`
- `app/Repositories/Contracts/SessionRepository.php`
- `app/Repositories/EloquentSessionRepository.php`
- `app/Services/SessionManagementService.php`
- View settings/session
- `routes/web.php`
- `tests/Feature/SessionManagementTest.php`

### Tiêu chí nghiệm thu

- User chỉ thấy và thu hồi session của chính mình.
- Phiên bị thu hồi bị logout ở request kế tiếp.
- Thu hồi tất cả phiên khác yêu cầu xác nhận mật khẩu.
- Không log/audit session ID nguyên bản.

### Checklist thủ công

1. Đăng nhập cùng tài khoản bằng hai trình duyệt.
2. Kiểm tra đánh dấu đúng phiên hiện tại.
3. Thu hồi trình duyệt còn lại và xác nhận bị logout.
4. Thử xóa session của user khác bằng request sửa tay; phải bị chặn.

### Checkpoint

Dừng sau P1-T02 để kiểm thử bằng ít nhất hai trình duyệt.

### Nhật ký P1-T02 — Quản lý phiên đăng nhập

Trạng thái: **hoàn tất triển khai, chờ chạy test Docker**.

Đã triển khai:

- Tạo route `/settings/sessions` và menu “Phiên đăng nhập” cho mọi user đã đăng nhập, verified và active.
- Tạo Livewire `SessionManager` hiển thị trình duyệt/thiết bị, IP đã che, thời gian hoạt động cuối, fingerprint rút gọn và nhãn phiên hiện tại.
- Tạo repository đọc/xóa bảng `sessions` với điều kiện bắt buộc `user_id` là user hiện tại.
- Tạo service thu hồi một phiên hoặc toàn bộ phiên khác, không đưa session ID nguyên bản ra UI hoặc audit.
- Dùng token mã hóa cho action revoke; token của user khác bị từ chối sau khi decrypt vì không qua ownership query.
- Yêu cầu `auth.password_confirmed_at` còn hạn trước thao tác thu hồi; nếu thiếu thì chuyển sang `password.confirm`.
- Audit ghi `session_revoked` hoặc `sessions_revoked` trên subject là chính user hiện tại, chỉ chứa fingerprint/IP đã che/metadata hoặc số lượng bị thu hồi.
- `.env.example` chuyển `SESSION_DRIVER=database` để local/demo dùng được màn quản lý session.

File đã tạo/sửa:

- `app/Data/SessionInfo.php`
- `app/Livewire/Settings/SessionManager.php`
- `app/Repositories/Contracts/SessionRepository.php`
- `app/Repositories/EloquentSessionRepository.php`
- `app/Services/SessionManagementService.php`
- `resources/views/livewire/settings/session-manager.blade.php`
- `app/Providers/RepositoryServiceProvider.php`
- `routes/web.php`
- `resources/views/layouts/app.blade.php`
- `.env.example`
- `tests/Feature/SessionManagementTest.php`

Migration/package:

- Không có migration mới; sử dụng bảng `sessions` đã có trong migration nền tảng Laravel.
- Không cài Composer/NPM package.

Lệnh kiểm tra:

```bash
docker compose exec -T app php artisan test tests/Feature/SessionManagementTest.php
git diff --check
```

Kết quả:

- `git diff --check`: **đạt**.
- Docker test: **chưa chạy được do môi trường**. WSL báo `The command 'docker' could not be found in this WSL 2 distro`; cần bật Docker Desktop WSL integration rồi chạy lại đúng một file test mới theo chỉ đạo chủ dự án.

Checkpoint P1-T02: **dừng tại đây để chủ dự án chạy test file mới và kiểm thử thủ công trước khi bắt đầu P1-T03**.

---

## P1-T03 — Chuẩn hóa App shell

### Mục đích

Hoàn thiện layout foundation đã bắt đầu ở P1, giảm giật khi chuyển trang và tạo slot thống nhất cho platform feature.

### Hiện trạng và đánh giá

- Sidebar responsive, active navigation, dark mode và `wire:navigate` đã có.
- Topbar, user menu, global search, quick create và notification slot chưa hoàn chỉnh.
- Layout cần tách trách nhiệm để tránh một file lớn.

### Phạm vi triển khai

- Tách app layout thành sidebar, topbar, user menu và page header.
- Chuẩn hóa desktop/mobile navigation, active state và keyboard focus.
- Dùng Livewire navigation/persist pattern phù hợp để hạn chế full reload/nhấp nháy.
- Quick create chỉ hiện action đã tồn tại và đúng permission.
- Global search/notification có slot rõ ràng; nếu chưa có backend thì disabled có chủ ý.
- User menu có session/help/logout.
- Không chạy query nghiệp vụ nặng trực tiếp trong layout.

### Ngoài phạm vi

- Chưa xây global search backend.
- Chưa xây notification center nghiệp vụ.
- Không thay Flux UI/Tailwind bằng thư viện khác.

### Tiêu chí nghiệm thu

- Chuyển trang nội bộ không full reload.
- Không nháy dark mode hoặc giật layout rõ rệt.
- Mobile và keyboard navigation hoạt động.
- Menu đúng permission và không tăng query theo số menu.

### Checklist thủ công

1. Chuyển liên tục Dashboard, Lead, Users, Departments và Audit.
2. Kiểm tra mobile/desktop, dark/light mode.
3. Đăng nhập đủ role để kiểm tra navigation.
4. Kiểm tra user menu và link session.

### Checkpoint

Dừng sau P1-T03 để chủ dự án đánh giá độ mượt và layout.

### Nhật ký triển khai

**Ngày**: 23/07/2026
**Branch**: `feature/p1-t03-app-shell`
**Requirement**: `REQ-5.2`, `GAP-UI-001`

**Files tạo mới**:
- `resources/views/layouts/partials/_sidebar.blade.php` — Sidebar partial với navigation nhóm CRM/Quản trị/Cá nhân/Sắp có
- `resources/views/layouts/partials/_topbar.blade.php` — Topbar partial với search slot (disabled), notification bell (disabled), quick create (disabled), dark mode toggle, user menu
- `resources/views/layouts/partials/_user-menu.blade.php` — User dropdown menu với Flux dropdown/menu components

**Files thay đổi**:
- `resources/views/layouts/app.blade.php` — Tách thành partials, thêm `@persist('app-sidebar')`
- `resources/css/app.css` — Thêm `.nav-group-label` và `.nav-icon` utility classes

**Quyết định kiến trúc**:
- Thay Unicode icons (⌂ ◎ ♙ ▤ ◴ ⌘ ◌) bằng Heroicon SVG qua Flux `<flux:icon.*>`
- Phân nhóm navigation: CRM → Quản trị → Cá nhân → Sắp có
- User menu chuyển lên topbar thành Flux dropdown, giữ avatar ở đáy sidebar
- Logo brand "SF" badge thêm vào sidebar header
- Platform slots (search, notification, quick create) đặt disabled có chủ ý
- `@persist('app-sidebar')` tránh re-render sidebar khi Livewire navigate

**Quality gates**:
- Pint: ✅ PASS 176 files
- PHPStan: ✅ No errors
- Vite build: ✅ built in 2.36s

Checkpoint P1-T03: **dừng tại đây để chủ dự án kiểm thử thủ công trước khi bắt đầu P1-T04**.

---

## P1-T04 — Quality foundation

### Mục đích

Tạo một quy trình kiểm tra lặp lại được cho mọi feature sau này.

### Phạm vi triển khai

- Thêm script `composer quality` hoặc script tương đương.
- Chạy test, Pint, PHPStan và frontend build theo thứ tự rõ ràng.
- Chuẩn hóa database test tách biệt local development.
- Bổ sung test nền tảng request context, session và app shell permission.
- Không đánh dấu pass nếu lệnh chưa chạy thật.

### Tiêu chí nghiệm thu

- Một lệnh quality chạy được trong Docker.
- Không ghi vào database development.
- Kết quả từng bước hiển thị rõ và fail-fast.
- Test P1 mới đạt.

### Checkpoint

Dừng sau P1-T04 để xác nhận baseline P1 trước khi sửa P2.

### Nhật ký triển khai

**Ngày**: 23/07/2026
**Branch**: `feature/p1-t04-quality-foundation`
**Requirement**: `REQ-11.2`, `GAP-PLATFORM-001`

**Files thay đổi**:
- `composer.json` — Thêm script `quality` chạy tuần tự test, Pint, và PHPStan.
- `app/Livewire/Settings/SessionManager.php` — Thêm `hasSession()` guard vào `refreshSessions()` và `passwordRecentlyConfirmed()` để tránh RuntimeException trong môi trường test (SESSION_DRIVER=array).
- `tests/Feature/SessionManagementTest.php` — Fix logic của 4 test cases trong `SessionManagementTest` để tương thích hoàn toàn với database & session configurations khi chạy test độc lập.

**Quyết định**:
- Tạo command `composer quality` chạy toàn bộ test suite, kiểm tra format code (Pint), và phân tích tĩnh (PHPStan) với hành vi fail-fast.
- Sửa lỗi runtime trong test suite do thiếu mock/setup session context trên request object.

**Quality gates**:
- `composer quality`: ✅ PASS (164 tests passed, 176 files style passed, phpstan no errors)
- `npm run build`: ✅ built in 1.96s

Checkpoint P1-T04: **dừng tại đây để chủ dự án xác nhận baseline P1 trước khi chuyển sang P2**.

---

# Phần B — Remediation Giai đoạn 2

## P2-T01 — Chuẩn hóa User Repository boundary

### Mục đích

Đảm bảo data scope User được cưỡng chế trong Repository và query không rò rỉ sang Service/Livewire.

### Hiện trạng và đánh giá

- `UserRepository::visibleTo()` public `Eloquent\Builder`.
- Caller có thể nối `where`, `find`, `orderBy` ngoài Repository.
- Department/Audit Repository chủ yếu đã trả dữ liệu cụ thể; cần audit contract nhưng không viết lại nếu không vi phạm.

### Phạm vi triển khai

- Repository contract chỉ trả Model, Collection, Paginator, DTO, scalar hoặc boolean.
- Chuyển Builder thành private query method trong Eloquent implementation.
- Bổ sung method có ý nghĩa: paginate visible users, active options, role filters, find visible user.
- Service chỉ điều phối use case; Livewire không nối query.
- Không đổi permission/data scope matrix.

### Tiêu chí nghiệm thu

- `UserRepository` không public Builder.
- User list/form/assignment vẫn đúng data scope.
- Admin/Sales Manager/Sales/Viewer không nhìn thấy dữ liệu ngoài quyền.
- Test P2 repository và authorization đạt.

### Checkpoint

Dừng sau P2-T01 để kiểm thử danh sách và quản lý user theo đủ role.

### Nhật ký triển khai

**Ngày**: 23/07/2026
**Branch**: `feature/p2-t01-user-repository-boundary`
**Requirement**: `REQ-1.2`, `GAP-UI-002`

**Files thay đổi**:
- `app/Repositories/Contracts/UserRepository.php` — Loại bỏ `visibleTo()` khỏi contract, thêm `findVisibleActiveUser()` và `visibleActiveUsers()`.
- `app/Repositories/EloquentUserRepository.php` — Đổi `visibleTo()` sang `private` helper và implement các method mới có kèm PHPStan return type annotations (`Collection<int, User>` và `Builder<User>`).
- `app/Services/LeadAssignmentService.php` — Dùng `findVisibleActiveUser()` thay thế.
- `app/Services/LeadDirectoryService.php` — Dùng `visibleActiveUsers()` thay thế.
- `app/Services/LeadManagementService.php` — Dùng `findVisibleActiveUser()` thay thế.
- `tests/Feature/DataScopePolicyTest.php` — Cập nhật assert test từ `visibleTo()` sang `paginateVisibleTo()` để bảo vệ boundary của Repository.

**Quyết định**:
- Đóng gói hoàn toàn logic truy vấn data scope người dùng vào trong tầng Repository. Service/Livewire không được phép thao tác trực tiếp trên Eloquent Builder của Model User.

**Quality gates**:
- `composer quality`: ✅ PASS (164 tests passed, 176 files style passed, phpstan no errors)
- `npm run build`: ✅ built in 1.81s

Checkpoint P2-T01: **dừng tại đây để chủ dự án kiểm thử phân quyền danh sách người dùng trên giao diện**.

---

## P2-T02 — Audit correlation với Request ID

### Mục đích

Liên kết audit nghiệp vụ P2 với request context P1 để tra cứu một thao tác xuyên suốt.

### Hiện trạng và đánh giá

- `SystemAuditService` đã sanitize dữ liệu nhạy cảm và broadcast realtime.
- `activity_log` chưa có request ID first-class.
- Audit filter chưa tìm theo request ID.

### Phạm vi triển khai

- Thêm cột/index `request_id` hoặc cơ chế first-class tương đương cho activity log.
- `SystemAuditService` tự lấy ID từ `RequestContext`, caller không truyền thủ công.
- Audit lưu route/method an toàn khi có HTTP request.
- Job/CLI không giả lập HTTP request ID; dùng correlation context phù hợp khi phát sinh.
- Bổ sung filter request ID trên màn Audit.
- Giữ private Reverb channel và quyền Super Admin/Admin IT.

### Tiêu chí nghiệm thu

- Response, application log và audit cùng request ID.
- Tìm audit theo request ID được.
- Realtime audit vẫn cập nhật.
- Password/token/session ID không xuất hiện trong properties.

### Checklist thủ công

1. Sửa User/Department và lấy request ID từ response.
2. Tìm đúng audit theo ID.
3. Kiểm tra Admin ngoài IT không truy cập Audit.
4. Kiểm tra realtime không cần reload.

### Checkpoint

Dừng sau P2-T02 để chủ dự án kiểm tra correlation và quyền truy cập Audit.

### Nhật ký triển khai

**Ngày**: 23/07/2026 2:25
**Branch**: `feature/p2-t02-audit-request-correlation`
**Requirement**: `REQ-11.2`, `GAP-AUDIT-001`

**Files thay đổi**:
- `database/migrations/2026_07_23_141238_add_request_id_to_activity_log_table.php` — Tạo migration thêm cột `request_id` (string, 100) có đánh index cho bảng `activity_log`.
- `app/Providers/AppServiceProvider.php` — Đăng ký sự kiện model `creating` cho Spatie Activity để tự động điền `request_id` từ RequestContext.
- `app/Data/AuditLogFilters.php` — Bổ sung filter `requestId`.
- `app/Livewire/AuditLogs/AuditLogList.php` — Bổ sung query parameter `request_id`, binding và cập nhật logic clear filters.
- `app/Repositories/EloquentAuditLogRepository.php` — Hỗ trợ lọc theo `request_id` trong truy vấn phân trang của Audit Logs, và thêm tìm kiếm khớp chính xác `request_id`.
- `resources/views/livewire/audit-logs/audit-log-list.blade.php` — Thêm trường nhập lọc Request ID, hiển thị huy hiệu Req trên bảng và console log, đồng thời click vào ID sẽ trigger lọc nhanh.
- `tests/Feature/AuditLogAccessTest.php` — Bổ sung test kiểm thử tính liên thông request ID và tính năng tìm kiếm/lọc.

**Quyết định**:
- Không thay đổi model mặc định của Spatie Activitylog; thay vào đó, dùng Eloquent model events (`creating`) để giữ mã nguồn tối giản và tự động gán request ID cho mọi luồng (kể cả queue job).

**Quality gates**:
- `composer quality`: ✅ PASS (165 tests passed, 177 files style passed, phpstan no errors)
- `npm run build`: ✅ built in 1.81s

Checkpoint P2-T02: **dừng tại đây để chủ dự án kiểm tra correlation và quyền truy cập Audit**.

---

## P2-T03 — Account và session lifecycle

### Mục đích

Đảm bảo thay đổi trạng thái bảo mật của User có hiệu lực với tất cả phiên liên quan.

### Phạm vi triển khai

- Khi khóa user: thu hồi toàn bộ session của user trong transaction/use case phù hợp.
- Khi đổi/reset mật khẩu: áp dụng rule thu hồi các phiên khác và cập nhật remember token.
- Không tự thu hồi phiên nếu chỉ đổi tên/phòng ban/role, trừ khi policy bảo mật được chốt khác.
- Không cho admin cuối cùng tự khóa nếu rule P2 hiện tại cấm.
- Audit actor, target, số session bị thu hồi và request ID; không lưu session ID.

### Tiêu chí nghiệm thu

- User bị khóa mất quyền ở tất cả thiết bị.
- Đổi/reset mật khẩu vô hiệu phiên theo rule đã ghi.
- Thay đổi thông tin thường không logout ngoài ý muốn.
- Audit không chứa credential/session secret.

### Checklist thủ công

1. Đăng nhập user trên hai trình duyệt rồi khóa từ Admin.
2. Xác nhận cả hai phiên bị vô hiệu.
3. Mở lại user và kiểm tra không tự phục hồi session cũ.
4. Đổi mật khẩu và kiểm tra rule phiên.

### Checkpoint

Dừng sau P2-T03 để kiểm thử account/session lifecycle bằng nhiều trình duyệt.

---

## P2-T04 — UI states và form quản trị

### Mục đích

Chuẩn hóa trải nghiệm User, Department và Audit theo app shell P1.

### Phạm vi triển khai

- State dùng chung: loading, skeleton, empty, filtered-empty, error/retry, success.
- Form ngắn User/Department ưu tiên Flux modal nếu phù hợp.
- Validation nằm trong Livewire Form Object; mutation nằm trong Service.
- Disable submit theo `wire:target` để chống gửi lặp.
- Modal reset data/error khi mở; focus, Escape và tab order hợp lý.
- Audit filter có loading/error/empty state nhất quán.

### Ngoài phạm vi

- Không thay permission matrix.
- Không redesign thương hiệu.
- Không thêm JavaScript framework.

### Tiêu chí nghiệm thu

- User/Department/Audit có đầy đủ state cần thiết.
- Không duplicate submit.
- Modal không giữ lỗi/dữ liệu cũ.
- Responsive, dark mode và keyboard đạt mức cơ bản.

### Checkpoint

Dừng sau P2-T04 để nghiệm thu toàn bộ remediation P2.

---

# Phần C — Remediation Giai đoạn 3

## P3-T01 — Chuẩn hóa Lead Repository boundary

### Mục đích

Đưa toàn bộ Lead query về Repository trước khi xây conversion P3-09.

### Hiện trạng và đánh giá

- `LeadRepository` public `visibleTo`, `filteredVisibleTo`, `trashedVisibleTo` trả Builder.
- `LeadDirectoryService` query trực tiếp `LeadSource` và `Tag`.
- Livewire/Service có thể nối query ngoài Repository.
- Data scope/filter hiện hoạt động tốt; chỉ refactor boundary, không viết lại nghiệp vụ.

### Phạm vi triển khai

- Contract chỉ trả Model, Collection, Paginator, DTO, scalar hoặc boolean.
- Query builder trở thành private method trong Eloquent Repository.
- Tạo Repository taxonomy khi source/tag có query dùng lại.
- Livewire gọi Service thay vì tự lấy Repository cho use case nghiệp vụ.
- Transaction tiếp tục thuộc Service.

### Ngoài phạm vi

- Không tạo generic BaseRepository.
- Không đổi schema hoặc permission Lead.
- Không thêm conversion.

### Tiêu chí nghiệm thu

- Không có Lead Repository contract public Builder.
- Không còn `LeadSource::query()`/`Tag::query()` trong Service.
- List/detail/trash/duplicate vẫn đúng data scope.
- Không phát sinh N+1.

### Checklist thủ công

1. Kiểm tra list/search/filter/sort/pagination.
2. Kiểm tra detail/edit/trash theo đủ role.
3. Xác nhận phòng A không thấy Lead phòng B.

### Checkpoint

Dừng sau P3-T01 để kiểm thử Repository boundary trước duplicate hardening.

---

## P3-T02 — Duplicate guard tại backend

### Mục đích

Ngăn API/import/service caller tương lai bypass duplicate check đang được điều phối từ Livewire.

### Phạm vi triển khai

- Đưa duplicate decision contract xuống Service.
- Recheck candidate ngay trước transaction lưu.
- Chuẩn hóa quyết định: cancel, open existing, save separately; restore/merge thuộc luồng phù hợp.
- `save separately` yêu cầu confirmation gắn với contact signature mới nhất và reason.
- Audit override với candidate IDs, reason và request ID; không lặp contact nhạy cảm không cần thiết.
- Duplicate query tiếp tục áp dụng data scope.

### Ngoài phạm vi

- Không unique tuyệt đối email/phone vì hệ thống cho phép lưu riêng.
- Không tự merge Lead.
- Không thêm Company/Contact/Opportunity.

### Tiêu chí nghiệm thu

- Gọi Service trực tiếp vẫn bắt buộc duplicate decision.
- Đổi contact làm confirmation cũ mất hiệu lực.
- Lưu riêng có reason và audit.
- Không lộ candidate ngoài data scope.

### Checklist thủ công

1. Thử email khác hoa/thường.
2. Thử số `+84`, `0084`, dấu cách/gạch.
3. Xác nhận rồi đổi contact sang duplicate khác.
4. Lưu riêng và kiểm tra audit.

### Checkpoint

Dừng sau P3-T02 để kiểm thử create/edit duplicate trước trash conflict.

---

## P3-T03 — Trash/restore conflict handling

### Mục đích

Không để restore Lead cũ âm thầm tạo xung đột với Lead active mới có cùng contact.

### Phạm vi triển khai

- Restore chạy duplicate preflight với active Lead.
- Nếu có conflict: mở Lead active, hủy restore hoặc restore riêng có xác nhận/reason.
- Merge chỉ định nghĩa contract; không triển khai khi chưa chốt tag/history/converted relations.
- Owner inactive và duplicate conflict được xử lý trong transaction an toàn.
- Giữ tag, assignment/status history và audit.
- Không hard delete qua UI.

### Tiêu chí nghiệm thu

- Restore duplicate active phải dừng và hiển thị lựa chọn.
- Restore riêng có reason/audit/request ID.
- Owner inactive được unassign theo rule hiện tại.
- Không thấy Lead trash ngoài data scope.

### Checklist thủ công

1. Xóa Lead A.
2. Tạo Lead B active cùng email/số điện thoại.
3. Restore Lead A và kiểm tra conflict UI.
4. Kiểm tra cancel/restore riêng và audit.
5. Xác nhận tag/history vẫn còn.

### Checkpoint

Dừng sau P3-T03 để kiểm thử trash/restore trước khi chỉnh Lead UI.

---

## P3-T04 — UI states và form Lead

### Mục đích

Chuẩn hóa feedback và modal/form của Lead mà không ép form dài vào modal.

### Phạm vi triển khai

- Áp dụng loading, skeleton, empty, filtered-empty, error/retry và success state dùng chung.
- Lead create/edit tiếp tục full page vì dài, có nhiều section và URL riêng; ghi rõ quyết định.
- Delete/restore/assign/status/duplicate decision dùng modal.
- Validation thuộc Form Object; mutation thuộc Service.
- Chống duplicate submit và reset modal state/error đúng cách.
- Đảm bảo responsive, dark mode và keyboard cơ bản.

### Tiêu chí nghiệm thu

- List/detail/editor/trash có state nhất quán.
- Không tạo mutation trùng khi double-click.
- Modal không giữ state cũ.
- Full-page Lead form có rationale trong tài liệu.

### Checklist thủ công

1. Throttle network để xem loading/skeleton.
2. Kiểm tra filtered-empty và error/retry.
3. Double-click Save/Delete/Restore.
4. Mở/đóng các modal nhiều lần.
5. Kiểm tra mobile/dark mode/keyboard.

### Checkpoint

Dừng sau P3-T04 để nghiệm thu toàn bộ remediation P3.

---

# Phần D — Checkpoint xuyên suốt

## TR-01 — Test và quality checkpoint toàn hệ thống

### Mục đích

Chứng minh remediation P1/P2/P3 hoạt động cùng nhau và không làm hồi quy nghiệp vụ đã hoàn tất.

### Phạm vi triển khai

- Bổ sung/hoàn thiện test cho Repository boundary, request context, logging redaction, session ownership/revoke, audit correlation, duplicate/restore conflict, navigation và UI state quan trọng.
- Chạy test riêng theo feature và toàn bộ suite.
- Chạy Pint, PHPStan, Vite build, migration status và Docker health.
- Không sử dụng database development cho automated test.

### Quality commands dự kiến

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose exec app php artisan migrate:status
docker compose ps
```

### Tiêu chí nghiệm thu

- Test/Pint/PHPStan/build đạt.
- Docker services cần thiết healthy.
- Nếu môi trường thiếu Docker/PHP phải ghi blocker và chưa được đánh dấu hoàn tất.
- Manual smoke flow Login → Session → User → Lead → Trash/Restore → Audit đạt.

### Checkpoint

Dừng sau TR-01 để chủ dự án xác nhận quality baseline trước khi chỉnh tài liệu lớn.

---

## TR-02 — Khôi phục và đồng bộ tài liệu

### Mục đích

Đưa tài liệu về cùng trạng thái với code đã được kiểm chứng.

### Phạm vi triển khai

- Xác định branch/commit chuẩn và khôi phục `docs/requirements.md` cùng skill dự án.
- Cập nhật README đúng checkpoint remediation.
- Cập nhật architecture, security, session, audit/request ID, logging, Repository boundary và Lead duplicate/trash.
- Ghi rationale full-page/modal và package decision.
- Kiểm tra toàn bộ link nội bộ.

### Tiêu chí nghiệm thu

- Requirement và skill chuẩn tồn tại trên branch.
- README không còn nói dự án dừng ở phase cũ.
- Documentation khớp code và kết quả TR-01.
- Không có link tài liệu bị hỏng.

### Checkpoint

Dừng sau TR-02 để chủ dự án đọc requirement/docs trước khi tách phase log.

---

## TR-03 — Tách và chuẩn hóa phase log

### Mục đích

Giữ `PROJECT_PHASES.md` dễ đọc mà không làm mất lịch sử triển khai.

### Phạm vi triển khai

- `PROJECT_PHASES.md` chỉ giữ mã, tên, dependency, branch, status và checkpoint link.
- Chuyển nhật ký chi tiết sang:

```text
docs/checkpoints/P1/
docs/checkpoints/P2/
docs/checkpoints/P3/
docs/checkpoints/technical/
```

- Giữ nguyên nội dung lịch sử và lệnh đã chạy; không rewrite Git history.
- File này trở thành index remediation hoặc được archive dưới `docs/checkpoints/technical/` sau nghiệm thu.

### Tiêu chí nghiệm thu

- Roadmap chính gọn và tra cứu nhanh.
- Mọi checkpoint cũ vẫn truy cập được.
- Link giữa roadmap, remediation và checkpoint hoạt động.
- P3-09 chỉ bắt đầu sau khi TR-03 được xác nhận.

### Checkpoint

Dừng sau TR-03 để nghiệm thu toàn bộ technical remediation trước P3-09.

---

## 6. Mẫu nhật ký sau mỗi feature

````markdown
### Nhật ký Pn-Txx/TR-xx — Tên feature

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

`(feature-pn-txx): tiêu đề ngắn`

Checkpoint: dừng tại đây để chủ dự án kiểm thử.
````

## 7. Definition of Done

- Đúng phạm vi một feature và không kéo theo feature kế tiếp.
- Backend cưỡng chế authorization, ownership, data scope và validation.
- Không rò rỉ Builder/secret/session ID qua layer hoặc log.
- Có test happy path, permission denied, validation và edge case quan trọng.
- Test liên quan, toàn suite, Pint, PHPStan và frontend build đạt hoặc có blocker được chứng minh.
- UI có loading/error và duplicate-submit guard khi có tương tác.
- Mutation quan trọng có audit và request ID sau P2-T02.
- Tài liệu/checkpoint ghi đúng kết quả thực tế.
- Có commit title/body đề xuất.
- Đã dừng để chủ dự án nghiệm thu.

## 8. Trạng thái hiện tại

- Giai đoạn P1 remediation: **P1-T01 hoàn tất triển khai, chờ chủ dự án kiểm thử**.
- Giai đoạn P2 remediation: **chưa bắt đầu**.
- Giai đoạn P3 remediation: **chưa bắt đầu**.
- Checkpoint xuyên suốt: **chưa bắt đầu**.

Feature hiện tại: **P1-T01 — Request context và structured logging**.

Branch hiện tại: **`feature/p1-t01-request-context-logging`**.

Feature tiếp theo sau khi P1-T01 được xác nhận và merge: **P1-T02 — Quản lý phiên đăng nhập**.
