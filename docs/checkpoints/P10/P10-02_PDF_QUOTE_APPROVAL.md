# P10-02 — PDF Quote Builder & Discount Approval

## Trạng thái

Hoàn tất triển khai trên branch `feature/p10-02-pdf-quote-approval`, chờ kiểm thử thủ công.

## Requirement và quyết định

- Requirement: `REQ-PIPELINE`, `REQ-IO`, `REQ-NOTIFY`, `REQ-AUDIT`, `REQ-AUTH-NAV`, authorization/data scope, private file và queue idempotency.
- Quyết định: `DEC-001`, `DEC-002`, `DEC-003`, `DEC-004`, `DEC-007`, `DEC-008`, `DEC-011`.
- Không triển khai margin/product-group rule khi chưa có Product Catalog và cost data ở P11-01.
- Không triển khai customer public acceptance và chữ ký điện tử; public acceptance thuộc P10-05.

## Nghiệp vụ đã triển khai

- Tạo báo giá từ Opportunity, tự sao chép khách hàng, người liên hệ và line items.
- Tính line total, subtotal, chiết khấu, VAT và tổng thanh toán bằng `brick/math`, không dùng float trong nghiệp vụ tài chính.
- State machine: draft/rejected → pending approval hoặc auto-approved → approved → issued → sent.
- Ma trận mặc định:
  - `0–10%`: tự động duyệt.
  - `10.01–20%`: Sales Manager cùng phòng ban.
  - `20.01–100%`: Admin/Super Admin.
- Khóa row khi submit/approve/issue; mỗi quote version chỉ có một approval request.
- Chống người gửi tự phê duyệt và kiểm tra vai trò, data scope ở backend.
- Lưu approval request/action, lý do từ chối, audit event và Request ID.
- Phát hành snapshot bất biến, queue render PDF và chống tạo tài liệu trùng khi retry.
- PDF private có signed URL 15 phút, hash SHA-256, metadata dung lượng/trạng thái.
- PDF tiếng Việt có branding, logo private, thông tin khách hàng, line items, VAT, tổng tiền bằng số/chữ và điều khoản.
- Notification database cho người duyệt, người lập và lúc PDF sẵn sàng.
- Notification realtime qua private user channel, polling 15 giây làm fallback khi Reverb reconnect.
- Màn hộp thư phê duyệt, cấu hình branding/ma trận và luồng Quote trong Opportunity.
- Admin/Sales Manager có thể duyệt hoặc từ chối ngay trên dòng báo giá đang chờ; Admin không hiển thị action “Gửi duyệt” và “Đánh dấu đã gửi” dành cho Sales.

## Schema

- Mở rộng `quotes`: version, discount_percent, workflow timestamps, rejection reason và issued snapshot.
- Migration backfill tính lại `discount_percent` cho báo giá cũ bằng decimal để không tự duyệt sai.
- Tạo:
  - `quote_approval_rules`
  - `quote_approval_requests`
  - `quote_approval_actions`
  - `quote_versions`
  - `quote_documents`
  - `quote_branding_settings`
- Tất cả khóa chính/khóa ngoại dùng PostgreSQL auto-incrementing `BIGINT`.
- Index `quotes(status, created_at)` phục vụ danh sách workflow.
- Index approval inbox theo `status`, `required_role`, `submitted_at`.
- Unique `(quote_id, quote_version)` chống submit trùng; unique `quote_version_id` chống PDF trùng.

## Kiến trúc

```text
Livewire → Quote Service → Quote Repository → Eloquent Model
                          → Audit/Notification

Issue → QuoteVersion + QuoteDocument → RenderQuotePdfJob → private storage
Signed route → QuoteController → QuotePolicy → private download
```

- Repository chịu trách nhiệm row lock, chọn rule và query approval inbox.
- Service điều phối pricing, approval, document, settings và transaction.
- Livewire chỉ quản lý form/modal/feedback.
- Policy cưỡng chế view/create/update/submit/approve/issue/send/download/settings.
- Route approval/settings có `can` middleware khớp trực tiếp với permission catalog trong `config/crm.php`; Livewire vẫn re-authorize use case.
- Đã quét toàn bộ authenticated route và sidebar: route list/create/settings có `can:` middleware, route record/cá nhân được khai báo backend boundary trong `crm.rbac.route_access_exceptions`.
- Ma trận phân quyền đọc duy nhất từ catalog `config/crm.php`; loại bỏ các quyền chết `users.manage`, `roles.view`, `roles.assign`, `departments.manage`, `imports.manage`, `exports.create`.
- Bổ sung authorization cho mọi action hồ sơ nhân viên, session của tài khoản khác, download và modal lịch sử import/export.

## Package

- Thêm `barryvdh/laravel-dompdf:^3.1`.
- Lý do: render template Blade dạng tài liệu A4, tương thích Laravel 12/PHP 8.5, không cần Chromium.
- Remote asset không được bật; logo được đọc từ private storage và nhúng data URI.

## File chính

- Domain: `app/Enums/QuoteStatus.php`, `app/Enums/QuoteApprovalStatus.php`, `app/Data/QuoteTotals.php`.
- Models: `app/Models/Quote*.php`.
- Services: `app/Services/QuoteService.php`, `QuotePricingService.php`, `QuoteApprovalService.php`, `QuoteDocumentService.php`, `QuoteSettingsService.php`.
- Repository: `app/Repositories/Contracts/QuoteRepository.php`, `app/Repositories/EloquentQuoteRepository.php`.
- Async: `app/Jobs/RenderQuotePdfJob.php`, `app/Notifications/QuoteWorkflowNotification.php`.
- UI: `app/Livewire/Quotes`, `app/Livewire/Opportunities/QuoteManager.php` và view tương ứng.
- PDF/download: `resources/views/pdf/quote.blade.php`, `app/Http/Controllers/QuoteController.php`.
- Schema/seed: migration P10-02 và `database/seeders/QuoteApprovalSeeder.php`.
- Test: `tests/Feature/P1002QuoteApprovalTest.php`.
- Test route/sidebar/RBAC: `tests/Feature/RouteNavigationAuthorizationTest.php`.

## Commands và kết quả

```bash
docker compose exec app composer install --no-interaction
# Cài barryvdh/laravel-dompdf 3.1.2 và dependency thành công.

docker compose exec app php artisan migrate --force
# Migration P10-02 thành công.

docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force
docker compose exec app php artisan db:seed --class=QuoteApprovalSeeder --force
# Permission, approval matrix và branding mặc định thành công.

docker compose exec app php artisan test tests/Feature/P1002QuoteApprovalTest.php
# 6 tests đạt, 44 assertions.

docker compose exec app php artisan test tests/Feature/RouteNavigationAuthorizationTest.php
# 5 tests đạt, 59 assertions.

docker compose exec app ./vendor/bin/phpstan analyse --no-progress <các file route/RBAC vừa sửa>
# Không có lỗi.

docker compose exec app ./vendor/bin/pint --test
# 520 files đạt.

docker compose exec app ./vendor/bin/phpstan analyse --no-progress
# Không có lỗi.

docker compose exec app composer audit --no-interaction
# Không có security advisory.

docker compose exec vite npm run build
# Vite build thành công, 733 modules.

docker compose exec app php artisan view:cache
# Blade compile thành công.

docker compose restart horizon
# Nạp autoloader mới sau khi cài package PDF.

docker compose exec app php artisan queue:retry <failed-job-uuid>
# Job local đã retry thành công; PDF private trạng thái ready, có size và SHA-256.
```

## Kiểm thử thủ công

1. Chạy migration và hai seeder nêu trên.
2. Đăng nhập Sales, mở một Opportunity có line items và tạo báo giá.
3. Kiểm tra chiết khấu `10%` tự động duyệt.
4. Kiểm tra `10.01–20%` xuất hiện trong “Duyệt báo giá” của Sales Manager cùng phòng ban.
5. Kiểm tra `>20%` chỉ Admin/Super Admin duyệt được.
6. Người tạo thử tự duyệt phải bị chặn.
7. Từ chối không nhập lý do phải bị chặn; lý do phải hiện lại ở Opportunity.
8. Sau duyệt, bấm “Phát hành PDF”; chờ Horizon xử lý rồi tải PDF.
9. Kiểm tra logo, tiếng Việt, khách hàng, VAT, tổng số/chữ và điều khoản.
10. Sửa hoặc để URL tải hết hạn phải nhận 403; user ngoài data scope không tải được.
11. Kiểm tra desktop/mobile/dark mode, modal loading và empty state.
12. Kiểm tra Notification Center và Audit Log có đúng actor, action, version, Request ID.

## Giới hạn còn lại

- Public quote link và phản hồi trực tiếp từ khách hàng thuộc P10-05.
- Margin/product group approval cần dữ liệu cost và Product Catalog ở P11-01.
- Chữ ký số pháp lý, e-invoice và trình thiết kế PDF kéo-thả nằm ngoài phạm vi.
