# SalesFlow CRM — Backlog mở rộng P1-P8 và P9-01 UI/UX Hardening

> Tài liệu này dùng để chia nhỏ các feature cải thiện sau khi hoàn tất P1-P8. Nguồn yêu cầu chuẩn vẫn là [`docs/requirements.md`](requirements.md); file này chỉ sắp xếp thứ tự làm branch và checklist nghiệm thu để triển khai từng feature gọn hơn.

## 1. Mục tiêu

- Gom toàn bộ đề xuất mở rộng từ P1 đến P8 vào một backlog có thứ tự ưu tiên rõ ràng.
- Đưa `P9-01` lên đầu như một feature hardening UI/UX toàn hệ thống trước khi bổ sung các chức năng sâu hơn.
- Mỗi feature đủ nhỏ để tạo branch riêng, làm xong dừng lại cho chủ dự án kiểm thử thủ công.
- Giữ đúng các quyết định đã chốt: PostgreSQL `BIGINT` tự tăng, cây thư mục technical-layer hiện tại, Flux UI Free, UI tiếng Việt, không cài package nếu chưa có nhu cầu thật.

## 2. Cách dùng file này

Khi bắt đầu một feature:

1. Tạo branch theo cột `Branch đề xuất`.
2. Đọc `docs/requirements.md`, feature tương ứng trong `PROJECT_PHASES.md` nếu có, và skill `.agents/skills/salesflow-feature-development/SKILL.md`.
3. Nếu feature chạm UI, dùng thêm skill `.agents/skills/salesflow-ui-consistency/SKILL.md`.
4. Trước khi code, trình bày mục đích, kết quả cuối, phạm vi, dependency, requirement ID và checklist nghiệm thu.
5. Làm đúng một feature, chạy test liên quan, cập nhật checkpoint nếu cần, đề xuất commit và dừng.

## 3. Thứ tự ưu tiên tổng thể

| Thứ tự | Mã | Feature | Branch đề xuất | Phụ thuộc | Requirement/GAP chính | Trạng thái | Lý do làm trước/sau |
|---:|---|---|---|---|---|---|---|
| 1 | P9-01 | UI/UX hardening toàn hệ thống | `feature/p9-01-ui-ux-hardening` | P8-07 | REQ-UI, GAP-UI-001..003 | ✅ Hoàn thành | Chuẩn hóa trải nghiệm trước khi mở rộng chức năng mới, tránh mỗi màn một kiểu. |
| 2 | P1-X01 | Health check page/API | `feature/p1-x01-health-check` | P1, P8-07 | REQ-PLATFORM, REQ-OPS | ✅ Hoàn thành | Có điểm kiểm tra app/queue/db/realtime trước deploy thử. |
| 3 | P1-X02 | Environment readiness checklist | `feature/p1-x02-env-readiness` | P1-X01 | REQ-OPS, REQ-DOC | ✅ Hoàn thành | Giúp local/staging/production tự kiểm tra cấu hình thiếu. |
| 4 | P2-X01 | Permission matrix UI | `feature/p2-x01-permission-matrix-ui` | P2-08, P9-01 | REQ-RBAC | ✅ Hoàn thành | Admin nhìn rõ role nào có quyền gì, dễ nghiệm thu phân quyền. |
| 5 | P2-X02 | User activity/session history | `feature/p2-x02-user-session-history` | P1-T02, P2-08 | MOD-AUTH, REQ-AUDIT | ✅ Hoàn thành | Bổ sung kiểm soát phiên và lịch sử đăng nhập thực tế. |
| 6 | P8-X04 | Audit detail drawer | `feature/p8-x04-audit-detail-drawer` | P8-07, P9-01 | REQ-AUDIT | ✅ Hoàn thành | Audit hiện có cần xem chi tiết dễ hơn nhưng vẫn immutable. |
| 7 | P8-X01 | Import history page | `feature/p8-x01-import-history` | P8-04, P9-01 | REQ-IO, REQ-NOTIFY | ✅ Hoàn thành | Người dùng cần xem lại các lần import, lỗi và file kết quả. |
| 8 | P8-X02 | Export history page | `feature/p8-x02-export-history` | P8-05, P9-01 | REQ-IO | ✅ Hoàn thành | Theo dõi file export, trạng thái, hạn tải và quyền tải lại. |
| 9 | P3-X01 | Lead notes/timeline hoàn chỉnh | `feature/p3-x01-lead-notes-timeline` | P3-10, P6-02 | REQ-LEAD, REQ-WORK | ✅ Hoàn thành | Lead cần lịch sử chăm sóc rõ trước scoring/bulk nâng cao. |
| 10 | P3-X03 | Bulk actions for Lead | `feature/p3-x03-lead-bulk-actions` | P3-10, P9-01 | REQ-LEAD, REQ-RBAC | ✅ Hoàn thành | Tăng tốc thao tác dữ liệu lớn, phải re-authorize backend. |
| 11 | P3-X04 | Lead conversion preview | `feature/p3-x04-lead-conversion-preview` | P3-09, P5-09 | REQ-LEAD, REQ-COMPANY, REQ-PIPELINE | ✅ Hoàn thành | Chuyển đổi Lead cần preview rõ Company/Contact/Opportunity sẽ tạo/ghép. |
| 12 | P4-X01 | Customer 360 page | `feature/p4-x01-customer-360` | P4-07, P5-09, P6-07 | REQ-COMPANY, REQ-CONTACT, REQ-WORK | ✅ Hoàn thành | Gom hồ sơ khách hàng, contact, opportunity, task, timeline vào một nơi. |
| 13 | P4-X02 | Duplicate/merge Company/Contact | `feature/p4-x02-customer-merge` | P4-05, P4-X01 | REQ-COMPANY, REQ-CONTACT, REQ-AUDIT | ✅ Hoàn thành | Dữ liệu khách hàng cần cơ chế merge an toàn trước khi dùng thật. |
| 14 | P5-X01 | Opportunity product/line items | `feature/p5-x01-opportunity-line-items` | P5-09 | REQ-PIPELINE | ✅ Hoàn thành | Opportunity hiện sơ khai; line items giúp giá trị doanh thu thực hơn. |
| 15 | P5-X03 | Stage required fields | `feature/p5-x03-stage-required-fields` | P5-05, P5-X01 | REQ-PIPELINE | ✅ Hoàn thành | Ép dữ liệu cần thiết khi chuyển stage, giảm pipeline “ảo”. |
| 16 | P6-X04 | SLA chăm sóc khách hàng | `feature/p6-x04-customer-sla` | P6-07, P4-X01 | REQ-WORK, REQ-NOTIFY | ✅ Hoàn thành | Giúp CRM có cảnh báo chăm sóc đúng hạn, không chỉ lưu task. |
| 17 | P7-X01 | Report drill-down | `feature/p7-x01-report-drill-down` | P7-FIX, P9-01 | REQ-REPORT | ✅ Hoàn thành | Từ chart/KPI mở ra danh sách dữ liệu gốc theo scope để kiểm chứng số liệu. |
| 18 | P7-X02 | Saved report filters | `feature/p7-x02-saved-report-filters` | P7-FIX | REQ-REPORT | ✅ Hoàn thành | Người quản lý lưu bộ lọc thường dùng, giảm thao tác lặp. |
| 19 | P7-X03 | Manager dashboard | `feature/p7-x03-manager-dashboard` | P7-X01 | REQ-REPORT, REQ-RBAC | Màn riêng cho sales-manager theo phòng ban, khác dashboard tổng quan. | Bỏ ko làm
| 20 | P7-X04 | Data quality dashboard | `feature/p7-x04-data-quality-dashboard` | P3-X03, P4-X02 | REQ-REPORT, REQ-LEAD | ✅ Hoàn thành | Theo dõi dữ liệu thiếu, trùng, chưa chăm sóc, stale records. |
| 21 | P2-X03 | Invitation flow | `feature/p2-x03-user-invitations` | P2-X01, P8-06 | MOD-AUTH, REQ-NOTIFY | ✅ Hoàn thành | Mời user qua email, phù hợp sau khi notification đã ổn. |
| 22 | P3-X02 | Lead scoring | `feature/p3-x02-lead-scoring` | P3-X01, P7-X04 | REQ-LEAD, REQ-REPORT | ✅ Hoàn thành | Cần timeline/data quality trước để scoring không cảm tính. |
| 23 | P4-X03 | Customer relationship map | `feature/p4-x03-customer-relationship-map` | P4-X01, P4-X02 | REQ-CUSTOMER | ✅ Hoàn thành | Hữu ích nhưng nên làm sau khi dữ liệu customer sạch. |
| 24 | P5-X02 | Quote/proposal basic | `feature/p5-x02-quote-proposal-basic` | P5-X01 | REQ-PIPELINE, REQ-IO | ✅ Hoàn thành | Tạo báo giá cơ bản từ opportunity, có thể export/download. |
| 25 | P5-X04 | Forecast category | `feature/p5-x04-forecast-category` | P5-X03, P7-X01 | REQ-PIPELINE, REQ-REPORT | ✅ Hoàn thành | Tăng độ chính xác forecast sau khi stage rule ổn. |
| 26 | P6-X01 | Calendar export | `feature/p6-x01-calendar-export` | P6-06 | REQ-WORK | Export lịch `.ics`, chưa cần tích hợp calendar bên thứ ba. |
| 27 | P6-X02 | Task recurring/reminder nâng cao | `feature/p6-x02-recurring-reminders` | P6-X04 | REQ-WORK, REQ-NOTIFY | Làm sau SLA để tránh nhắc hạn trùng logic. |
| 28 | P6-X03 | Comment attachments/mentions | `feature/p6-x03-comments-mentions-attachments` | P6-04, P8-06 | REQ-WORK, REQ-NOTIFY, REQ-PLATFORM | Collaboration sâu hơn, phụ thuộc notification và file private. |
| 29 | P8-X03 | Notification preferences | `feature/p8-x03-notification-preferences` | P8-06, P2-X03 | REQ-NOTIFY | User tự bật/tắt email/broadcast/database theo loại sự kiện. |
| 30 | P8-X05 | Audit retention policy | `feature/p8-x05-audit-retention-policy` | P8-X04 | REQ-AUDIT, REQ-OPS | Chính sách lưu giữ audit cho production; không xóa tùy tiện trên UI. |

## 4. Chi tiết theo giai đoạn

### P1 — Platform foundation mở rộng

#### P1-X01 — Health check page/API

- **Mục tiêu**: Có trang/endpoint kiểm tra trạng thái app, database, redis, queue, scheduler, realtime và storage.
- **Phạm vi**: Route protected cho admin, service health checker, UI trạng thái tiếng Việt, endpoint JSON nội bộ nếu cần.
- **Không làm**: Monitoring SaaS, alert production.
- **Nghiệm thu**: Admin mở trang health thấy từng service OK/Warning/Fail; lỗi có request ID/log; user thường không truy cập được.

#### P1-X02 — Environment readiness checklist

- **Mục tiêu**: Kiểm tra nhanh cấu hình local/staging/production trước deploy.
- **Phạm vi**: Checklist `.env`, mail, queue, reverb, storage, app debug, secure cookie, timezone.
- **Không làm**: Tự sửa `.env` hoặc secret.
- **Nghiệm thu**: Admin biết cấu hình nào thiếu/sai; production không lộ secret.

### P2 — Users, Departments, Roles, Permissions mở rộng

#### P2-X01 — Permission matrix UI

- **Mục tiêu**: Hiển thị bảng role/permission/data scope để admin kiểm chứng phân quyền.
- **Phạm vi**: Trang read-only hoặc chỉnh sửa có kiểm soát nếu requirement xác nhận; nhóm permission theo module.
- **Không làm**: Sinh permission mới tùy ý từ UI.
- **Nghiệm thu**: Nhìn được `super-admin`, `admin`, `sales-manager`, `sales`, `viewer` có quyền gì; backend vẫn enforce policy.

#### P2-X02 — User activity/session history

- **Mục tiêu**: Theo dõi phiên đăng nhập, thiết bị, IP, thời gian đăng nhập/đăng xuất và trạng thái phiên.
- **Phạm vi**: List session theo user, revoke session khác nếu có quyền, audit thao tác revoke.
- **Không làm**: SSO/OAuth ngoài hệ thống.
- **Nghiệm thu**: Admin IT/super-admin xem được lịch sử; user thường chỉ xem phiên của mình nếu được phép.

#### P2-X03 — Invitation flow

- **Mục tiêu**: Mời user mới qua email thay vì tạo mật khẩu thủ công.
- **Phạm vi**: Token mời, email, hết hạn, accept invitation, active state, audit.
- **Không làm**: Multi-tenant invitation.
- **Nghiệm thu**: Invite idempotent, token hết hạn, không lộ user tồn tại qua lỗi.

### P3 — Leads mở rộng

#### P3-X01 — Lead notes/timeline hoàn chỉnh

- **Mục tiêu**: Lead có timeline thống nhất: note, activity, task, assignment, status, import/export và audit quan trọng.
- **Phạm vi**: Tab timeline trong detail, tạo note nhanh, filter loại sự kiện, data scope.
- **Không làm**: Mention/attachment nâng cao nếu chưa sang P6/P8.
- **Nghiệm thu**: Vào chi tiết Lead thấy lịch sử đầy đủ theo thời gian Việt Nam, user ngoài scope không xem được.

#### P3-X02 — Lead scoring

- **Mục tiêu**: Chấm điểm Lead dựa trên nguồn, trạng thái, priority, tương tác, độ đầy đủ dữ liệu và thời gian chưa chăm sóc.
- **Phạm vi**: Rule cấu hình ban đầu trong code/config, score lưu/tính backend, filter/sort theo score.
- **Không làm**: AI scoring hoặc machine learning.
- **Nghiệm thu**: Score thay đổi khi dữ liệu thay đổi; danh sách Lead có lọc/sắp xếp theo score.

#### P3-X03 — Bulk actions for Lead

- **Mục tiêu**: Chọn nhiều Lead để assign, đổi trạng thái, gắn tag hoặc xóa mềm.
- **Phạm vi**: Bulk selection theo trang/filter, modal xác nhận, backend re-authorize từng ID, audit.
- **Không làm**: Bulk merge.
- **Nghiệm thu**: Không thao tác được Lead ngoài scope; kết quả báo số thành công/thất bại.

#### P3-X04 — Lead conversion preview

- **Mục tiêu**: Trước khi convert, user thấy rõ Company/Contact/Opportunity nào sẽ được tạo mới hoặc ghép.
- **Phạm vi**: Preview duplicate, lựa chọn merge/create, transaction convert, rollback khi lỗi.
- **Không làm**: Merge Company/Contact phức tạp ngoài conversion.
- **Nghiệm thu**: Convert không lặp; preview không lộ record ngoài scope.

### P4 — Companies & Contacts mở rộng

#### P4-X01 — Customer 360 page

- **Mục tiêu**: Một màn nhìn toàn cảnh khách hàng: company, contacts, opportunities, tasks, activities, files và timeline.
- **Phạm vi**: Detail tổng hợp theo data scope, tab/section rõ, empty/loading/error states.
- **Không làm**: Relationship graph nâng cao.
- **Nghiệm thu**: Sales/manager nhìn được đúng dữ liệu thuộc scope; không N+1 rõ rệt.

#### P4-X02 — Duplicate/merge Company/Contact

- **Mục tiêu**: Phát hiện và xử lý khách hàng/liên hệ trùng lặp an toàn.
- **Phạm vi**: Duplicate detection, preview merge, chọn field thắng, chuyển relations, audit immutable.
- **Không làm**: Auto merge không cần người xác nhận.
- **Nghiệm thu**: Merge giữ relation quan trọng, có lịch sử và rollback transaction khi lỗi.

#### P4-X03 — Customer relationship map

- **Mục tiêu**: Hiển thị quan hệ giữa Company, Contact, Opportunity và Lead đã convert.
- **Phạm vi**: Map dạng cây/list trực quan bằng Blade/Tailwind, permission-aware.
- **Không làm**: Cài graph library nếu chưa cần.
- **Nghiệm thu**: Người dùng hiểu quan hệ dữ liệu mà không bị rối UI.

### P5 — Pipelines & Opportunities mở rộng

#### P5-X01 — Opportunity product/line items

- **Mục tiêu**: Opportunity có dòng sản phẩm/dịch vụ để tính giá trị bán hàng thực tế.
- **Phạm vi**: Schema line items, quantity, unit price, discount/tax nếu cần, total backend decimal.
- **Không làm**: Quản lý kho hoặc catalog sản phẩm đầy đủ.
- **Nghiệm thu**: Tổng tiền/weighted value tính đúng; audit khi thay đổi line item.

#### P5-X02 — Quote/proposal basic

- **Mục tiêu**: Tạo báo giá cơ bản từ opportunity để gửi khách hàng hoặc tải file.
- **Phạm vi**: Template Blade/PDF hoặc export đơn giản theo package hiện có, signed download.
- **Không làm**: Ký số, version legal contract.
- **Nghiệm thu**: File tải qua route có quyền; dữ liệu tiền và khách hàng đúng.

#### P5-X03 — Stage required fields

- **Mục tiêu**: Stage có rule yêu cầu dữ liệu trước khi chuyển bước.
- **Phạm vi**: Cấu hình required fields theo stage, validation backend, message tiếng Việt.
- **Không làm**: Workflow builder kéo-thả.
- **Nghiệm thu**: Thiếu field thì không chuyển stage; lỗi rõ ngay tại UI/modal.

#### P5-X04 — Forecast category

- **Mục tiêu**: Tách forecast theo commit/best case/pipeline/omitted để báo cáo doanh thu sát hơn.
- **Phạm vi**: Enum/category, filter report, KPI forecast.
- **Không làm**: Forecast AI.
- **Nghiệm thu**: Report forecast đổi theo category và data scope.

### P6 — Activities & Tasks mở rộng

#### P6-X01 — Calendar export

- **Mục tiêu**: Cho user export lịch task/meeting ra file `.ics`.
- **Phạm vi**: Download theo scope, timezone Việt Nam, route có quyền.
- **Không làm**: Sync hai chiều Google/Outlook.
- **Nghiệm thu**: File import được vào calendar ngoài, không chứa task ngoài scope.

#### P6-X02 — Task recurring/reminder nâng cao

- **Mục tiêu**: Task lặp và reminder linh hoạt hơn nhưng không gửi trùng.
- **Phạm vi**: Rule lặp đơn giản, scheduler idempotent, notification.
- **Không làm**: Cron expression builder phức tạp.
- **Nghiệm thu**: Reminder đúng thời điểm, retry không tạo thông báo lặp.

#### P6-X03 — Comment attachments/mentions

- **Mục tiêu**: Comment trong task/activity hỗ trợ file riêng tư và nhắc người liên quan.
- **Phạm vi**: Upload private, mention user theo scope, notification, audit.
- **Không làm**: Rich collaborative editor realtime.
- **Nghiệm thu**: User được mention nhận thông báo; file download luôn authorize.

#### P6-X04 — SLA chăm sóc khách hàng

- **Mục tiêu**: Cảnh báo Lead/Customer/Opportunity quá hạn chăm sóc.
- **Phạm vi**: Rule SLA theo status/stage, scheduler, list overdue, notification.
- **Không làm**: SLA nhiều tầng enterprise.
- **Nghiệm thu**: Record quá hạn được đánh dấu; hoàn tất activity/task thì SLA cập nhật.

### P7 — Dashboard & Reports mở rộng

#### P7-X01 — Report drill-down

- **Mục tiêu**: Click KPI/chart để xem danh sách dữ liệu gốc tạo nên số liệu.
- **Phạm vi**: Drill-down route/modal/table, filter theo context chart, scoped query.
- **Không làm**: BI builder.
- **Nghiệm thu**: Tổng dòng drill-down khớp KPI/chart; không mất filter của màn khác.

#### P7-X02 — Saved report filters

- **Mục tiêu**: Lưu bộ lọc báo cáo thường dùng theo user.
- **Phạm vi**: CRUD filter preset cá nhân, đặt mặc định, URL state tương thích.
- **Không làm**: Chia sẻ filter giữa team nếu chưa xác nhận.
- **Nghiệm thu**: Reload không mất preset; mỗi tab báo cáo giữ filter riêng.

#### P7-X03 — Manager dashboard

- **Mục tiêu**: Dashboard riêng cho sales-manager theo đội/phòng ban.
- **Phạm vi**: KPI team, leaderboard, overdue, pipeline snapshot.
- **Không làm**: Admin analytics toàn công ty nếu trùng dashboard hiện có.
- **Nghiệm thu**: Manager chỉ thấy team/department đúng scope.

#### P7-X04 — Data quality dashboard

- **Mục tiêu**: Đo chất lượng dữ liệu CRM: thiếu email/phone, trùng, chưa chăm sóc, stale opportunity.
- **Phạm vi**: Metrics scoped, list hành động khắc phục, liên kết sang màn liên quan.
- **Không làm**: Tự động sửa dữ liệu hàng loạt.
- **Nghiệm thu**: Số liệu giúp phát hiện record cần xử lý; click được để kiểm tra.

### P8 — Import, Export, Notifications & Audit mở rộng

#### P8-X01 — Import history page

- **Mục tiêu**: Người dùng xem lại các job import, trạng thái, lỗi và file kết quả.
- **Phạm vi**: List/filter/detail import jobs, error file download có quyền.
- **Không làm**: Import template marketplace.
- **Nghiệm thu**: User chỉ thấy job mình/được phép; progress/result nhất quán.

#### P8-X02 — Export history page

- **Mục tiêu**: Theo dõi export đã tạo, trạng thái, hết hạn và tải lại nếu còn quyền.
- **Phạm vi**: List export jobs, signed download, trạng thái expired.
- **Không làm**: Public file sharing.
- **Nghiệm thu**: File expired không tải được; query export vẫn scoped.

#### P8-X03 — Notification preferences

- **Mục tiêu**: User tùy chỉnh nhận thông báo theo kênh và loại sự kiện.
- **Phạm vi**: Preferences database/email/broadcast, defaults an toàn, UI settings.
- **Không làm**: Quiet hours phức tạp nếu chưa cần.
- **Nghiệm thu**: Tắt một loại notification thì event tương ứng không gửi kênh đó.

#### P8-X04 — Audit detail drawer

- **Mục tiêu**: Xem chi tiết audit theo dạng drawer/log console dễ đọc.
- **Phạm vi**: Old/new values masked, request ID, actor, subject, timeline; chỉ super-admin/Admin IT.
- **Không làm**: Sửa/xóa audit từ UI.
- **Nghiệm thu**: Không lộ secret; realtime/list/detail đều permission-aware.

#### P8-X05 — Audit retention policy

- **Mục tiêu**: Định nghĩa cách lưu giữ, archive hoặc rotate audit/log cho production.
- **Phạm vi**: Policy tài liệu + scheduler/job nếu cần, không phá immutable audit UI.
- **Không làm**: Xóa audit tùy tiện không có retention rule.
- **Nghiệm thu**: Có quy tắc rõ audit giữ bao lâu, log vận hành rotate thế nào.

### P9-01 — UI/UX hardening toàn hệ thống

- **Mục tiêu**: Chuẩn hóa toàn bộ giao diện sau P8: app shell, navigation, list/detail/edit, table, filter, modal/form, chart, empty/loading/error states, responsive, dark mode, focus/keyboard và icon.
- **Branch đề xuất**: `feature/p9-01-ui-ux-hardening`
- **Phụ thuộc**: P8-07, các fix UI P7/P8 đang chờ kiểm thử.
- **Requirement/GAP**: `REQ-UI`, `REQ-REPORT`, `REQ-IO`, `REQ-AUDIT`, `GAP-UI-001`, `GAP-UI-002`, `GAP-UI-003`.
- **Phạm vi**:
  - Đồng bộ app shell: sidebar, topbar, breadcrumb, title/action, notification/user menu.
  - Đồng bộ các màn list/detail/edit của Lead, Company, Contact, Pipeline, Opportunity, Activity, Task, Report, Import, Export, Audit.
  - Đồng bộ filter theo từng màn, không cache chéo làm mất số liệu.
  - Chuẩn hóa table responsive, empty-filter, reset filter, loading/skeleton.
  - Chuẩn hóa modal/form: title, description, validation, processing, confirm danger.
  - Rà icon: dùng icon có ý nghĩa, bỏ icon trang trí nhìn “AI”, không lạm dụng icon trong mọi card/button.
  - Rà chart: tiếng Việt, loading/empty/error, dark mode, animation vừa phải.
- **Không làm**:
  - Không thêm nghiệp vụ mới.
  - Không cài UI package mới nếu Flux/Tailwind/Chart.js hiện có đáp ứng.
  - Không đổi cây thư mục hoặc refactor domain.
- **Nghiệm thu thủ công**:
  - Chuyển menu giữa các module không giật bất thường.
  - Mỗi màn list có search/filter/table/pagination/empty/loading nhất quán.
  - Mỗi màn detail/edit có layout cùng ngôn ngữ thị giác, form dễ đọc.
  - Chart đổi filter có chuyển động mượt, không mất dữ liệu khi chuyển tab.
  - Mobile/tablet không vỡ layout; dark mode đọc được.
  - Keyboard tab/focus nhìn rõ; action nguy hiểm có xác nhận.

## 5. Quy tắc chung cho toàn bộ backlog mở rộng

- Làm từ trên xuống theo thứ tự ưu tiên, trừ khi chủ dự án chọn feature khác.
- Mỗi feature dùng branch riêng, không gộp nhiều nghiệp vụ vào một branch.
- Nếu chạm UI, dùng skill `salesflow-ui-consistency` trước khi sửa.
- Nếu feature thêm package, phải chứng minh Flux UI Free/Tailwind/Blade/Alpine không đủ và ghi rõ lý do.
- Nếu feature chạm data scope, export, report, duplicate, realtime hoặc audit, backend phải enforce quyền trước UI.
- Seeder demo phải idempotent và phù hợp dữ liệu Việt Nam.
- Timestamp hiển thị theo `Asia/Ho_Chi_Minh`; audit/log có request ID khi phù hợp.
