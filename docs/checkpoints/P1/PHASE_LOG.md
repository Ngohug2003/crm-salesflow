# Giai đoạn 1 — Khởi tạo nền tảng và Docker

## Kế hoạch và Nhật ký triển khai chi tiết

### Phạm vi đã thống nhất

- Laravel stable, PHP 8.3+, PostgreSQL, Redis.
- Livewire 3, Flux UI bản miễn phí, Tailwind CSS, Alpine.js, Vite.
- Fortify authentication, Sanctum API authentication.
- Horizon, Reverb, queue và scheduler foundation.
- Docker Compose: app, nginx, postgres, redis, horizon, scheduler, reverb, mailpit, minio.
- App shell: auth layout, sidebar, topbar, responsive và dark mode.
- Smoke test, test suite, Pint, Larastan và asset build.

### Những gì đã làm

1. Kiểm tra workspace: thư mục ban đầu trống, chỉ có metadata `.git`, `.agents`, `.codex`.
2. Kiểm tra máy: có Docker CLI/Compose; không có PHP, Composer và Node chạy native trong WSL hiện tại.
3. Dùng image `composer:2` để scaffold Laravel 12.
4. Cài các package backend/component bắt buộc.
5. Publish một phần config/migration của Permission, Activity Log, Fortify, Horizon và Broadcasting.
6. Tạo các thư mục module dự kiến và tài liệu kiến trúc. Chưa viết CRUD.

### Thư viện/component đã cài

Production dependencies trong `composer.json`:

| Package | Constraint | Mục đích |
|---|---:|---|
| `laravel/framework` | `^12.0` | Framework |
| `livewire/livewire` | `^3.6` | Reactive server UI |
| `livewire/flux` | `^2.0` | Component UI chính, không dùng Flux Pro |
| `laravel/fortify` | `^1.37` | Authentication backend |
| `laravel/sanctum` | `^4.3` | API token/auth |
| `laravel/horizon` | `^5.47` | Redis queue dashboard/workers |
| `laravel/reverb` | `^1.10` | WebSocket/realtime |
| `spatie/laravel-permission` | `^8.3` | Role/permission |
| `spatie/laravel-activitylog` | `^5.0` | Audit/activity log |

Development dependencies đã bổ sung:

| Package | Constraint | Mục đích |
|---|---:|---|
| `pestphp/pest` | `^3.8` | Test runner |
| `pestphp/pest-plugin-laravel` | `^3.2` | Laravel integration cho Pest |
| `nunomaduro/larastan` | `^3.0` | Static analysis; cần đổi sang package kế nhiệm `larastan/larastan` trước khi chốt phase |

Chưa cài package frontend bổ sung như `sortablejs` và `chart.js`; chưa chạy `npm install`.

### Các lệnh đã chạy

Kiểm tra môi trường:

```bash
php -v
composer --version
node --version
npm --version
docker --version
docker compose version
docker info --format '{{.ServerVersion}} {{.OSType}}'
docker run --rm composer:2 --version
```

Scaffold Laravel vào thư mục tạm rồi sao chép vào workspace:

```bash
mkdir -p /tmp/salesflow-scaffold-019f7
docker run --rm -u 1000:1000 \
  -v /tmp/salesflow-scaffold-019f7:/workspace \
  composer:2 create-project laravel/laravel:^12.0 /workspace --no-interaction
cp -a /tmp/salesflow-scaffold-019f7/. .
```

Cài production dependencies:

```bash
docker run --rm -u 1000:1000 \
  -v /home/hungnv/crm-salesflow:/app -w /app composer:2 \
  require livewire/livewire:^3.6 livewire/flux:^2.0 \
  laravel/fortify laravel/sanctum laravel/horizon laravel/reverb \
  spatie/laravel-permission spatie/laravel-activitylog \
  --with-all-dependencies --ignore-platform-req=ext-pcntl --no-interaction
```

`ext-pcntl` chỉ bị bỏ qua trong image Composer. Docker image PHP của ứng dụng vẫn phải cài `pcntl` để Horizon hoạt động.

Cài development dependencies:

```bash
docker run --rm -u 1000:1000 \
  -v /home/hungnv/crm-salesflow:/app -w /app composer:2 \
  require pestphp/pest:^3.8 pestphp/pest-plugin-laravel:^3.2 \
  nunomaduro/larastan:^3.0 --dev --with-all-dependencies \
  --ignore-platform-req=ext-pcntl --no-interaction
```

Publish package assets (lệnh dừng ở bước Reverb):

```bash
docker run --rm -u 1000:1000 \
  -v /home/hungnv/crm-salesflow:/app -w /app composer:2 sh -lc '
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force &&
    php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations" --force &&
    php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider" --force &&
    php artisan horizon:install &&
    php artisan reverb:install --no-interaction &&
    php artisan install:api --no-interaction
  '
```

### Kết quả và lỗi cần biết

- Scaffold Laravel và `composer install/require` thành công.
- `composer audit` trong quá trình cài đặt báo không có security advisory.
- Permission, Activity Log, Fortify và Horizon đã publish thành công.
- `reverb:install --no-interaction` thất bại vì installer yêu cầu nhập App ID/Key/Secret. `config/broadcasting.php` và `routes/channels.php` đã được tạo trước khi lệnh dừng.
- Vì chuỗi lệnh dùng `&&`, `php artisan install:api` **chưa chạy**.
- `config/reverb.php` sau đó được sao chép từ package, nhưng chưa cấu hình/kiểm thử.
- Fortify provider đã được tạo nhưng chưa đăng ký trong `bootstrap/providers.php`.
- Chưa có Dockerfile hoặc `docker-compose.yml` hoàn chỉnh.
- Chưa chạy migration bằng PostgreSQL, chưa seed, chưa chạy test/Pint/Larastan, chưa build frontend.
- Ứng dụng hiện tại **chưa đạt checkpoint để chạy thử bằng Docker**.

### File package đã tạo/publish

- `app/Actions/Fortify/*`
- `app/Providers/FortifyServiceProvider.php`
- `app/Providers/HorizonServiceProvider.php`
- `config/fortify.php`
- `config/horizon.php`
- `config/permission.php`
- `config/broadcasting.php`
- `config/reverb.php`
- `routes/channels.php`
- `database/migrations/2026_07_20_131729_create_permission_tables.php`
- `database/migrations/2026_07_20_131730_create_activity_log_table.php`
- `database/migrations/2026_07_20_131732_add_two_factor_columns_to_users_table.php`
- `database/migrations/2026_07_20_131733_create_passkeys_table.php`

### Việc còn lại để hoàn tất Giai đoạn 1

- Đăng ký Fortify provider và cấu hình login/reset/verification views.
- Cấu hình Sanctum/API route theo Laravel 12.
- Tạo layout Flux UI, sidebar/topbar/dark mode và trang dashboard placeholder có auth.
- Cài frontend dependencies và build assets.
- Tạo PHP Dockerfile, Nginx config, entrypoint và Compose đủ 9 service.
- Cấu hình `.env.example` cho PostgreSQL, Redis, Reverb, Mailpit và MinIO.
- Tạo health checks và bảo đảm container chạy không race migration/database.
- Chạy migrate, smoke test, Pest, Pint, Larastan và `npm run build`.
- Cập nhật README với lệnh chạy và tài khoản test phù hợp với phase.

### Cập nhật lần 2 — tiếp tục ngày 20/07/2026

Đã triển khai thêm nhưng chưa đánh dấu hoàn tất:

- Đăng ký `FortifyServiceProvider`; cấu hình login, reset password, email verification, password confirmation và two-factor challenge views.
- Thêm middleware `account.active`; tài khoản khóa bị từ chối ở cả thời điểm đăng nhập và khi truy cập route bảo vệ.
- Thêm Sanctum API foundation tại `/api/v1/user`.
- Thêm dashboard Livewire, app/guest layouts, Flux UI, responsive sidebar, persisted sidebar state và dark mode chống nháy.
- Thêm Docker multi-stage build và Compose cho đủ 9 service: app, nginx, postgres, redis, horizon, scheduler, reverb, mailpit, minio.
- Thêm PostgreSQL/Redis/Reverb/Mailpit/MinIO environment template và Nginx security headers.
- Thêm Pest tests cho auth, account lock, verification và dashboard; thêm Pint/Larastan config.
- Thay README mặc định bằng hướng dẫn dự án và Docker.

File chính được thêm/sửa trong lần tiếp tục:

- `Dockerfile`, `compose.yaml`, `.dockerignore`
- `docker/php/entrypoint.sh`, `docker/php/php.ini`, `docker/nginx/default.conf`
- `app/Http/Middleware/EnsureAccountIsActive.php`
- `app/Livewire/Dashboard/DashboardOverview.php`
- `app/Models/User.php`, `app/Providers/FortifyServiceProvider.php`
- `bootstrap/app.php`, `bootstrap/providers.php`, `config/fortify.php`
- `routes/web.php`, `routes/api.php`
- `resources/views/layouts/*`, `resources/views/auth/*`, `resources/views/livewire/dashboard/*`
- `resources/css/app.css`, `resources/js/app.js`
- `tests/Pest.php`, `tests/Feature/AuthenticationTest.php`, `tests/Unit/AccountStateTest.php`
- `phpstan.neon`, `.env.example`, `README.md`

Blocker Docker Desktop/WSL ở thời điểm cập nhật này đã được xử lý trong lần tiếp tục thứ 3 bên dưới.

### Cập nhật lần 3 — hoàn tất triển khai ngày 20/07/2026

- Sửa Dockerfile cho PHP 8.5: không biên dịch lại `dom`, `mbstring`, `opcache`, `xml` vì các extension này đã có trong image nền; giữ `bcmath`, `gd`, `intl`, `pcntl`, `pdo_pgsql`, `zip` và Redis PECL.
- Build thành công hai image `salesflow-crm-app:local` và `salesflow-crm-web:local`.
- Khởi động đủ 9 service và xác nhận tất cả đều `healthy`.
- Tạo `APP_KEY`, chạy `migrate:fresh --seed` thành công trên PostgreSQL 17 và tạo tài khoản demo.
- Sửa test environment để luôn ép SQLite in-memory trước khi Laravel bootstrap, tránh test chạm vào PostgreSQL phát triển; tắt Vite trong HTTP test.
- Sửa nhánh kiểm tra email verification luôn đúng trong `UpdateUserProfileInformation`.
- Chạy Pint và sửa 6 lỗi định dạng.
- Thay package abandoned `nunomaduro/larastan` bằng `larastan/larastan` v3.10.0.
- Smoke test đạt: `/` trả 302, `/login` trả 200, `/up` trả 200.

Các lệnh quan trọng đã chạy trong lần hoàn tất:

```bash
docker compose build app
docker compose up -d --build
docker compose exec --user "$(id -u):$(id -g)" app php artisan key:generate --force
docker compose exec app php artisan migrate:fresh --seed --force
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --no-progress
docker compose exec app composer audit --no-interaction
curl -I http://127.0.0.1/login
curl -I http://127.0.0.1/up
```

Kết quả quality gate cuối sau khi rebuild package Larastan mới:

| Kiểm tra | Kết quả |
|---|---|
| Docker Compose | 9/9 service `healthy` |
| PostgreSQL migrations | 9/9 migration trạng thái `Ran` |
| Pest | 7 test đạt, 18 assertion |
| Pint | 49 file đạt chuẩn |
| PHPStan/Larastan level 5 | Không có lỗi |
| Composer audit | Không có security advisory, không còn package abandoned |
| Vite production build | 55 module; CSS 258.79 kB, JS 46.31 kB |
| HTTP smoke test | `/` 302, `/login` 200, `/up` 200 |

Seeder idempotent đã được chạy lại sau test; tài khoản `admin@salesflow.test` sẵn sàng cho kiểm thử thủ công. Giai đoạn 1 **hoàn tất triển khai và dừng tại checkpoint chờ chủ dự án xác nhận**.

### Kiểm thử Mailpit tại checkpoint

Đã kích hoạt luồng quên mật khẩu qua password broker của Laravel:

```bash
docker compose exec -e XDG_CONFIG_HOME=/tmp app php artisan tinker \
  --execute="dump(Illuminate\\Support\\Facades\\Password::sendResetLink(['email' => 'admin@salesflow.test']));"
```

Laravel trả về `passwords.sent`. API Mailpit xác nhận nhận được 1 email chưa đọc từ `SalesFlow CRM <hello@salesflow.test>` tới `admin@salesflow.test`, tiêu đề `Reset Password Notification`, không có file đính kèm. Luồng chỉ tạo link reset; mật khẩu hiện tại chưa bị thay đổi.

### Kết nối PostgreSQL bằng DBeaver tại checkpoint

Đã thêm mapping `${DB_FORWARD_PORT:-15432}:5432` cho service PostgreSQL và recreate riêng container mà không xóa named volume. Trạng thái sau thay đổi là `healthy`, cổng host `0.0.0.0:15432`. DBeaver sử dụng `localhost:15432`; Laravel trong mạng Docker tiếp tục sử dụng `postgres:5432`.

### Checkpoint dự kiến khi hoàn tất

Chủ dự án sẽ chạy:

```bash
cp .env.example .env
docker compose build app
docker compose run --rm --user "$(id -u):$(id -g)" app php artisan key:generate
docker compose up -d --build
docker compose ps
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app composer audit --no-interaction
```

Sau đó kiểm tra `http://localhost`, `/login`, `/dashboard`, `/up`, Mailpit, MinIO, Horizon và kết nối Reverb. Dừng tại đây; chỉ bắt đầu Giai đoạn 2 sau khi chủ dự án xác nhận checkpoint.
