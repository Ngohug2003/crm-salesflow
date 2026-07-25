# SalesFlow CRM — Kế hoạch giai đoạn và lộ trình triển khai

> **Nguồn requirement chuẩn:** [`docs/requirements.md`](docs/requirements.md). File này quản lý lộ trình feature, dependency, trạng thái checkpoint và liên kết đến nhật ký triển khai chi tiết. Khi có xung đột, áp dụng thứ tự ưu tiên tại mục 1.1 của requirement.

Tài liệu này là lộ trình và trạng thái checkpoint chính của dự án. Quy ước làm việc:

1. Chỉ triển khai đúng phạm vi của **một giai đoạn**.
2. Cuối giai đoạn, cập nhật lộ trình này và lưu nhật ký chi tiết vào thư mục `docs/checkpoints/`.
3. Dừng lại để chủ dự án chạy test thủ công.
4. Chỉ chuyển giai đoạn sau khi nhận xác nhận rõ ràng.
5. Không đánh dấu hoàn tất nếu migration, test, Pint, static analysis và frontend build chưa đạt.

---

## Trạng thái tổng quan

| Mã | Giai đoạn | Trạng thái | Checkpoint của chủ dự án | Nhật ký chi tiết |
|---|---|---|---|---|
| 0 | Phân tích kiến trúc và dữ liệu | Hoàn tất tài liệu ban đầu | Chưa xác nhận | [Chi tiết P0](docs/checkpoints/P0/ANALYSIS.md) |
| 1 | Khởi tạo nền tảng và Docker | Hoàn tất triển khai | Chờ chủ dự án kiểm thử | [Chi tiết P1](docs/checkpoints/P1/PHASE_LOG.md) |
| 2 | Users, Departments, Roles, Permissions | Hoàn tất P2-01 đến P2-08 | Đã qua checkpoint | [Chi tiết P2](docs/checkpoints/P2/PHASE_LOG.md) |
| 3 | Leads | Hoàn tất P3-01 đến P3-10 & Remediation | Đã sẵn sàng nghiệm thu | [Chi tiết P3](docs/checkpoints/P3/PHASE_LOG.md) / [Remediation](docs/checkpoints/technical/TECHNICAL_REMEDIATION_PHASES.md) |
| 4 | Companies và Contacts | Hoàn tất P4-01 đến P4-07 | Đã qua checkpoint nghiệm thu | [Chi tiết P4](docs/checkpoints/P4/PHASE_LOG.md) |
| 5 | Pipelines và Opportunities | Đang làm — P5-01 hoàn tất | P5-02 là feature tiếp theo | [Chi tiết P5](docs/checkpoints/P5/PHASE_LOG.md) |
| 6 | Activities và Tasks | Chưa bắt đầu | — | — |
| 7 | Dashboard và Reports | Chưa bắt đầu | — | — |
| 8 | Import, Export, Notifications và Audit | Chưa bắt đầu | — | — |
| 9 | Hoàn thiện, CI/CD và deployment | Chưa bắt đầu | — | — |

---

## Lộ trình và Feature Breakdown chi tiết theo từng giai đoạn

### Giai đoạn 0 — Phân tích kiến trúc và dữ liệu
- **Mục tiêu**: Phân tích nghiệp vụ, mô tả business flow, thiết kế ERD mức domain, xây dựng permission matrix và timelines.
- **Tài liệu lưu trữ**: [docs/checkpoints/P0/ANALYSIS.md](docs/checkpoints/P0/ANALYSIS.md)

### Giai đoạn 1 — Khởi tạo nền tảng và Docker
- **Mục tiêu**: Thiết lập Laravel stable, Livewire, Fortify, Horizon, Reverb, Docker Compose (9 services) và App Shell.
- **Tài liệu lưu trữ**: [docs/checkpoints/P1/PHASE_LOG.md](docs/checkpoints/P1/PHASE_LOG.md)

---

### Giai đoạn 2 — Users, Departments, Roles, Permissions
- **Mục tiêu**: Hoàn thiện tổ chức người dùng và ranh giới phân quyền trước khi tạo dữ liệu CRM.
- **Tài liệu lưu trữ**: [docs/checkpoints/P2/PHASE_LOG.md](docs/checkpoints/P2/PHASE_LOG.md)

| Mã | Feature | Branch | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P2-01 | Department schema và domain | `feature/p2-01-department-schema` | P1 | ✅ Hoàn tất |
| P2-02 | Quản lý phòng ban | `feature/p2-02-department-management` | P2-01 | ✅ Hoàn tất |
| P2-03 | Danh mục quyền và role seeder | `feature/p2-03-rbac-catalog-seeder` | P2-01 | ✅ Hoàn tất |
| P2-04 | Data scope và policies nền tảng | `feature/p2-04-data-scope-policies` | P2-03 | ✅ Hoàn tất |
| P2-05 | Danh sách người dùng | `feature/p2-05-user-list` | P2-01, P2-04 | ✅ Hoàn tất |
| P2-06 | Tạo và chỉnh sửa người dùng | `feature/p2-06-user-form` | P2-05 | ✅ Hoàn tất |
| P2-07 | Gán phòng ban và role | `feature/p2-07-user-role-assignment` | P2-03, P2-06 | ✅ Hoàn tất |
| P2-07-01 | Audit log toàn hệ thống | `feature/p2-07-user-role-assignment` | P2-07 | ✅ Hoàn tất |
| P2-07-02 | Realtime Audit Log | `feature/p2-07-user-role-assignment` | P2-07-01 | ✅ Hoàn tất |
| P2-08 | Authorization test và checkpoint | `feature/p2-08-authorization-checkpoint` | P2-02..P2-07-02 | ✅ Hoàn tất |

---

### Giai đoạn 3 — Leads
- **Mục tiêu**: Hoàn thiện vòng đời Lead từ tiếp nhận đến chuyển đổi; xử lý duplicate và soft delete/restore conflict an toàn.
- **Tài liệu lưu trữ**: [docs/checkpoints/P3/PHASE_LOG.md](docs/checkpoints/P3/PHASE_LOG.md)

| Mã | Feature | Branch | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P3-01 | Lead sources và tags | `feature/p3-01-lead-taxonomy` | P2-08 | ✅ Hoàn tất |
| P3-02 | Lead schema và domain | `feature/p3-02-lead-domain` | P3-01 | ✅ Hoàn tất |
| P3-03 | Repository và bộ lọc Lead | `feature/p3-03-lead-query-filters` | P3-02 | ✅ Hoàn tất |
| P3-04 | Lead policy và visibility | `feature/p3-04-lead-authorization` | P2-04, P3-03 | ✅ Hoàn tất |
| P3-05 | Danh sách Lead | `feature/p3-05-lead-list` | P3-03, P3-04 | ✅ Hoàn tất |
| P3-06 | Form và chi tiết Lead | `feature/p3-06-lead-form-detail` | P3-05 | ✅ Hoàn tất |
| P3-07 | Assignment và status history | `feature/p3-07-lead-assignment-status` | P3-06 | ✅ Hoàn tất |
| P3-08 | Duplicate, soft delete và restore | `feature/p3-08-lead-duplicate-delete` | P3-06 | ✅ Hoàn tất |
| P3-09 | Conversion eligibility và contract | `feature/p3-09-conversion-contract` | P3-07, P3-08 | ✅ Hoàn tất |
| P3-10 | Lead test và checkpoint | `feature/p3-10-lead-checkpoint` | P3-01..P3-09 | Hoàn tất triển khai — chờ kiểm thử |

#### Technical Remediation sau P3-08
- **Mục tiêu**: Chuẩn hóa cấu trúc hạ tầng, phân vùng dữ liệu an toàn, xử lý logic trùng lặp và xung đột ở database layer, đồng nhất hóa UI loaders & modal state.
- **Tài liệu lưu trữ**: [docs/checkpoints/technical/TECHNICAL_REMEDIATION_PHASES.md](docs/checkpoints/technical/TECHNICAL_REMEDIATION_PHASES.md)

| Mã | Phạm vi | Branch | Trạng thái |
|---|---|---|---|
| P1-T01 | Request context và structured logging | `feature/p1-t01-request-context-logging` | ✅ Hoàn tất |
| P1-T02 | Quản lý phiên đăng nhập | `feature/p1-t02-session-management` | ✅ Hoàn tất |
| P1-T03 | Chuẩn hóa App shell | `feature/p1-t03-app-shell` | ✅ Hoàn tất |
| P1-T04 | Quality foundation | `feature/p1-t04-quality-foundation` | ✅ Hoàn tất |
| P2-T01 | Chuẩn hóa User Repository boundary | `feature/p2-t01-user-repository-boundary` | ✅ Hoàn tất |
| P2-T02 | Audit correlation với Request ID | `feature/p2-t02-audit-request-correlation` | ✅ Hoàn tất |
| P2-T03 | Account và session lifecycle | `feature/p2-t03-account-session-lifecycle` | ✅ Hoàn tất |
| P2-T04 | UI states và form quản trị | `feature/p2-t04-admin-ui-states` | ✅ Hoàn tất |
| P3-T01 | Chuẩn hóa Lead Repository boundary | `feature/p3-t01-lead-repository-boundary` | ✅ Hoàn tất |
| P3-T02 | Duplicate guard tại backend | `feature/p3-t02-lead-duplicate-guard` | ✅ Hoàn tất |
| P3-T03 | Trash/restore conflict handling | `feature/p3-t03-lead-trash-conflict` | ✅ Hoàn tất |
| P3-T04 | UI states và form Lead | `feature/p3-t04-lead-ui-states` | ✅ Hoàn tất |
| TR-01 | Test và quality checkpoint toàn hệ thống | `feature/tr-01-system-quality-checkpoint` | ✅ Hoàn tất |
| TR-02 | Khôi phục và đồng bộ tài liệu | `docs/tr-02-requirements-sync` | ✅ Hoàn tất |
| TR-03 | Tách và chuẩn hóa phase log | `docs/tr-03-phase-log-split` | ✅ Hoàn tất |

---

### Giai đoạn 4 — Companies và Contacts
- **Mục tiêu**: Xây dựng hồ sơ khách hàng và quan hệ Company–Contact làm đích chuyển đổi Lead.
- **Tài liệu lưu trữ**: [docs/checkpoints/P4/PHASE_LOG.md](docs/checkpoints/P4/PHASE_LOG.md)

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P4-01 | Company schema và domain | `feature/p4-01-company-domain` | P2-08 | ✅ Hoàn tất |
| P4-02 | Contact schema và domain | `feature/p4-02-contact-domain` | P4-01 | ✅ Hoàn tất |
| P4-03 | Company CRUD | `feature/p4-03-company-crud` | P4-01, P2-04 | ✅ Hoàn tất |
| P4-04 | Contact CRUD và quan hệ | `feature/p4-04-contact-crud` | P4-02, P2-04 | ✅ Hoàn tất |
| P4-05 | Duplicate handling | `feature/p4-05-customer-duplicates` | P4-03, P4-04 | ✅ Hoàn tất |
| P4-06 | Attachment và timeline foundation | `feature/p4-06-customer-files-timeline` | P4-03, P4-04 | ✅ Hoàn tất |
| P4-07 | Customer authorization checkpoint | `feature/p4-07-customer-checkpoint` | P4-01..P4-06 | ✅ Hoàn tất |

---

### Giai đoạn 5 — Pipelines và Opportunities
- **Mục tiêu**: Quản lý pipeline có cấu hình, opportunity lifecycle, Kanban và realtime.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P5-01 | Pipeline và stage schema | `feature/p5-01-pipeline-domain` | P2-08 | Hoàn tất triển khai — chờ kiểm thử |
| P5-02 | Quản lý pipeline/stage | `feature/p5-02-pipeline-management` | P5-01 | Hoàn tất triển khai — chờ kiểm thử |
| P5-03 | Opportunity schema và domain | `feature/p5-03-opportunity-domain` | P4-02, P5-01 | Hoàn tất triển khai — chờ kiểm thử |
| P5-04 | Opportunity CRUD và weighted value | `feature/p5-04-opportunity-crud` | P5-03 | Hoàn tất triển khai — chờ kiểm thử |
| P5-05 | Stage transition và history | `feature/p5-05-stage-transition-history` | P5-04 | Hoàn tất triển khai — chờ kiểm thử |
| P5-06 | Opportunity Kanban | `feature/p5-06-opportunity-kanban` | P5-05 | Hoàn tất triển khai — chờ kiểm thử |
| P5-07 | Realtime private broadcast | `feature/p5-07-opportunity-realtime` | P5-06 | Hoàn tất triển khai — chờ kiểm thử |
| P5-08 | Close won/lost workflow | `feature/p5-08-opportunity-close` | P5-05 | Hoàn tất triển khai — chờ kiểm thử |
| P5-09 | Lead conversion integration và checkpoint | `feature/p5-09-lead-conversion-checkpoint` | P3-09, P4-02, P5-01..P5-08 | Hoàn tất triển khai — chờ kiểm thử |

---

### Giai đoạn 6 — Activities và Tasks
- **Mục tiêu**: Timeline tương tác, công việc, lịch và nhắc hạn cho các đối tượng CRM.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P6-01 | Activity polymorphic domain | `feature/p6-01-activity-domain` | P3-10, P4-07, P5-09 | Hoàn tất triển khai — chờ kiểm thử |
| P6-02 | Activity timeline CRUD | `feature/p6-02-activity-timeline` | P6-01 | Hoàn tất triển khai — chờ kiểm thử |
| P6-03 | Task domain và CRUD | `feature/p6-03-task-crud` | P6-01 | Hoàn tất triển khai — chờ kiểm thử |
| P6-04 | Checklist và comments | `feature/p6-04-task-collaboration` | P6-03 | Hoàn tất triển khai — chờ kiểm thử |
| P6-05 | Task list và Kanban | `feature/p6-05-task-views` | P6-03, P6-04 | Hoàn tất triển khai — chờ kiểm thử |
| P6-06 | Calendar và reminders | `feature/p6-06-calendar-reminders` | P6-03 | Hoàn tất triển khai — chờ kiểm thử |
| P6-07 | Activity/Task checkpoint | `feature/p6-07-activity-task-checkpoint` | P6-01..P6-06 | ⏳ Chưa bắt đầu |

---

### Giai đoạn 7 — Dashboard và Reports
- **Mục tiêu**: Số liệu thực, bộ lọc dùng chung và báo cáo bán hàng có kiểm soát data scope.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P7-01 | Metrics query services | `feature/p7-01-metrics-services` | P5-09, P6-07 | ⏳ Chưa bắt đầu |
| P7-02 | Dashboard filters và KPI | `feature/p7-02-dashboard-kpi` | P7-01 | ⏳ Chưa bắt đầu |
| P7-03 | Funnel report | `feature/p7-03-funnel-report` | P7-01 | ⏳ Chưa bắt đầu |
| P7-04 | Revenue và forecast report | `feature/p7-04-revenue-forecast` | P7-01 | ⏳ Chưa bắt đầu |
| P7-05 | Sales performance report | `feature/p7-05-sales-performance` | P7-01, P6-07 | ⏳ Chưa bắt đầu |
| P7-06 | Report cache và checkpoint | `feature/p7-06-report-checkpoint` | P7-02..P7-05 | ⏳ Chưa bắt đầu |

---

### Giai đoạn 8 — Import, Export, Notifications và Audit
- **Mục tiêu**: Xử lý dữ liệu lớn qua queue, notification center và audit hoàn chỉnh.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P8-01 | Import upload và preview | `feature/p8-01-import-upload-preview` | P3-10, P4-07 | ⏳ Chưa bắt đầu |
| P8-02 | Column mapping và validation | `feature/p8-02-import-mapping-validation` | P8-01 | ⏳ Chưa bắt đầu |
| P8-03 | Chunk queue và duplicate strategy | `feature/p8-03-import-queue-duplicates` | P8-02 | ⏳ Chưa bắt đầu |
| P8-04 | Import progress và error file | `feature/p8-04-import-progress-errors` | P8-03 | ⏳ Chưa bắt đầu |
| P8-05 | Queued export và signed download | `feature/p8-05-export-signed-download` | P7-06 | ⏳ Chưa bắt đầu |
| P8-06 | Notification center | `feature/p8-06-notification-center` | P6-06, P8-04 | ⏳ Chưa bắt đầu |
| P8-07 | Audit hardening và checkpoint | `feature/p8-07-audit-checkpoint` | P8-01..P8-06 | ⏳ Chưa bắt đầu |

---

### Giai đoạn 9 — Hoàn thiện, CI/CD và deployment
- **Mục tiêu**: Đưa hệ thống tới trạng thái sẵn sàng triển khai và vận hành.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P9-01 | Responsive và accessibility audit | `feature/p9-01-accessibility-responsive` | P8-07 | ⏳ Chưa bắt đầu |
| P9-02 | Security hardening | `feature/p9-02-security-hardening` | P8-07 | ⏳ Chưa bắt đầu |
| P9-03 | Performance và database indexes | `feature/p9-03-performance-indexes` | P8-07 | ⏳ Chưa bắt đầu |
| P9-04 | Complete regression suite | `feature/p9-04-regression-suite` | P9-01..P9-03 | ⏳ Chưa bắt đầu |
| P9-05 | CI workflow | `feature/p9-05-ci-workflow` | P9-04 | ⏳ Chưa bắt đầu |
| P9-06 | Production image và deployment | `feature/p9-06-production-deployment` | P9-05 | ⏳ Chưa bắt đầu |
| P9-07 | Backup, monitoring và final checkpoint | `feature/p9-07-final-checkpoint` | P9-06 | ⏳ Chưa bắt đầu |
