# P2-X04 — Cấu hình quyền theo Role

## Trạng thái

Hoàn tất triển khai trên branch `feature/p2-x04-role-permission-management`, chờ kiểm thử thủ công.

## Mục tiêu và requirement

- Module: `MOD-RBAC`.
- Requirement: `REQ-AUTH-NAV`, `REQ-RBAC-MANAGE`, `REQ-AUDIT`.
- Quyết định: `DEC-001`, `DEC-005`, `DEC-011`, `DEC-012`.
- Permission key và default assignment vẫn thuộc `config/crm.php`; assignment đang có hiệu lực thuộc database.

## Đã triển khai

- Chuyển Permission Matrix read-only thành editor theo từng role.
- Chọn role, tìm/lọc Permission theo phân hệ, bật/tắt quyền, trạng thái chưa lưu và modal xác nhận save/discard/reset.
- Role chỉ sửa được role có thứ bậc thấp hơn; `super-admin` bất biến.
- Actor không phải Super Admin không thể thay đổi Permission quản trị được bảo vệ hoặc cấp quyền mà actor không sở hữu.
- Permission `roles.manage` bảo vệ route, sidebar, mount và mọi Livewire action.
- Cập nhật role trong transaction, xóa cache Spatie Permission và ghi audit old/new cùng added/removed permissions.
- Bảng `role_permission_customizations` đánh dấu role đã chỉnh từ UI; seeder không ghi đè các role này.
- Khôi phục mặc định đồng bộ lại config và xóa customization marker.
- Route/sidebar/Policy phản ánh thay đổi ngay, không cần restart Docker.
- Trang hướng dẫn **Vai trò & quyền** đọc assignment thực tế trong DB, không hiển thị lại bộ mặc định đã lỗi thời.

## Schema

- `role_permission_customizations`
  - `id`: PostgreSQL auto-incrementing `BIGINT`.
  - `role_id`: unique FK tới `roles`, cascade delete.
  - `customized_by`: nullable FK tới `users`, null khi user bị xóa.
  - timestamps.

## File chính

- `app/Livewire/Roles/PermissionMatrixView.php`
- `app/Services/Rbac/PermissionMatrixService.php`
- `app/Services/Rbac/RolePermissionManagementService.php`
- `app/Services/RoleGuideService.php`
- `app/Models/RolePermissionCustomization.php`
- `resources/views/livewire/roles/permission-matrix-view.blade.php`
- `database/migrations/2026_08_12_000001_create_role_permission_customizations_table.php`
- `database/seeders/RolePermissionSeeder.php`
- `tests/Feature/P2X04RolePermissionManagementTest.php`

## Commands và kết quả

```bash
docker compose exec app php artisan migrate --force
# Migration role_permission_customizations thành công.

docker compose exec app php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
# Catalog và permission roles.manage được đồng bộ thành công.

docker compose exec app php artisan test tests/Feature/P2X04RolePermissionManagementTest.php
# 6 tests đạt, 29 assertions.

docker compose exec app php artisan test tests/Feature/RouteNavigationAuthorizationTest.php
# 5 tests đạt, 59 assertions.

docker compose exec app ./vendor/bin/phpstan analyse --no-progress <các file P2-X04>
# Không có lỗi.

docker compose exec app php artisan view:cache
# Blade compile thành công.
```

## Kiểm thử thủ công

1. Đăng nhập Admin, mở **Quản trị → Ma trận phân quyền**.
2. Chọn role Sales, tắt `companies.update`, bấm **Lưu thay đổi** và xác nhận.
3. Đăng nhập Sales: menu **Hợp nhất trùng lặp** và nút sửa Company phải biến mất.
4. Sales truy cập trực tiếp `/customers/merge` hoặc URL edit Company phải nhận `403`.
5. Cấp lại `companies.update`; kiểm tra menu và quyền edit có hiệu lực ngay.
6. Admin thử chỉnh role Admin/Super Admin hoặc `settings.manage`: UI khóa và backend từ chối.
7. Chuyển role khi còn thay đổi chưa lưu: modal discard phải xuất hiện.
8. Bấm **Khôi phục mặc định** và xác nhận bộ quyền quay lại cấu hình source.
9. Chạy RolePermissionSeeder; role đang customized không bị ghi đè.
10. Kiểm tra Audit Log có actor, role, old/new, added/removed permissions và Request ID.
11. Kiểm tra desktop/mobile, dark mode, keyboard focus và loading state.

## Ngoài phạm vi

- Tạo/xóa/đổi tên Permission từ UI.
- Tạo custom role và cấu hình data scope động.
- Approval workflow nhiều người cho thay đổi quyền.
