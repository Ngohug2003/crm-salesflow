# P2-08 Authorization checkpoint

Tài liệu này dùng để nghiệm thu Giai đoạn 2 trước khi bắt đầu P3-01. Mật khẩu chung của các tài khoản local bên dưới là `SalesFlow@123`.

## Tài khoản và kết quả mong đợi

| Role | Email | User | Department | Audit Log |
|---|---|---|---|---|
| Super Admin | `admin@salesflow.test` | Quản lý | Quản lý | Được xem |
| Admin IT | `it.admin@salesflow.test` | Quản lý | Quản lý | Được xem |
| Sales Manager | `demo03@salesflow.test` | Chỉ xem trong SALES | Chỉ xem | Bị chặn |
| Sales | `demo04@salesflow.test` | Bị chặn | Bị chặn | Bị chặn |
| Viewer | `demo12@salesflow.test` | Bị chặn | Bị chặn | Bị chặn |

Admin ngoài IT `demo01@salesflow.test` vẫn quản lý User/Department nhưng không được xem Audit Log.

## Chuẩn bị dữ liệu

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed --force
docker compose ps
```

Seeder có thể chạy lại. Sau lần chạy thứ hai, email demo không bị nhân đôi và mỗi tài khoản vẫn chỉ mang các role được khai báo.

## Kiểm thử tự động

Chạy checkpoint riêng:

```bash
docker compose exec app php artisan test tests/Feature/AuthorizationCheckpointTest.php
```

Kết quả chuẩn tại thời điểm hoàn tất P2-08 là 15 test và 115 assertions.

Chạy toàn bộ dự án và các cổng chất lượng:

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose ps
```

## Kiểm thử thủ công

1. Đăng nhập lần lượt từng tài khoản trong bảng và đối chiếu menu User, Department, Audit Log.
2. Với Sales hoặc Viewer, nhập trực tiếp `/settings/users`, `/settings/departments` và `/settings/audit-logs`; cả ba trang phải trả `403`.
3. Với Sales Manager, mở User và xác nhận chỉ thấy người thuộc SALES; không có nút tạo/sửa và gọi action ghi trực tiếp vẫn bị `403`.
4. Với Admin IT, mở User/Department và thực hiện tạo hoặc sửa; mở Audit Log để xác nhận activity xuất hiện.
5. Với Admin ngoài IT `demo01@salesflow.test`, xác nhận không có menu Audit Log và truy cập trực tiếp `/settings/audit-logs` trả `403`.
6. Với Admin thường, xác nhận không thể sửa hoặc xóa `admin@salesflow.test`; chỉ Super Admin được quản lý mục tiêu này.
7. Đăng nhập `demo07@salesflow.test` và xác nhận được chuyển đến bước xác minh email.
8. Đăng nhập `demo08@salesflow.test` và xác nhận hệ thống từ chối tài khoản đã khóa.
9. Mở Audit Log bằng Admin IT, sau đó đăng nhập tài khoản khác trong cửa sổ ẩn danh; log mới phải xuất hiện realtime mà không tải lại trang.

## Điều kiện đạt checkpoint

- Bộ test toàn dự án, Pint, PHPStan và Vite build đều đạt.
- Cả năm role hiển thị navigation đúng và truy cập URL trực tiếp đúng kết quả.
- Data scope phòng ban không làm lộ user phòng ban khác.
- User khóa/chưa xác minh bị middleware chặn.
- Audit chỉ được xem bởi Super Admin hoặc Admin IT và realtime hoạt động.
- Không có đường backend cho Admin thường sửa/xóa Super Admin.

Sau khi chủ dự án xác nhận toàn bộ checklist, P2-08 được nghiệm thu và có thể bắt đầu P3-01.
