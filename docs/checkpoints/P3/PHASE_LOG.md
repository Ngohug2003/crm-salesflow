# Giai đoạn 3 — Leads

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: hoàn thiện vòng đời Lead từ tiếp nhận đến chuyển đổi; import/export để lại P8.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P3-01 | ✅ Lead sources và tags | `feature/p3-01-lead-taxonomy` | P2-08 | Schema/model/factory/seed nguồn Lead và Tag, chuẩn bị contract many-to-many |
| P3-02 | ✅ Lead schema và domain | `feature/p3-02-lead-domain` | P3-01 | BIGINT tự tăng, owner, department, source, contact fields, indexes, factory và pivot `lead_tag` |
| P3-03 | ✅ Repository và bộ lọc Lead | `feature/p3-03-lead-query-filters` | P3-02 | Search, filter, sort, pagination và reusable data-scope query |
| P3-04 | ✅ Lead policy và visibility | `feature/p3-04-lead-authorization` | P2-04, P3-03 | Policy CRUD/assign/convert/restore đúng permission matrix |
| P3-05 | ✅ Danh sách Lead | `feature/p3-05-lead-list` | P3-03, P3-04 | Livewire table responsive, URL filters, bulk selection foundation và empty states |
| P3-06 | ✅ Form và chi tiết Lead | `feature/p3-06-lead-form-detail` | P3-05 | Create/edit/detail, validation, source/tags/owner và audit cơ bản |
| P3-07 | ✅ Assignment và status history | `feature/p3-07-lead-assignment-status` | P3-06 | Gán owner, chuyển trạng thái hợp lệ, lịch sử và event |
| P3-08 | ✅ Duplicate, soft delete và restore | `feature/p3-08-lead-duplicate-delete` | P3-06 | Phát hiện email/phone trùng, cảnh báo/merge decision, trash/restore |
| P3-09 | Conversion eligibility và contract | `feature/p3-09-conversion-contract` | P3-07, P3-08 | Rule đủ điều kiện, DTO/action contract, chống convert lặp và test contract; chưa tạo Opportunity |
| P3-10 | Lead test và checkpoint | `feature/p3-10-lead-checkpoint` | P3-01..P3-09 | Feature/policy/transaction tests và checklist vòng đời Lead |

### Nhật ký feature P3-01 — Lead sources và tags

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo hai bảng `lead_sources` và `tags` dùng khóa chính PostgreSQL `BIGINT` tự tăng.
- `lead_sources.code` và `tags.slug` là khóa nghiệp vụ duy nhất; cả hai bảng có màu hiển thị, trạng thái hoạt động và thứ tự sắp xếp.
- Model `LeadSource` và `Tag` có typed casts, scope `active()` và `ordered()` để dùng lại trong form/filter Lead.
- Factory hỗ trợ trạng thái active/inactive và dữ liệu độc lập cho test.
- `LeadTaxonomySeeder` idempotent tạo 8 nguồn Lead và 6 tag tiếng Việt; chạy lại sẽ sửa dữ liệu chuẩn mà không tạo bản ghi trùng.
- `DatabaseSeeder` gọi taxonomy seeder để môi trường local có dữ liệu ngay sau `migrate --seed`.
- Chưa tạo bảng pivot `lead_tag` trong P3-01 vì bảng `leads` chưa tồn tại. P3-02 sẽ tạo `leads`, pivot cùng foreign key và quan hệ Eloquent hai chiều trong một migration chain hợp lệ.

File chính:

- `database/migrations/2026_07_22_210000_create_lead_taxonomies_tables.php`
- `app/Models/LeadSource.php`, `app/Models/Tag.php`
- `database/factories/LeadSourceFactory.php`, `database/factories/TagFactory.php`
- `database/seeders/LeadTaxonomySeeder.php`, `database/seeders/DatabaseSeeder.php`
- `tests/Feature/LeadTaxonomyDomainTest.php`
- `docs/database.md`, `docs/architecture.md`

### Nhật ký feature P3-02 — Lead schema và domain

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo bảng `leads` dùng khóa chính `BIGINT` tự tăng và Soft Delete.
- Lead liên kết nullable với nguồn, người phụ trách, phòng ban, người tạo và người cập nhật; xóa kỹ thuật bản ghi liên quan sẽ đặt foreign key về `NULL`, không làm mất Lead.
- Dùng `owner_id` thay cho `assigned_to` để tương thích trực tiếp với `DataScopeService` và repository của các module CRM.
- Lưu đầy đủ thông tin liên hệ: họ tên, email, điện thoại chính/phụ, công ty, chức vụ, website và địa chỉ.
- Có giá trị dự kiến `DECIMAL(15,2)`, ghi chú, thời điểm chuyển đổi và metadata người tạo/cập nhật.
- `LeadStatus` có 6 trạng thái `new/contacted/qualified/unqualified/converted/lost`; `LeadPriority` có 4 mức `low/medium/high/urgent`, kèm label tiếng Việt.
- Tạo pivot `lead_tag` có timestamps, unique cặp Lead–Tag và cascade khi force delete; Soft Delete Lead vẫn giữ tag để khôi phục nguyên trạng.
- Hoàn thiện quan hệ Eloquent hai chiều trên Lead, Source, Tag, User và Department.
- `LeadFactory` hỗ trợ owner/phòng ban đồng nhất, actor tạo/cập nhật, status, priority và converted state.
- Tạo 11 index PostgreSQL phục vụ status, owner, department, source, priority, email, phone, thời gian tạo và audit users.

File chính:

- `database/migrations/2026_07_22_220000_create_leads_table.php`
- `app/Models/Lead.php`
- `app/Enums/LeadStatus.php`, `app/Enums/LeadPriority.php`
- `database/factories/LeadFactory.php`
- `app/Models/LeadSource.php`, `app/Models/Tag.php`, `app/Models/User.php`, `app/Models/Department.php`
- `tests/Feature/LeadDomainTest.php`
- `docs/database.md`, `docs/architecture.md`

### Nhật ký feature P3-03 — Repository và bộ lọc Lead

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo `LeadFilterData` readonly DTO dùng Enum cho status/priority, `CarbonImmutable` cho khoảng ngày và typed ID cho source/tag/owner/department.
- Tạo `LeadRepository` contract với base query theo scope, filtered query tái sử dụng, pagination và `findVisibleOrFail`.
- `EloquentLeadRepository` luôn gọi `DataScopeService::apply()` với `owner_id` và `department_id` trước khi search/filter/sort.
- Search không phân biệt hoa thường trên họ tên, email, điện thoại chính/phụ và công ty.
- Có thể kết hợp status, priority, source, tag, owner, department và khoảng ngày tạo trong một query.
- Filter tag dùng `whereHas` nên một Lead có nhiều tag vẫn chỉ xuất hiện một lần.
- Sort chỉ cho phép `created_at`, `full_name`, `status`, `priority`, `estimated_value`; input ngoài allowlist fallback về ngày tạo giảm dần.
- Mọi sort thêm `leads.id` làm khóa phụ để thứ tự phân trang ổn định.
- `perPage` được giới hạn từ 1 đến 100.
- List và find eager-load source, owner, department, tags để tránh N+1 khi tầng UI đọc quan hệ.
- Soft-deleted Lead bị loại khỏi query mặc định.
- Binding repository được đăng ký tập trung trong `RepositoryServiceProvider`.

File chính:

- `app/Data/LeadFilterData.php`
- `app/Repositories/Contracts/LeadRepository.php`
- `app/Repositories/EloquentLeadRepository.php`
- `app/Providers/RepositoryServiceProvider.php`
- `tests/Feature/LeadRepositoryTest.php`
- `docs/architecture.md`, `docs/permissions.md`

### Nhật ký feature P3-04 — Lead policy và visibility

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo `LeadPolicy` và đăng ký tường minh với Laravel Gate trong `AuthServiceProvider`.
- `viewAny` yêu cầu `leads.view`; `view` kết hợp permission này với data scope của bản ghi.
- `create` yêu cầu `leads.create` và scope có quyền ghi.
- `update`, `delete`, `assign`, `convert` lần lượt yêu cầu permission tương ứng, scope ghi và Lead nằm trong phạm vi actor.
- `restore` dùng `leads.delete` theo catalog quyền gốc và vẫn kiểm tra owner/department lưu trên Lead đã soft delete.
- `forceDelete` bị Policy từ chối; chỉ Super Admin được Global Gate bypass.
- Super Admin không cần direct permission; Admin quản lý toàn bộ Lead nhưng không force delete.
- Sales Manager quản lý trong phòng ban. `leads.view-all` và `leads.update-all` không cho phép vượt khỏi data scope phòng ban.
- Sales chỉ thao tác Lead có `owner_id` là chính mình, có thể convert nhưng không có quyền assign.
- Viewer xem được dữ liệu theo read-only scope nhưng mọi mutation bị chặn kể cả khi được gán nhầm direct write permission.
- User không có role/permission hợp lệ bị từ chối tất cả Lead abilities.
- `DataScopeService::allows()` chấp nhận owner nullable; Lead chưa phân công không thể lọt vào scope `owned`.

File chính:

- `app/Policies/LeadPolicy.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Services/Authorization/DataScopeService.php`
- `tests/Feature/LeadPolicyTest.php`
- `docs/permissions.md`, `docs/architecture.md`

### Nhật ký feature P3-05 — Danh sách Lead

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Thêm route `GET /leads`, `LeadController` và navigation có điều kiện theo `LeadPolicy::viewAny`.
- Tạo `LeadDirectoryService` làm lớp điều phối giữa Livewire và repository; không đặt truy vấn nghiệp vụ trực tiếp trong component/view.
- Tạo Livewire `LeadList` với tìm kiếm, trạng thái, ưu tiên, nguồn, tag, owner, phòng ban, khoảng ngày, sắp xếp và số dòng mỗi trang.
- Đồng bộ toàn bộ filter/sort/pagination vào URL để có thể tải lại hoặc chia sẻ đúng trạng thái danh sách; giá trị URL không hợp lệ được chuẩn hóa theo allowlist.
- Dữ liệu, tùy chọn owner và phòng ban đều được giới hạn bằng data scope của người đang đăng nhập.
- Hiển thị bảng trên desktop và card trên mobile, có loading indicator, tổng số Lead trong phạm vi và hai empty state riêng biệt.
- Thêm nền tảng bulk selection cho trang hiện tại; lựa chọn bị xóa khi đổi trang hoặc đổi filter và ID ngoài phạm vi trang bị loại bỏ ở backend.
- Tạo `DemoLeadSeeder` idempotent gồm 30 Lead, phân bổ 18 Lead cho Sales và 12 Lead cho Marketing, phủ đủ trạng thái/ưu tiên và gán 2 tag mỗi Lead.

File chính:

- `app/Http/Controllers/LeadController.php`
- `app/Livewire/Leads/LeadList.php`
- `app/Services/LeadDirectoryService.php`
- `resources/views/leads/index.blade.php`
- `resources/views/livewire/leads/lead-list.blade.php`
- `database/seeders/DemoLeadSeeder.php`
- `tests/Feature/LeadListTest.php`
- `tests/Feature/DemoLeadSeederTest.php`

### Nhật ký feature P3-06 — Form và chi tiết Lead

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Thêm ba route `leads.create`, `leads.show`, `leads.edit`; Controller tải Lead qua scoped repository rồi kiểm tra `LeadPolicy` trước khi render.
- Tạo Livewire `LeadEditor` dùng chung cho create/edit và `LeadForm` riêng để chuẩn hóa, validate dữ liệu phía server.
- Form hỗ trợ thông tin liên hệ, công ty, địa chỉ, nguồn, ưu tiên, giá trị dự kiến, ghi chú, nhiều tag và người phụ trách.
- Email được trim/lowercase; website chỉ nhận HTTP/HTTPS; độ dài khớp schema; source/tag/owner phải tồn tại và đang hoạt động; tối đa 20 tag, không trùng ID.
- Tạo `LeadManagementService` điều phối Policy, data scope, assignment, transaction, audit và repository write; Livewire/Blade không chứa truy vấn ghi dữ liệu.
- Sales tạo Lead được tự gán cho chính mình và không thể gửi owner khác qua request giả mạo.
- Sales Manager chỉ thấy/chọn owner trong phòng ban; Admin/Super Admin có thể chọn owner toàn hệ thống. `department_id` luôn suy ra từ owner, hoặc giữ phòng ban actor khi Manager tạo Lead chưa phân công.
- Đổi owner trên Lead hiện có yêu cầu `leads.assign`; cập nhật thông thường vẫn yêu cầu `leads.update` và Lead nằm trong data scope.
- Trạng thái Lead mới luôn là `new`; P3-06 không cho sửa trạng thái để tránh bỏ qua transition/history sẽ triển khai ở P3-07.
- Repository bổ sung create/update/sync tag và eager-load người tạo/người cập nhật cho trang chi tiết.
- Mỗi lần tạo/cập nhật có thay đổi đều ghi `activity_log` module `leads`, actor, subject và snapshot old/new; audit được tạo trong cùng database transaction.
- Trang chi tiết hiển thị đầy đủ liên hệ, địa chỉ, giá trị, source/tag, owner/phòng ban và thời gian theo múi giờ Việt Nam.
- Danh sách Lead có nút tạo/xem/sửa theo Policy; Viewer chỉ thấy nút xem.

File chính:

- `app/Http/Controllers/LeadController.php`
- `app/Livewire/Forms/LeadForm.php`
- `app/Livewire/Leads/LeadEditor.php`
- `app/Services/LeadManagementService.php`
- `app/Repositories/Contracts/LeadRepository.php`
- `app/Repositories/EloquentLeadRepository.php`
- `resources/views/livewire/leads/lead-editor.blade.php`
- `resources/views/leads/create.blade.php`, `edit.blade.php`, `show.blade.php`
- `tests/Feature/LeadFormDetailTest.php`

### Nhật ký feature P3-07 — Assignment và status history

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo bảng `lead_assignment_histories` lưu owner/phòng ban cũ và mới, actor, lý do và timestamps.
- Tạo bảng `lead_status_histories` lưu trạng thái cũ/mới, actor, lý do và timestamps.
- Hai bảng dùng BIGINT tự tăng, foreign key phù hợp, cascade khi Lead bị force delete và tổng cộng 7 index phục vụ timeline/audit query.
- Thêm model/relationship hai chiều từ Lead tới assignment/status history.
- Tạo `LeadAssignmentService`: khóa bản ghi bằng `SELECT ... FOR UPDATE`, kiểm tra `LeadPolicy::assign`, giới hạn owner đang hoạt động theo data scope, đồng bộ phòng ban, ghi history và audit trong cùng transaction.
- Tạo `LeadStatusTransitionService`: khóa Lead, kiểm tra `LeadPolicy::update`, áp dụng transition matrix allowlist và ghi status/history/audit nguyên tử.
- Trạng thái `converted` là terminal và không thể chọn thủ công; chỉ integration conversion sau này được đặt trạng thái này.
- Chuyển sang `unqualified` hoặc `lost` bắt buộc nhập lý do. Các lý do khác không bắt buộc nhưng tối đa 500 ký tự.
- Không tạo history/event khi gán lại đúng owner/phòng ban hiện tại; request bị trả validation error rõ ràng.
- Form nhận biết owner hiện tại theo thời gian thực: nút lưu bị vô hiệu hóa cho đến khi chọn owner khác và nhãn giải thích rõ lý do chỉ áp dụng cho lần phân công mới; lịch sử cũ không được chỉnh sửa.
- Thêm `LeadAssigned` và `LeadStatusChanged`, đều implement `ShouldDispatchAfterCommit` để consumer không thấy dữ liệu chưa commit.
- Khi tạo Lead mới, hệ thống ghi status history `Khởi tạo → Mới`; nếu có owner/phòng ban ban đầu thì ghi thêm assignment history.
- Khóa đường đổi owner trong form edit thông tin chung; request giả mạo bị backend từ chối và phải dùng workflow service.
- Tạo `LeadWorkflow` Livewire trên trang chi tiết với form phân công, form chuyển trạng thái và timeline hợp nhất mới nhất trước.
- Sales Manager chỉ thấy owner cùng phòng ban; Sales không có form phân công nhưng được chuyển trạng thái Lead sở hữu; Viewer chỉ xem timeline.
- Timeline hiển thị actor, lý do và thời gian theo `Asia/Ho_Chi_Minh`.

File chính:

- `database/migrations/2026_07_22_230000_create_lead_workflow_histories_tables.php`
- `app/Models/LeadAssignmentHistory.php`, `LeadStatusHistory.php`
- `app/Services/LeadAssignmentService.php`
- `app/Services/LeadStatusTransitionService.php`
- `app/Repositories/Contracts/LeadWorkflowRepository.php`
- `app/Repositories/EloquentLeadWorkflowRepository.php`
- `app/Events/LeadAssigned.php`, `LeadStatusChanged.php`
- `app/Livewire/Leads/LeadWorkflow.php`
- `resources/views/livewire/leads/lead-workflow.blade.php`
- `tests/Feature/LeadWorkflowTest.php`

### Nhật ký feature P3-08 — Duplicate, soft delete và restore

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Thêm `email_normalized`, `phone_normalized`, `secondary_phone_normalized` cùng 3 index vào bảng `leads`.
- Migration backfill dữ liệu cũ; PostgreSQL local hiện có đủ 30/30 email và 30/30 số điện thoại demo đã chuẩn hóa.
- `LeadContactNormalizer` chuyển email về lowercase/trim và số Việt Nam `+84`, `0084`, dấu cách/gạch/chấm về cùng dạng `0xxxxxxxxx`.
- Model `Lead` tự cập nhật normalized contact ở sự kiện `saving`, bảo đảm form, factory, seeder và import sau này dùng chung quy tắc.
- `DuplicateLeadService` tìm theo email hoặc cả số chính/số phụ, gồm Lead active và soft-deleted, loại trừ Lead đang edit.
- Duplicate query luôn áp dụng data scope trước khi trả candidate; không tiết lộ Lead ngoài phạm vi người dùng.
- Form create/edit chặn lưu lần đầu khi có candidate và hiển thị modal: trường trùng, owner, phòng ban, trạng thái, active/trash state.
- Người dùng có thể mở Lead hiện có, quay lại chỉnh sửa hoặc xác nhận `Vẫn lưu riêng`; P3-08 không tự động merge/ghi đè dữ liệu.
- Xác nhận lưu riêng gắn với hash của email/số điện thoại hiện tại. Thay contact sau cảnh báo bắt buộc chạy duplicate check lại.
- Thêm `LeadPolicy::viewTrash`, route `/leads/trash`, Livewire `LeadTrash`, tìm kiếm, phân trang và scoped restore.
- Thêm nút `Đưa vào thùng rác` trên chi tiết Lead, modal xác nhận và lý do tối đa 500 ký tự.
- `LeadLifecycleService` soft-delete/restore trong transaction có row lock, Policy và audit `deleted`/`restored`.
- Soft delete giữ nguyên tag, assignment history, status history và activity log; danh sách chính tự động loại Lead đã xóa.
- Restore giữ lại phòng ban/lịch sử. Nếu owner đã bị khóa, hệ thống bỏ assignment, ghi thêm assignment history và dispatch `LeadAssigned` sau commit.
- Viewer không được mở thùng rác hoặc mutation; Manager/Sales chỉ thấy và khôi phục Lead đúng data scope.

File chính:

- `database/migrations/2026_07_22_231000_add_normalized_contacts_to_leads_table.php`
- `app/Support/LeadContactNormalizer.php`
- `app/Services/DuplicateLeadService.php`
- `app/Services/LeadLifecycleService.php`
- `app/Livewire/Leads/LeadEditor.php`
- `app/Livewire/Leads/LeadLifecycle.php`
- `app/Livewire/Leads/LeadTrash.php`
- `resources/views/livewire/leads/lead-lifecycle.blade.php`
- `resources/views/livewire/leads/lead-trash.blade.php`
- `tests/Feature/LeadDuplicateLifecycleTest.php`
