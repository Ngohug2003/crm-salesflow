# P3-10 Lead checkpoint & Acceptance guide

Tài liệu này dùng để nghiệm thu Giai đoạn 3 (Leads Lifecycle) trước khi chuyển sang Phase 4 (Companies & Contacts). Mật khẩu chung của các tài khoản demo local là `SalesFlow@123`.

## Tài khoản và kết quả mong đợi

| Role | Email | Quyền Lead List | Quyền Sửa/Tạo | Workflow Assign | Workflow Status | Trash / Restore | Conversion Check |
|---|---|---|---|---|---|---|---|
| Super Admin | `admin@salesflow.test` | Toàn bộ | Có | Toàn hệ thống | Được phép | Được phép | Đủ điều kiện |
| Admin IT | `it.admin@salesflow.test` | Toàn bộ | Có | Toàn hệ thống | Được phép | Được phép | Đủ điều kiện |
| Sales Manager | `demo03@salesflow.test` | Cấp phòng ban (SALES) | Có (trong scope) | Trong phòng ban | Được phép | Trong phòng ban | Đủ điều kiện |
| Sales Person | `demo04@salesflow.test` | Sở hữu cá nhân (owned) | Tự gán chính mình | Bị chặn | Được phép | Sở hữu cá nhân | Đủ điều kiện |
| Viewer | `demo12@salesflow.test` | Chỉ đọc (read-only) | Bị chặn (403) | Bị chặn (403) | Bị chặn (403) | Bị chặn (403) | Bị chặn (403) |

---

## Chuẩn bị dữ liệu

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed --force
docker compose ps
```

Seeder là idempotent. `DemoLeadSeeder` khởi tạo 30 Lead demo (18 thuộc SALES, 12 thuộc MARKETING).

---

## Kiểm thử tự động

Chạy bộ test checkpoint riêng của Giai đoạn 3:

```bash
docker compose exec app php artisan test tests/Feature/LeadLifecycleCheckpointTest.php
```

Chạy toàn bộ test suite dự án và các cổng kiểm soát chất lượng (Quality Gates):

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose ps
```

---

## Kịch bản kiểm thử thủ công

1. **Kiểm thử Data Scope trên Danh sách Lead (`/leads`)**:
   - Đăng nhập `admin@salesflow.test` ➔ Thấy đủ 30 Lead demo.
   - Đăng nhập `demo03@salesflow.test` (Sales Manager SALES) ➔ Thấy 18 Lead thuộc phòng ban SALES.
   - Đăng nhập `demo04@salesflow.test` (Sales Person) ➔ Thấy các Lead do chính mình sở hữu.
   - Thử tìm kiếm theo tên, email, sđt, lọc theo trạng thái, ưu tiên, tag ➔ URL state tự đồng bộ query params.

2. **Kiểm thử Tạo và Chỉnh sửa Lead (Form Full-page)**:
   - Đăng nhập `admin@salesflow.test`, nhấn **Tạo mới Lead** ➔ Mở trang `/leads/create`.
   - Nhập thông tin và chọn nhiều Tag, lưu ➔ Hệ thống tạo thành công và ghi nhận Audit log.
   - Đăng nhập `demo04@salesflow.test` (Sales Person), tạo Lead ➔ Hệ thống tự động gán chính Sales Person làm owner, không cho chọn người khác.
   - Đăng nhập `demo12@salesflow.test` (Viewer) ➔ Nút tạo/sửa bị ẩn. Truy cập trực tiếp `/leads/create` hoặc `/leads/1/edit` bị trả `403`.

3. **Kiểm thử Cảnh báo Trùng lặp (Duplicate Guard)**:
   - Thử tạo mới Lead với email/sđt trùng với Lead đang hoạt động ➔ Modal cảnh báo xuất hiện hiển thị các đối tượng trùng.
   - Nếu bấm **Vẫn lưu riêng**, hệ thống bắt buộc nhập lý do ghi đè dài ít nhất 10 ký tự.

4. **Kiểm thử Workflow Modals (Assign Owner & Change Status)**:
   - Mở chi tiết Lead ➔ Nhấn **Gán người phụ trách** hoặc **Đổi trạng thái** ➔ Form dạng Modal hiển thị mượt mà.
   - Đổi trạng thái sang `unqualified` hoặc `lost` mà bỏ trống lý do ➔ Hệ thống từ chối và báo lỗi tiếng Việt.
   - Timeline nghiệp vụ hiển thị cập nhật ngay lịch sử chuyển trạng thái/phân công.

5. **Kiểm thử Thùng rác & Khôi phục (Trash & Restore)**:
   - Nhấn **Đưa vào thùng rác** kèm lý do ➔ Lead chuyển sang trạng thái xóa mềm.
   - Mở `/leads/trash` ➔ Nhấn Khôi phục (Restore). Nếu Lead trùng thông tin với 1 Lead active khác ➔ Cảnh báo xung đột xuất hiện yêu cầu nhập lý do ghi đè.

6. **Kiểm thử Eligibility Chuyển đổi (Conversion Eligibility)**:
   - Đăng nhập `admin@salesflow.test`, kiểm tra Lead ở trạng thái `qualified` ➔ Service xác nhận đủ điều kiện.
   - Với Lead đã `converted` hoặc Lead bị xóa mềm ➔ Service ném `LeadConversionException` tiếng Việt chuẩn xác.

---

## Điều kiện đạt checkpoint P3-10

- 100% tests toàn hệ thống, Pint, PHPStan và Vite build đều đạt xanh.
- 5 vai trò hiển thị đúng Data Scope trên danh sách, chi tiết và các modals.
- Chống chuyển đổi lặp và chống trùng lặp dữ liệu hoạt động an toàn.
- Audit Log ghi nhận đúng actor, subject, event và properties cho các thao tác Lead.
