# SalesFlow CRM — Biên bản nghiệm thu kỹ thuật

- Ngày thực hiện: `28/07/2026`
- Nhánh kiểm tra: `develop`
- Phạm vi: các task đã đánh dấu hoàn thành trong `docs/PROJECT_PHASES.md` và `docs/EXTENSION_FEATURES_P1_P8.md`
- Nguồn yêu cầu chuẩn: `docs/requirements.md` phiên bản `2.1`
- Kết luận: **Đạt nghiệm thu kỹ thuật tự động; chờ chủ dự án nghiệm thu thủ công các luồng UI và vận hành thực tế**

## 1. Quy ước trạng thái

| Trạng thái | Ý nghĩa |
|---|---|
| Đạt kỹ thuật | Có implementation, test liên quan đạt và không làm hỏng regression suite |
| Chờ thủ công | Test tự động đạt nhưng còn tiêu chí cần quan sát trên trình duyệt hoặc nhiều tài khoản/service thật |
| Chưa triển khai | Roadmap đang ghi chưa bắt đầu hoặc chờ duyệt |
| Cần đồng bộ tài liệu | Code/test và các file roadmap đang mô tả trạng thái khác nhau |

Không dùng kết quả test tự động để tự xác nhận thay chủ dự án đối với cảm nhận UI, responsive, realtime nhiều trình duyệt, email, file tải xuống và quy trình vận hành.

## 2. Kết quả quality gate

| Gate | Lệnh | Kết quả |
|---|---|---|
| Docker runtime | `docker compose ps` | 10 service đang chạy và healthy |
| Migration | `docker compose exec app php artisan migrate:status` | 42 migration đã chạy, không có migration pending |
| Regression | `docker compose exec app php artisan test` | **426 test đạt, 2.220 assertions, 0 lỗi** trong `314,63s` |
| Code style | `docker compose exec app ./vendor/bin/pint --test` | **PASS 468 file** |
| Static analysis | `docker compose exec app ./vendor/bin/phpstan analyse --no-progress` | **0 lỗi** |
| Frontend build | `docker compose exec vite npm run build` | **PASS**, 733 module, build trong `9,84s` |
| Runtime log | `docker compose logs --since 15m --tail 300 app horizon reverb scheduler vite nginx` | Không thấy exception; `/up` trả `200`; scheduler reminder chạy thành công |

Các service được xác nhận healthy: `app`, `horizon`, `mailpit`, `minio`, `nginx`, `postgres`, `redis`, `reverb`, `scheduler`, `vite`.

## 3. Nghiệm thu các giai đoạn P1-P8

| Giai đoạn | Kết quả kỹ thuật | Bằng chứng tiêu biểu | Trạng thái còn lại |
|---|---|---|---|
| P1 — Nền tảng và Docker | Đạt kỹ thuật | Docker healthy, request context, system health, environment readiness và session tests đạt | Kiểm tra trực quan App Shell, Mailpit, MinIO và realtime |
| P2 — Users/RBAC | Đạt kỹ thuật | Authorization 5 role, data scope, user CRUD, role assignment, audit/realtime tests đạt | Đăng nhập thủ công từng role và xác nhận navigation |
| P3 — Leads | Đạt kỹ thuật | Lead lifecycle, policy, repository, duplicate/trash, conversion, bulk, scoring và timeline tests đạt | Chạy trọn luồng Lead trên trình duyệt bằng sales/manager/viewer |
| P4 — Companies/Contacts | Đạt kỹ thuật | CRUD, scope, duplicate, attachment, Customer 360, merge và relationship map tests đạt | Kiểm tra file thật, merge preview và layout Customer 360 |
| P5 — Pipelines/Opportunities | Đạt kỹ thuật | Pipeline, Opportunity, Kanban, realtime, close workflow, line items, stage rules, quote và forecast tests đạt | Kéo Kanban, realtime hai tài khoản, kiểm tra bản in báo giá |
| P6 — Activities/Tasks | Đạt kỹ thuật | Timeline, task CRUD, checklist/comment, mention, Kanban, calendar, reminder và SLA tests đạt | Kiểm tra editor, lịch, notification và scheduler theo thời gian thật |
| P7 — Dashboard/Reports | Đạt kỹ thuật | KPI, funnel, revenue, performance, cache consistency, drill-down, saved filter và data quality tests đạt | Xác nhận chart animation, tiếng Việt, filter độc lập và số liệu trực quan |
| P8 — Import/Export/Notifications/Audit | Đạt kỹ thuật | Import/export execution/history, signed download, notification/preferences, audit access/detail/realtime tests đạt | Import file thật, tải file lỗi/export, Mailpit và audit realtime hai trình duyệt |

## 4. Nghiệm thu backlog mở rộng đã đánh dấu hoàn thành

| Mã | Feature | Test/bằng chứng chính | Kết quả |
|---|---|---|---|
| P9-01 | UI/UX hardening toàn hệ thống | `UiHardeningTest`, `DataListUiConsistencyTest` | Đạt kỹ thuật, chờ kiểm tra UI |
| P1-X01 | Health check page/API | `SystemHealthCheckTest` | Đạt kỹ thuật |
| P1-X02 | Environment readiness checklist | `EnvironmentReadinessTest` | Đạt kỹ thuật |
| P2-X01 | Permission matrix UI | `PermissionMatrixTest` | Đạt kỹ thuật, chờ kiểm tra bảng UI |
| P2-X02 | User activity/session history | `UserSessionHistoryTest` | Đạt kỹ thuật |
| P8-X04 | Audit detail drawer | `AuditLogDetailDrawerTest` | Đạt kỹ thuật, chờ kiểm tra drawer |
| P8-X01 | Import history page | `ImportHistoryTest` | Đạt kỹ thuật |
| P8-X02 | Export history page | `ExportHistoryTest` | Đạt kỹ thuật |
| P3-X01 | Lead notes/timeline | `LeadNotesTimelineTest` | Đạt kỹ thuật |
| P3-X03 | Bulk actions for Lead | `LeadBulkActionsTest` | Đạt kỹ thuật |
| P3-X04 | Lead conversion preview | `LeadConversionPreviewTest` | Đạt kỹ thuật, chờ kiểm tra modal |
| P4-X01 | Customer 360 | `Customer360Test` | Đạt kỹ thuật, chờ kiểm tra UI |
| P4-X02 | Merge Company/Contact | `CustomerMergeTest` | Đạt kỹ thuật, chờ kiểm tra dữ liệu thật |
| P5-X01 | Opportunity line items | `OpportunityLineItemsTest` | Đạt kỹ thuật |
| P5-X03 | Stage required fields | `StageRequiredFieldsTest` | Đạt kỹ thuật |
| P6-X04 | SLA chăm sóc khách hàng | `CustomerSlaTest` | Đạt kỹ thuật, chờ kiểm tra lịch chạy thật |
| P7-X01 | Report drill-down | `ReportDrillDownTest` | Đạt kỹ thuật, chờ đối chiếu số liệu UI |
| P7-X02 | Saved report filters | `SavedReportFiltersTest` | Đạt kỹ thuật |
| P7-X04 | Data quality dashboard | `DataQualityDashboardTest` | Đạt kỹ thuật |
| P2-X03 | Invitation flow | `UserInvitationTest` | Đạt kỹ thuật, chờ kiểm tra Mailpit |
| P3-X02 | Lead scoring | `LeadScoringTest` | Đạt kỹ thuật |
| P4-X03 | Customer relationship map | `CustomerRelationshipMapTest` | Đạt kỹ thuật, chờ kiểm tra responsive |
| P5-X02 | Quote/proposal basic | `QuoteTest` | Đạt kỹ thuật, chờ kiểm tra bản in/file |
| P5-X04 | Forecast category | `ForecastCategoryTest` | Đạt kỹ thuật |
| P8-X03 | Notification preferences | `NotificationPreferencesTest` | Đạt kỹ thuật, chờ kiểm tra từng kênh |

## 5. Checklist chủ dự án nghiệm thu thủ công

### 5.1 Tài khoản và phân quyền

- [ ] Đăng nhập lần lượt `super-admin`, `admin IT`, `sales-manager`, `sales`, `viewer`.
- [ ] Navigation và nút hành động đúng quyền; nhập URL trực tiếp vẫn bị backend chặn.
- [ ] Admin ngoài phòng IT không xem được Audit; Super Admin và Admin IT xem được.
- [ ] Thu hồi phiên đăng nhập ở thiết bị khác có hiệu lực ngay và có audit.

### 5.2 Luồng CRM chính

- [ ] Tạo Lead → phát hiện trùng → phân công → đổi trạng thái → thêm note/task → convert.
- [ ] Conversion preview thể hiện đúng record tạo mới/ghép và không convert lặp.
- [ ] Company 360 hiển thị đúng Contact, Opportunity, Task, Activity, file và timeline.
- [ ] Merge Company/Contact giữ đúng relation và audit.
- [ ] Opportunity chuyển stage, kiểm tra required field, line item, forecast, won/lost.

### 5.3 UI/UX và báo cáo

- [ ] Chuyển menu không giật hoặc mất trạng thái ngoài ý muốn.
- [ ] List/detail/edit đồng nhất ở desktop, tablet, mobile và dark mode.
- [ ] Search/filter/pagination/back-forward giữ đúng URL state của từng màn.
- [ ] Chart có tiếng Việt, animation vừa phải, không mất số liệu khi đổi tab/filter.
- [ ] Keyboard focus rõ; modal giữ focus, chống submit lặp và xác nhận action nguy hiểm.

### 5.4 Platform

- [ ] Import một CSV thật: preview → mapping → validation → queue → history → error file.
- [ ] Export dữ liệu theo role; signed URL hợp lệ và URL sửa tay bị từ chối.
- [ ] Invitation và notification email xuất hiện đúng trong Mailpit.
- [ ] Audit realtime xuất hiện trên trình duyệt Admin IT khi tài khoản khác thao tác.
- [ ] File private không tải được khi không có quyền hoặc link hết hạn.

## 6. Các điểm chưa thể ký nghiệm thu cuối

1. **Nghiệm thu UI chưa có xác nhận của chủ dự án**: test render không thay thế đánh giá độ mượt, bố cục, responsive, dark mode và icon.
2. **Realtime nhiều phiên cần test thủ công**: test channel/payload đã đạt nhưng cần hai trình duyệt/tài khoản để xác nhận trải nghiệm.
3. **Email và file thật cần quan sát đầu ra**: Mailpit, CSV lỗi/export, attachment và bản in báo giá.
4. **P9-02 đến P9-07 chưa triển khai**: security hardening, performance/index review, regression checkpoint chính thức, CI, production image/deployment, backup/monitoring.
5. **P10-01 và P10-02 mới ở trạng thái chờ duyệt** trong `docs/ADVANCED_ENTERPRISE_FEATURES.md`.

## 7. Lỗi đồng bộ tài liệu cần xử lý

1. `AGENTS.md` và một số liên kết vẫn trỏ tới `PROJECT_PHASES.md` ở thư mục gốc, trong khi file hiện hành nằm tại `docs/PROJECT_PHASES.md`.
2. `docs/PROJECT_PHASES.md` dùng một số link có tiền tố `docs/`; khi mở từ trong thư mục `docs`, link có thể trỏ nhầm thành `docs/docs/...`.
3. `docs/checkpoints/technical/TECHNICAL_REMEDIATION_PHASES.md` còn ghi `P1-T02` là “Chưa bắt đầu” tại bảng tổng hợp, nhưng phần nhật ký và regression test chứng minh đã triển khai.
4. Roadmap chính ghi một số giai đoạn “đã qua checkpoint”, trong khi phase log chi tiết vẫn còn câu “chờ chủ dự án kiểm thử”.

Các lỗi trên không làm regression fail nhưng khiến người đọc khó xác định nguồn trạng thái chính xác. Nên xử lý thành một task tài liệu riêng, không trộn vào feature nghiệp vụ.

## 8. Kết luận nghiệm thu

- **Có thể chấp nhận toàn bộ task P1-P8 và 25 extension feature ở mức kỹ thuật tự động.**
- **Chưa ký nghiệm thu sản phẩm cuối** cho tới khi hoàn thành checklist thủ công tại mục 5 và triển khai P9-02 đến P9-07.
- Nếu checklist thủ công không phát hiện lỗi, trạng thái phù hợp tiếp theo là: `Đã nghiệm thu bởi chủ dự án`.
