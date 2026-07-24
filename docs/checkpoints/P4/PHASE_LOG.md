# Giai đoạn 4 — Companies và Contacts

## Kế hoạch và Nhật ký triển khai chi tiết

Mục tiêu: Xây dựng hồ sơ khách hàng và quan hệ Company–Contact làm đích chuyển đổi Lead.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Trạng thái |
|---|---|---|---|---|
| P4-01 | ✅ Company schema và domain | `feature/p4-01-company-domain` | P2-08 | Schema/model/factory/test Company với PostgreSQL BIGINT tự tăng, Data Scope và soft delete |
| P4-02 | ✅ Contact schema và domain | `feature/p4-02-contact-domain` | P4-01 | Schema/model/factory/test Contact và quan hệ với Company |
| P4-03 | Company CRUD | `feature/p4-03-company-crud` | P4-01, P2-04 | Dịch vụ, Repository, Policy và Livewire CRUD cho Company |
| P4-04 | Contact CRUD và quan hệ | `feature/p4-04-contact-crud` | P4-02, P2-04 | Dịch vụ, Repository, Policy và Livewire CRUD cho Contact |
| P4-05 | Duplicate handling | `feature/p4-05-customer-duplicates` | P4-03, P4-04 | Phát hiện trùng lặp khách hàng, cảnh báo và xử lý |
| P4-06 | Attachment và timeline foundation | `feature/p4-06-customer-files-timeline` | P4-03, P4-04 | Đính kèm tệp tin và mốc thời gian khách hàng |
| P4-07 | Customer authorization checkpoint | `feature/p4-07-customer-checkpoint` | P4-01..P4-06 | Kiểm thử phân quyền và checkpoint nghiệm thu Giai đoạn 4 |

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

