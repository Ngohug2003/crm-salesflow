# Giai đoạn 4 — Companies và Contacts

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Xây dựng hồ sơ khách hàng và quan hệ Company–Contact làm đích chuyển đổi Lead.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P4-01 | ✅ Company schema và domain | `feature/p4-01-company-domain` | P2-08 | Schema/model/factory/test Company với PostgreSQL BIGINT tự tăng, Data Scope và soft delete |
| P4-02 | ✅ Contact schema và domain | `feature/p4-02-contact-domain` | P4-01 | Schema/model/factory/test Contact và quan hệ với Company |
| P4-03 | ✅ Company CRUD | `feature/p4-03-company-crud` | P4-01, P2-04 | Dịch vụ, Repository, Policy và Livewire CRUD cho Company |
| P4-04 | ✅ Contact CRUD và quan hệ | `feature/p4-04-contact-crud` | P4-02, P2-04 | Dịch vụ, Repository, Policy và Livewire CRUD cho Contact |
| P4-05 | ✅ Duplicate handling | `feature/p4-05-customer-duplicates` | P4-03, P4-04 | Phát hiện trùng lặp khách hàng, cảnh báo và xử lý |
| P4-06 | ✅ Attachment và timeline foundation | `feature/p4-06-customer-files-timeline` | P4-03, P4-04 | Đính kèm tệp tin và mốc thời gian khách hàng |
| P4-07 | ✅ Customer authorization checkpoint | `feature/p4-07-customer-checkpoint` | P4-01..P4-06 | Kiểm thử phân quyền và checkpoint nghiệm thu Giai đoạn 4 |

---

### Nhật ký feature P4-01 — Company schema và domain

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_24_000001_create_companies_table.php` sử dụng khóa chính PostgreSQL `BIGINT` tự tăng (`id`).
- Khai báo các trường Data Scope (`owner_id`, `department_id`) liên kết tới `users` và `departments`.
- Khai báo các trường thông tin doanh nghiệp (`name`, `tax_code`, `website`, `email`, `phone`, `industry`, `company_size`, `annual_revenue`, `address`, `city`, `province`, `country`, `notes`), xóa mềm (`softDeletes`) và audit fields (`created_by`, `updated_by`).
- Tạo Eloquent Model `Company` hỗ trợ soft deletes, casting `annual_revenue` kiểu decimal, định nghĩa các quan hệ `owner`, `department`, `createdBy`, `updatedBy` cùng các local scopes `scopeActive`, `scopeSearch`.
- Tạo `CompanyFactory` hỗ trợ sinh dữ liệu giả lập có cấu trúc.
- Tạo `CompanyDomainTest` với 4 test cases kiểm thử tạo bản ghi, casting, soft deletes, quan hệ và tìm kiếm.

File chính:

- `database/migrations/2026_07_24_000001_create_companies_table.php`
- `app/Models/Company.php`
- `database/factories/CompanyFactory.php`
- `tests/Feature/CompanyDomainTest.php`

### Nhật ký feature P4-02 — Contact schema và domain

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_24_000002_create_contacts_table.php` sử dụng khóa chính PostgreSQL `BIGINT` tự tăng (`id`).
- Khai báo mối quan hệ tới `Company` qua `company_id` (nullable) và các trường Data Scope (`owner_id`, `department_id`) liên kết tới `users` và `departments`.
- Khai báo thông tin cá nhân (`first_name`, `last_name`, `full_name`, `email`, `phone`, `secondary_phone`, `job_title`, `department_name`, `birthday`, cờ `is_primary`, `address`, `city`, `province`, `country`, `notes`), xóa mềm (`softDeletes`) và audit fields.
- Tạo Eloquent Model `Contact` với casts `birthday` kiểu date và `is_primary` kiểu boolean, định nghĩa các quan hệ `company`, `owner`, `department`, `createdBy`, `updatedBy` cùng các local scopes `scopeActive`, `scopePrimary`, `scopeSearch`.
- Thêm quan hệ `contacts()` (`HasMany`) trong Model `Company`.
- Tạo `ContactFactory` hỗ trợ các factory states `primary`, `ownedBy` và `forCompany`.
- Tạo `ContactDomainTest` với 4 test cases kiểm thử tạo bản ghi, mối quan hệ `Contact <-> Company`, cờ `is_primary`, soft deletes và tìm kiếm.

File chính:

- `database/migrations/2026_07_24_000002_create_contacts_table.php`
- `app/Models/Contact.php`
- `app/Models/Company.php`
- `database/factories/ContactFactory.php`
- `tests/Feature/ContactDomainTest.php`

### Nhật ký feature P4-03 — Company CRUD

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo `CompanyFilterData` DTO đóng gói tham số tìm kiếm, ngành nghề, quy mô, owner_id, department_id, sorting.
- Định nghĩa `CompanyRepository` contract và `EloquentCompanyRepository` thực thi Data Scope (`all`, `department`, `owned`, `read-only`) ở backend cùng allowlist sorting (`name`, `created_at`, `annual_revenue`, `tax_code`).
- Tạo `CompanyPolicy` phân quyền `companies.view`, `companies.create`, `companies.update`, `companies.delete` và kiểm tra Data Scope.
- Tạo `CompanyManagementService` thực thi logic nghiệp vụ CRUD trong `DB::transaction()` và tự động ghi log kiểm toán qua `SystemAuditService`.
- Đăng ký Bind `CompanyRepository` ➔ `EloquentCompanyRepository` trong `RepositoryServiceProvider`.
- Tạo các Livewire Components & Blade views: `CompanyList` (`/companies`), `CompanyEditor` (`/companies/create`, `/companies/{id}/edit`), `CompanyDetail` (`/companies/{id}`).
- Khai báo các tuyến đường URL trong `routes/web.php` và thêm menu **Doanh nghiệp** vào Sidebar navigation.
- Viết 5 test cases trong `CompanyCrudTest` kiểm thử phân quyền 5 vai trò, Data Scope isolation, CRUD và audit logs.

File chính:

- `app/Data/CompanyFilterData.php`
- `app/Repositories/Contracts/CompanyRepository.php`
- `app/Repositories/EloquentCompanyRepository.php`
- `app/Policies/CompanyPolicy.php`
- `app/Services/CompanyManagementService.php`
- `app/Livewire/Companies/CompanyList.php`
- `app/Livewire/Companies/CompanyEditor.php`
- `app/Livewire/Companies/CompanyDetail.php`
- `resources/views/livewire/companies/company-list.blade.php`
- `resources/views/livewire/companies/company-editor.blade.php`
- `resources/views/livewire/companies/company-detail.blade.php`
- `routes/web.php`
- `resources/views/layouts/partials/_sidebar.blade.php`
- `tests/Feature/CompanyCrudTest.php`

### Nhật ký feature P4-04 — Contact CRUD và quan hệ

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo `ContactFilterData` DTO đóng gói tham số tìm kiếm, doanh nghiệp, cờ đại diện chính (isPrimary), owner_id, department_id, sorting.
- Định nghĩa `ContactRepository` contract và `EloquentContactRepository` thực thi Data Scope (`all`, `department`, `owned`, `read-only`) ở backend cùng allowlist sorting (`full_name`, `created_at`, `job_title`, `email`) và phương thức `resetPrimaryContactsExcept`.
- Tạo `ContactPolicy` phân quyền `contacts.view`, `contacts.create`, `contacts.update`, `contacts.delete` và kiểm tra Data Scope.
- Tạo `ContactManagementService` thực thi logic nghiệp vụ CRUD trong `DB::transaction()`, tự động xử lý cờ `is_primary` duy nhất cho mỗi Doanh nghiệp và ghi log kiểm toán qua `SystemAuditService`.
- Đăng ký Bind `ContactRepository` ➔ `EloquentContactRepository` trong `RepositoryServiceProvider`.
- Tạo các Livewire Components & Blade views dạng Full-width: `ContactList` (`/contacts`), `ContactEditor` (`/contacts/create`, `/contacts/{id}/edit`), `ContactDetail` (`/contacts/{id}`).
- Khai báo các tuyến đường URL trong `routes/web.php` và bật menu **Người liên hệ** chính thức trên Sidebar navigation.
- Viết 6 test cases trong `ContactCrudTest` kiểm thử phân quyền 5 vai trò, Data Scope isolation, CRUD, quan hệ Doanh nghiệp, cờ Đại diện chính duy nhất và audit logs.

File chính:

- `app/Data/ContactFilterData.php`
- `app/Repositories/Contracts/ContactRepository.php`
- `app/Repositories/EloquentContactRepository.php`
- `app/Policies/ContactPolicy.php`
- `app/Services/ContactManagementService.php`
- `app/Livewire/Contacts/ContactList.php`
- `app/Livewire/Contacts/ContactEditor.php`
- `app/Livewire/Contacts/ContactDetail.php`
- `resources/views/livewire/contacts/contact-list.blade.php`
- `resources/views/livewire/contacts/contact-editor.blade.php`
- `resources/views/livewire/contacts/contact-detail.blade.php`
- `routes/web.php`
- `resources/views/layouts/partials/_sidebar.blade.php`
- `tests/Feature/ContactCrudTest.php`

### Nhật ký feature P4-05 — Duplicate handling

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo `DuplicateCompanyException` và `DuplicateContactException` nạp sẵn danh sách ứng viên trùng và chữ ký mã hóa signature.
- Tạo `DuplicateCompanyService` phát hiện trùng lặp Doanh nghiệp theo Mã số thuế (tax_code), Tên công ty (name), Email hoặc Số điện thoại.
- Tạo `DuplicateContactService` phát hiện trùng lặp Người liên hệ theo Email, Số điện thoại (phone/secondary_phone), hoặc Họ tên + Doanh nghiệp.
- Cập nhật `CompanyManagementService` & `ContactManagementService` tích hợp duplicate guard tại backend: chặn tạo/sửa trùng khi chưa xác nhận signature & lý do ghi đè (min 10 ký tự), đồng thời lưu vết `duplicate_override` vào log kiểm toán.
- Cập nhật Livewire Editors (`CompanyEditor`, `ContactEditor`) & Blade views hiển thị Banner cảnh báo trùng lặp màu vàng nổi bật với danh sách bản ghi bị trùng (kèm link), ô nhập lý do và nút xác nhận lưu trùng.
- Viết `CustomerDuplicateTest` kiểm thử 2 test cases chính đạt 100% PASS (chặn trùng, xác nhận chữ ký, xác thực lý do >= 10 ký tự và ghi log audit).

File chính:

- `app/Exceptions/DuplicateCompanyException.php`
- `app/Exceptions/DuplicateContactException.php`
- `app/Services/DuplicateCompanyService.php`
- `app/Services/DuplicateContactService.php`
- `app/Services/CompanyManagementService.php`
- `app/Services/ContactManagementService.php`
- `app/Livewire/Companies/CompanyEditor.php`
- `app/Livewire/Contacts/ContactEditor.php`
- `resources/views/livewire/companies/company-editor.blade.php`
- `resources/views/livewire/contacts/contact-editor.blade.php`
- `tests/Feature/CustomerDuplicateTest.php`

### Nhật ký feature P4-06 — Attachment và timeline foundation

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo migration `2026_07_24_000003_create_attachments_table.php` sử dụng quan hệ Đa hình (`attachable_type`, `attachable_id`) với PostgreSQL `BIGINT` auto-increment `id`.
- Tạo Eloquent Model `Attachment` với hỗ trợ softDeletes và helper `humanSize()`. Thêm quan hệ `attachments(): MorphMany` trong Model `Company` và `Contact`.
- Tạo `CustomerAttachmentService` thực hiện đăng tải tệp tin bảo mật, kiểm tra đuôi tệp nguy hiểm (`.php`, `.exe`, `.sh`,...), xóa tệp và tự động phát sinh log audit qua `SystemAuditService`.
- Tạo `CustomerTimelineService` hợp nhất dữ liệu Audit Logs (`Spatie\Activitylog\Models\Activity`) và Tệp đính kèm (`Attachment`) thành một luồng Dòng thời gian hoạt động (Timeline Feed) xếp theo thứ tự thời gian giảm dần.
- Tạo các Livewire Components & Blade views: `CustomerAttachmentManager` (quản lý/tải lên/xóa/tải về tệp đính kèm) và `CustomerTimelineFeed` (hiển thị dòng thời gian).
- Nhúng cả 2 components vào trang chi tiết Doanh nghiệp (`CompanyDetail`) và Người liên hệ (`ContactDetail`).
- Viết `CustomerAttachmentAndTimelineTest` kiểm thử 4 test cases đạt 100% PASS (tải tệp hợp lệ, chặn tệp nguy hiểm, dòng thời gian hợp nhất và xóa tệp đính kèm).

File chính:

- `database/migrations/2026_07_24_000003_create_attachments_table.php`
- `app/Models/Attachment.php`
- `app/Models/Company.php`
- `app/Models/Contact.php`
- `app/Data/CustomerTimelineItemData.php`
- `app/Services/CustomerAttachmentService.php`
- `app/Services/CustomerTimelineService.php`
- `app/Livewire/Customers/CustomerAttachmentManager.php`
- `app/Livewire/Customers/CustomerTimelineFeed.php`
- `resources/views/livewire/customers/customer-attachment-manager.blade.php`
- `resources/views/livewire/customers/customer-timeline-feed.blade.php`
- `resources/views/livewire/companies/company-detail.blade.php`
- `resources/views/livewire/contacts/contact-detail.blade.php`
- `tests/Feature/CustomerAttachmentAndTimelineTest.php`

### Nhật ký feature P4-07 — Customer authorization checkpoint

Trạng thái: **hoàn tất triển khai, sẵn sàng nghiệm thu Giai đoạn 4**.

Đã triển khai:

- Tạo `CustomerAuthorizationCheckpointTest` kiểm thử ma trận phân quyền và Data Scope toàn diện 5 vai trò hệ thống (`super-admin`, `admin`, `sales-manager`, `sales`, `viewer`) trên mô-đun Companies & Contacts.
- Xác nhận phân quyền Data Scope:
  - Super Admin & Admin: Xem và thao tác toàn bộ Doanh nghiệp & Người liên hệ hệ thống.
  - Sales Manager: Chỉ xem và thao tác dữ liệu thuộc cùng Phòng ban (`department` scope). Bị chặn (404/403) khi truy cập ngoài phòng ban.
  - Sales Rep: Chỉ xem và thao tác dữ liệu do chính mình phụ trách (`owned` scope). Bị chặn (404/403) khi truy cập dữ liệu của đồng nghiệp khác.
  - Viewer: Quyền xem (`read-only`), bị chặn hoàn toàn (403) trên các thao tác tạo mới, sửa, xóa.
- Kiểm thử chuyển đổi cờ đại diện chính `is_primary` của Contact có thẩm quyền và kiểm soát dữ liệu liên quan.
- Chạy Quality Gates toàn mô-đun P4: **20/20 test cases PASS (117 assertions)**, Laravel Pint formatting clean, PHPStan Level 5 `0 errors`.

File chính:

- `tests/Feature/CustomerAuthorizationCheckpointTest.php`
- `docs/checkpoints/P4/PHASE_LOG.md`
- `PROJECT_PHASES.md`






