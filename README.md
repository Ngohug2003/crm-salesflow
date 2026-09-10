# SalesFlow CRM — Hệ thống Quản trị Quan hệ Khách hàng Phân hệ Bán hàng

SalesFlow CRM là một ứng dụng monolith được thiết kế theo cấu trúc modular-monolith sử dụng framework **Laravel 12**, thiết lập ranh giới nghiệp vụ rõ ràng (separation of concerns) và tổ chức mã nguồn theo các Technical Layers chuẩn mực (Controller ➔ Livewire ➔ Service ➔ Repository ➔ Eloquent ➔ PostgreSQL).

Dự án hiện tại đã hoàn tất triển khai **Giai đoạn 3 (Leads Lifecycle)** và hoàn thành toàn bộ các hạng mục **Technical Remediation (P1-P3)**. Lộ trình phát triển được theo dõi tại [PROJECT_PHASES.md](PROJECT_PHASES.md).

---

## 1. Bản đồ Nghiệp vụ & Các chức năng đã triển khai

Hệ thống đã triển khai hoàn thiện và tích hợp chặt chẽ các phân hệ lõi sau:

### 1.1 Quản trị Tổ chức & Phân quyền (RBAC & Data Scopes)
- **Cơ cấu Phòng ban (Departments)**: 
  - Hỗ trợ mô hình cây phòng ban phân cấp (Parent-Child) không giới hạn.
  - Ràng buộc trạng thái hoạt động chặt chẽ: không cho phép tắt phòng ban cha nếu còn con hoạt động; không cho phép cycle reference (chọn chính mình hoặc hậu duệ làm cha).
  - Quy tắc xóa an toàn: Chỉ được xóa phòng ban khi không chứa người dùng và không có phòng ban con.
- **Danh mục quyền hạn (RBAC Catalog)**:
  - Hệ thống gồm **45 quyền cụ thể** phân theo phân hệ (quản trị, phòng ban, người dùng, lead, cơ hội, kiểm toán...) được cấu hình tập trung trong `config/crm.php`.
  - Khởi tạo tự động và idempotent qua `RolePermissionSeeder` cho **5 Role mặc định**:
    - `super-admin`: Bypass mọi Gate kiểm tra (sử dụng cơ chế `Gate::before`).
    - `admin`: Quản lý toàn bộ cấu hình hệ thống và dữ liệu.
    - `sales-manager`: Phạm vi dữ liệu cấp phòng ban (`department` scope).
    - `sales`: Phạm vi dữ liệu sở hữu cá nhân (`owned` scope).
    - `viewer`: Phạm vi chỉ đọc toàn hệ thống (`read-only` scope), chặn đứng mọi hành vi chỉnh sửa/thao tác ghi ở backend.
- **Ranh giới Dữ liệu (Backend Data Scopes)**:
  - Cưỡng chế lọc phạm vi dữ liệu (`all`, `department`, `owned`, `read-only`) trực tiếp tại Repository và Service Layer trước khi thực hiện tìm kiếm, sắp xếp hay phân trang. 
  - Đảm bảo an toàn thông tin tối đa: không rò rỉ metadata của bản ghi nằm ngoài phạm vi truy cập thông qua các thông báo lỗi hay duplicate validation.

### 1.2 Xác thực & Quản lý Phiên (Authentication & Session Security)
- **Tính năng xác thực**: Tích hợp Laravel Fortify hỗ trợ đăng nhập, đăng xuất, quên/đổi mật khẩu, xác minh email, xác nhận mật khẩu bảo mật.
- **Quản lý phiên (Session Management)**:
  - Người dùng có quyền tự xem danh sách các phiên đăng nhập đang hoạt động của mình (thiết bị, IP, thời gian tương tác).
  - Thu hồi phiên hoạt động từ xa (Revoke) yêu cầu xác nhận mật khẩu gần đây (`password.confirm`).
- **Khóa tài khoản (Account Lifecycle)**:
  - Khóa tài khoản (`is_active = false`) sẽ chặn đăng nhập lập tức ở Fortify và thu hồi mọi phiên hoạt động hiện có của tài khoản đó ở Middleware.

### 1.3 Quản lý Lead & Vòng đời (Leads Lifecycle)
- **Chuẩn hóa thông tin liên hệ**: tự động chuẩn hóa email về chữ thường, số điện thoại Việt Nam (+84, 0084, ký tự phân tách) về định dạng đầu số `0` tiêu chuẩn trước khi lưu trữ.
- **Bộ lọc & Tìm kiếm nâng cao**: 
  - Lọc đa điều kiện (Trạng thái, độ ưu tiên, nguồn Lead, nhiều tags, owner, phòng ban, khoảng ngày khởi tạo).
  - Sắp xếp ổn định theo allowlist (chặn SQL Injection trên tham số sort) và đồng bộ trạng thái bộ lọc lên URL (`Livewire URL state`) giúp chia sẻ hoặc tải lại trang giữ nguyên kết quả.
- **Phòng ngừa trùng lặp (Duplicate Guard)**:
  - Quét trùng lặp email/điện thoại (bao gồm cả Lead trong Thùng rác) dựa trên signature liên hệ trước khi ghi dữ liệu.
  - Hiển thị Warning Modal kèm thông tin đối tượng trùng. Yêu cầu nhập lý do ghi đè dài tối thiểu 10 ký tự nếu người dùng chọn ghi đè (`Vẫn lưu riêng`).
- **Workflow & Lịch sử**:
  - Giao diện thao tác bằng **Modals** cho "Phân công người phụ trách" (`assign-owner-modal`) và "Chuyển trạng thái" (`change-status-modal`).
  - Áp dụng ma trận chuyển trạng thái hợp lệ (Transition Matrix), ngăn chặn chuyển thủ công sang trạng thái `converted`.
  - Bắt buộc nhập lý do khi chuyển Lead sang `unqualified` hoặc `lost`.
  - Lưu trữ lịch sử thay đổi bất biến vào bảng `lead_assignment_histories` và `lead_status_histories`.
- **Thùng rác & Khôi phục (Trash & Restore)**:
  - Đưa vào thùng rác đi kèm lý do cụ thể.
  - Khi khôi phục (Restore), kiểm tra trùng lặp với các Lead đang hoạt động; tự động hủy phân công và ghi nhận unassigned history nếu tài khoản phụ trách gốc đã bị khóa/vô hiệu hóa.

### 1.4 Nhật ký kiểm toán & Realtime Audit Logs
- **System Audit Log**: 
  - Ghi nhận chi tiết lịch sử tạo/sửa đổi/xóa các đối tượng, đăng nhập/đăng xuất/đổi mật khẩu.
  - Tự động lọc bỏ các thông tin nhạy cảm lồng nhau (password, token, hash) ra khỏi log properties.
  - Phân quyền IT-only: Chỉ Super Admin hoặc Admin thuộc phòng ban có mã `IT` mới được phép truy cập Nhật ký kiểm toán.
- **Realtime Updates**: 
  - Phát tín hiệu realtime qua kênh Private WebSocket (`reverb`) sử dụng Laravel Echo khi có log mới phát sinh.
  - Giao diện Livewire tự động tải lại dữ liệu và hiệu ứng highlight dòng log mới mà không cần reload trang.

---

## 2. Công nghệ sử dụng (Tech Stack)

- **Backend**: PHP 8.5 (yêu cầu PHP 8.3+) / Laravel 12
- **Frontend**: Blade / Livewire 3 / Flux UI Free / Alpine.js / Tailwind CSS 4 / Vite
- **Database / Caching**: PostgreSQL 17 / Redis 7
- **Realtime / Queue**: Laravel Reverb (WebSocket) / Laravel Horizon
- **Testing & Quality Gates**: Pest / Laravel Pint / Larastan (PHPStan Level 5)
- **Services**: Nginx / Mailpit (SMTP server giả lập) / MinIO (S3 object storage tương thích) / Docker Compose

---

## 3. Hướng dẫn cài đặt & Khởi động nhanh (Docker)

### 3.1 Yêu cầu hệ thống
- Đã cài đặt **Docker Desktop** (bật WSL 2 trên Windows) hoặc **Docker Engine + Docker Compose** trên Linux.

### 3.2 Các bước khởi động dự án

1. **Sao chép tệp cấu hình môi trường**:
   ```bash
   cp .env.example .env
   ```

2. **Xây dựng Docker image cho PHP app**:
   ```bash
   docker compose build app
   ```

3. **Khởi tạo mã khóa ứng dụng (Application Key)**:
   ```bash
   docker compose run --rm --user "$(id -u):$(id -g)" app php artisan key:generate
   ```

4. **Khởi động toàn bộ topology (10 services)** ở chế độ background:
   ```bash
   docker compose up -d --build
   ```

5. **Chạy database migrations và seeds dữ liệu demo**:
   ```bash
   docker compose exec app php artisan migrate --seed
   ```

6. **Kiểm tra trạng thái các container**:
   ```bash
   docker compose ps
   ```
   *Yêu cầu tất cả container đều hiển thị trạng thái `healthy` hoặc `running`.*

### 3.3 Các lệnh quản lý Cơ sở Dữ liệu hữu ích

- **Reset & nạp lại sạch toàn bộ dữ liệu mẫu (Force Fresh Seed)**:
  ```bash
  docker compose exec app php artisan migrate:fresh --seed
  ```
- **Chỉ nạp thêm dữ liệu Seed (không xóa các bảng hiện tại)**:
  ```bash
  docker compose exec app php artisan db:seed --force
  ```
- **Chạy các Migration mới bổ sung (không làm mất dữ liệu hiện có)**:
  ```bash
  docker compose exec app php artisan migrate --force
  ```

---

## 4. Địa chỉ các dịch vụ cục bộ (Endpoints)

- **CRM Application**: http://localhost
- **Application Health Check**: http://localhost/up
- **Mailpit Web UI (Kiểm tra email gửi đi)**: http://localhost:8025
- **MinIO Console (Quản lý S3 storage)**: http://localhost:9001
- **Horizon Queue Dashboard**: http://localhost/horizon (chỉ cho phép truy cập ở môi trường `local`)

---

## 5. Tài khoản dùng thử (Demo Accounts)

Sau khi chạy database seeder, các tài khoản mẫu sau đã sẵn sàng để kiểm thử (Mật khẩu chung: `SalesFlow@123`):

| Role / Chức danh | Email đăng nhập | Phòng ban | Trách nhiệm chính |
|---|---|---|---|
| **Super Admin** | `admin@salesflow.test` | MANAGEMENT | Toàn quyền hệ thống, xem nhật ký kiểm toán |
| **Admin (Phòng IT)** | `it.admin@salesflow.test` | IT | Quản trị người dùng/phòng ban, xem nhật ký kiểm toán1 |
| **Sales Manager** | `demo03@salesflow.test` | SALES | Quản lý đội ngũ và Lead trong phòng ban SALES |
| **Sales Person** | `demo04@salesflow.test` | SALES | Tiếp nhận, chăm sóc và theo dõi Lead được giao |
| **Viewer** | `demo12@salesflow.test` | SALES | Tài khoản chỉ đọc dữ liệu, chặn mọi thao tác sửa đổi |

---

## 6. Kiểm soát chất lượng (Quality Gates)

Để đảm bảo mã nguồn luôn tuân thủ tiêu chuẩn chất lượng nghiêm ngặt của dự án, hãy chạy các lệnh kiểm tra sau trước khi đóng checkpoint:

- **Chạy toàn bộ 172 test cases**:
  ```bash
  docker compose exec app php artisan test
  ```
- **Kiểm tra và tự động sửa định dạng code (Pint)**:
  ```bash
  docker compose exec app ./vendor/bin/pint --test
  ```
- **Phân tích tĩnh mã nguồn (PHPStan Level 5)**:
  ```bash
  docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
  ```
- **Build assets cho môi trường sản xuất (Vite CSS/JS)**:
  ```bash
  docker compose exec vite npm run build
  ```

---

## 7. Tài liệu kỹ thuật liên quan

- [Tài liệu Kiến trúc & ERD](docs/architecture.md)
- [Ma trận quyền hạn và Data Scope](docs/permissions.md)
- [Thiết kế Cơ sở Dữ liệu & Normalization](docs/database.md)
- [Kiểm thử phân quyền & Checkpoint nghiệm thu Giai đoạn 2](docs/authorization-checkpoint.md)
