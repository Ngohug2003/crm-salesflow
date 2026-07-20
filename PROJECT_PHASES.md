# SalesFlow CRM — Kế hoạch giai đoạn và nhật ký triển khai

Tài liệu này là checkpoint chính của dự án. Quy ước làm việc từ ngày 20/07/2026:

1. Chỉ triển khai đúng phạm vi của **một giai đoạn**.
2. Cuối giai đoạn, cập nhật file này với file đã tạo/sửa, migration, lệnh đã chạy và kết quả kiểm tra.
3. Dừng lại để chủ dự án chạy test thủ công.
4. Chỉ chuyển giai đoạn sau khi nhận xác nhận rõ ràng.
5. Không đánh dấu hoàn tất nếu migration, test, Pint, static analysis và frontend build chưa đạt.

## Trạng thái tổng quan

| Mã | Giai đoạn | Trạng thái | Checkpoint của chủ dự án |
|---|---|---|---|
| 0 | Phân tích kiến trúc và dữ liệu | Hoàn tất tài liệu ban đầu | Chưa xác nhận |
| 1 | Khởi tạo nền tảng và Docker | Hoàn tất triển khai | Chờ chủ dự án kiểm thử |
| 2 | Users, Departments, Roles, Permissions | Đang làm — P2-01 hoàn tất | Chờ kiểm thử P2-01 |
| 3 | Leads | Chưa bắt đầu | — |
| 4 | Companies và Contacts | Chưa bắt đầu | — |
| 5 | Pipelines và Opportunities | Chưa bắt đầu | — |
| 6 | Activities và Tasks | Chưa bắt đầu | — |
| 7 | Dashboard và Reports | Chưa bắt đầu | — |
| 8 | Import, Export, Notifications và Audit | Chưa bắt đầu | — |
| 9 | Hoàn thiện, CI/CD và deployment | Chưa bắt đầu | — |

## Giai đoạn 0 — Phân tích kiến trúc và dữ liệu

### Mục tiêu

- Phân tích nghiệp vụ và module map.
- Mô tả business flow bằng Mermaid.
- Thiết kế ERD ở mức domain.
- Xây permission matrix.
- Đưa ra timeline, man-day, rủi ro và tiêu chí MVP.

### File đã tạo

- `docs/architecture.md`: business analysis, module map, business flow, ERD, estimate 90 man-day, risk và MVP criteria.
- `docs/permissions.md`: ma trận quyền cho 5 role mặc định.
- `docs/database.md`: định hướng PostgreSQL và khóa chính `BIGINT` tự tăng.
- `docs/api.md`: định hướng API v1/Sanctum.
- `docs/deployment.md`: topology Docker/production ban đầu.

### Việc cần chủ dự án kiểm tra

- Đọc `docs/architecture.md` và xác nhận luồng Lead → Company/Contact → Opportunity.
- Đọc `docs/permissions.md` và xác nhận data scope của role `sales` và `sales-manager`.

### Trạng thái

Tài liệu ban đầu đã được tạo nhưng **chưa có xác nhận của chủ dự án**.

---

## Giai đoạn 1 — Khởi tạo nền tảng và Docker

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

---

## Quy ước triển khai feature từ Giai đoạn 2

- Mỗi mã feature tương ứng một branch độc lập; mẫu branch: `feature/p2-01-department-schema`.
- Chỉ bắt đầu feature khi các mã trong cột **Phụ thuộc** đã hoàn tất.
- Mỗi branch phải có migration/code/test/tài liệu đúng phạm vi, chạy đạt Pest liên quan, Pint và PHPStan.
- Feature có chữ **Checkpoint** là điểm dừng bắt buộc để chủ dự án kiểm thử trước khi chuyển giai đoạn.
- Danh sách chi tiết để lọc, giao việc và theo dõi nằm trong `SALESFLOW_FEATURE_PLAN.xlsx`.
- Workbook có 3 sheet: `Features`, `Tổng quan giai đoạn`, `Hướng dẫn branch`; có thể tạo lại sau khi sửa file này bằng `python3 scripts/generate_feature_plan.py`.

## Giai đoạn 2 — Users, Departments, Roles, Permissions

Mục tiêu: hoàn thiện tổ chức người dùng và ranh giới phân quyền trước khi tạo dữ liệu CRM.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P2-01 | ✅ Department schema và domain | `feature/p2-01-department-schema` | P1 | Migration/model/factory/seed phòng ban, quan hệ cha-con và trạng thái hoạt động |
| P2-02 | Quản lý phòng ban | `feature/p2-02-department-management` | P2-01 | Livewire list/create/edit/disable phòng ban, validation và test |
| P2-03 | Danh mục quyền và role seeder | `feature/p2-03-rbac-catalog-seeder` | P2-01 | `config/crm.php`, 5 role mặc định, permission idempotent và super-admin bypass |
| P2-04 | Data scope và policies nền tảng | `feature/p2-04-data-scope-policies` | P2-03 | Scope all/department/owned/read-only được cưỡng chế ở backend |
| P2-05 | Danh sách người dùng | `feature/p2-05-user-list` | P2-01, P2-04 | Tìm kiếm, lọc phòng ban/role/trạng thái, phân trang và URL state |
| P2-06 | Tạo và chỉnh sửa người dùng | `feature/p2-06-user-form` | P2-05 | Form tạo/sửa, active/locked state, email uniqueness và password rule |
| P2-07 | Gán phòng ban và role | `feature/p2-07-user-role-assignment` | P2-03, P2-06 | UI gán role/phòng ban, chống tự khóa admin cuối cùng, audit thay đổi |
| P2-08 | Authorization test và checkpoint | `feature/p2-08-authorization-checkpoint` | P2-02..P2-07 | Test đủ 5 role, navigation theo quyền, seed demo và checklist nghiệm thu |

### Nhật ký feature P2-01 — Department schema và domain

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- `departments` dùng khóa chính PostgreSQL `BIGINT` tự tăng; quyết định này thay thế định hướng ULID cũ cho toàn bộ application domain table.
- Cây phòng ban qua `parent_id`, trạng thái `is_active`, thứ tự `sort_order`, `code` duy nhất và các index phục vụ truy vấn.
- Model `Department` với `parent`, `children`, `users`, active scope và typed casts.
- `users.department_id` nullable, quan hệ hai chiều User–Department và `nullOnDelete`.
- Factory có state inactive/child; seeder idempotent tạo `MANAGEMENT`, `SALES`, `MARKETING` và gán admin demo vào `MANAGEMENT`.
- 6 test riêng cho ID tự tăng, cây cha-con, active scope, User relation, seeder idempotent và foreign-key behavior.

File chính:

- `app/Models/Department.php`, `app/Models/User.php`
- `database/factories/DepartmentFactory.php`, `database/factories/UserFactory.php`
- `database/migrations/2026_07_20_150000_create_departments_table.php`
- `database/migrations/2026_07_20_150001_add_department_id_to_users_table.php`
- `database/seeders/DepartmentSeeder.php`, `database/seeders/DatabaseSeeder.php`
- `tests/Feature/DepartmentDomainTest.php`
- `docs/database.md`, `docs/architecture.md`

Lệnh kiểm chứng:

```bash
docker compose build app
docker compose up -d --force-recreate app
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --no-progress
docker compose exec app php artisan migrate:status
```

Kết quả cuối:

- PostgreSQL: 2 migration P2-01 ở batch 2, trạng thái `Ran`.
- Seed: ID `1 MANAGEMENT`, `2 SALES`, `3 MARKETING`; SALES và MARKETING có `parent_id=1`.
- Pest: 13 test đạt, 33 assertions; riêng P2-01 có 6 test/15 assertions.
- Pint: 55 file đạt; PHPStan/Larastan level 5 không có lỗi.
- Lần migration ULID đầu tiên lỗi self-referencing foreign key trên PostgreSQL và đã rollback toàn bộ. Sau yêu cầu của chủ dự án, schema được đổi sang `BIGINT` tự tăng và migrate thành công; không có bảng ULID dở dang.

Checkpoint: dừng để kiểm thử đăng nhập, quản lý user/department và toàn bộ ma trận quyền trước P3.

## Giai đoạn 3 — Leads

Mục tiêu: hoàn thiện vòng đời Lead từ tiếp nhận đến chuyển đổi; import/export để lại P8.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P3-01 | Lead sources và tags | `feature/p3-01-lead-taxonomy` | P2-08 | Schema/model/seed nguồn lead, tag và quan hệ many-to-many |
| P3-02 | Lead schema và domain | `feature/p3-02-lead-domain` | P3-01 | BIGINT tự tăng, owner, department, trạng thái, contact fields, indexes và factory |
| P3-03 | Repository và bộ lọc Lead | `feature/p3-03-lead-query-filters` | P3-02 | Search, filter, sort, pagination và reusable data-scope query |
| P3-04 | Lead policy và visibility | `feature/p3-04-lead-authorization` | P2-04, P3-03 | Policy CRUD/assign/convert/restore đúng permission matrix |
| P3-05 | Danh sách Lead | `feature/p3-05-lead-list` | P3-03, P3-04 | Livewire table responsive, URL filters, bulk selection foundation và empty states |
| P3-06 | Form và chi tiết Lead | `feature/p3-06-lead-form-detail` | P3-05 | Create/edit/detail, validation, source/tags/owner và audit cơ bản |
| P3-07 | Assignment và status history | `feature/p3-07-lead-assignment-status` | P3-06 | Gán owner, chuyển trạng thái hợp lệ, lịch sử và event |
| P3-08 | Duplicate, soft delete và restore | `feature/p3-08-lead-duplicate-delete` | P3-06 | Phát hiện email/phone trùng, cảnh báo/merge decision, trash/restore |
| P3-09 | Conversion eligibility và contract | `feature/p3-09-conversion-contract` | P3-07, P3-08 | Rule đủ điều kiện, DTO/action contract, chống convert lặp và test contract; chưa tạo Opportunity |
| P3-10 | Lead test và checkpoint | `feature/p3-10-lead-checkpoint` | P3-01..P3-09 | Feature/policy/transaction tests và checklist vòng đời Lead |

Conversion transaction tạo Company/Contact/Opportunity được tích hợp ở P5-09 sau khi đủ schema đích; cách chia này loại bỏ phụ thuộc vòng giữa các giai đoạn.

## Giai đoạn 4 — Companies và Contacts

Mục tiêu: xây dựng hồ sơ khách hàng và quan hệ Company–Contact làm đích chuyển đổi Lead.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P4-01 | Company schema và domain | `feature/p4-01-company-domain` | P2-08 | Schema/model/factory, owner/department, industry, address và indexes |
| P4-02 | Contact schema và domain | `feature/p4-02-contact-domain` | P4-01 | Schema/model/factory, company relation, email/phone và primary contact |
| P4-03 | Company CRUD | `feature/p4-03-company-crud` | P4-01, P2-04 | List/filter/create/edit/detail, policy và soft delete |
| P4-04 | Contact CRUD và quan hệ | `feature/p4-04-contact-crud` | P4-02, P2-04 | List/form/detail, liên kết/chuyển company và policy |
| P4-05 | Duplicate handling | `feature/p4-05-customer-duplicates` | P4-03, P4-04 | Rule trùng Company/Contact, cảnh báo và merge-safe service foundation |
| P4-06 | Attachment và timeline foundation | `feature/p4-06-customer-files-timeline` | P4-03, P4-04 | Upload private qua MinIO, metadata DB, signed download và timeline shell |
| P4-07 | Customer authorization checkpoint | `feature/p4-07-customer-checkpoint` | P4-01..P4-06 | CRUD/file/policy tests và checklist Company–Contact |

Checkpoint: dừng để kiểm thử Company, Contact, file MinIO và visibility trước P5.

## Giai đoạn 5 — Pipelines và Opportunities

Mục tiêu: quản lý pipeline có cấu hình, opportunity lifecycle, Kanban và realtime.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P5-01 | Pipeline và stage schema | `feature/p5-01-pipeline-domain` | P2-08 | Multiple pipelines, ordered stages, probability, active/default constraints |
| P5-02 | Quản lý pipeline/stage | `feature/p5-02-pipeline-management` | P5-01 | CRUD/reorder stage, chống xóa stage đang dùng và policy quản trị |
| P5-03 | Opportunity schema và domain | `feature/p5-03-opportunity-domain` | P4-02, P5-01 | Company/contact/owner/stage, amount, probability, dates và indexes |
| P5-04 | Opportunity CRUD và weighted value | `feature/p5-04-opportunity-crud` | P5-03 | List/form/detail, decimal-safe calculation và authorization |
| P5-05 | Stage transition và history | `feature/p5-05-stage-transition-history` | P5-04 | Server-authoritative transition, version check, history và events |
| P5-06 | Opportunity Kanban | `feature/p5-06-opportunity-kanban` | P5-05 | Livewire + Alpine + SortableJS board, filters và optimistic rollback |
| P5-07 | Realtime private broadcast | `feature/p5-07-opportunity-realtime` | P5-06 | Private Reverb channel, authorized events, ordering/idempotency handling |
| P5-08 | Close won/lost workflow | `feature/p5-08-opportunity-close` | P5-05 | Won/lost reason, closed date, required validation và reopen rule |
| P5-09 | Lead conversion integration và checkpoint | `feature/p5-09-lead-conversion-checkpoint` | P3-09, P4-02, P5-01..P5-08 | Transaction tạo Company/Contact/Opportunity, idempotency/rollback, Kanban/realtime tests và checklist |

Checkpoint: dừng để kiểm thử Lead conversion hoàn chỉnh và Kanban đa người dùng trước P6.

## Giai đoạn 6 — Activities và Tasks

Mục tiêu: timeline tương tác, công việc, lịch và nhắc hạn cho các đối tượng CRM.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P6-01 | Activity polymorphic domain | `feature/p6-01-activity-domain` | P3-10, P4-07, P5-09 | Schema/model cho call/email/meeting/note gắn nhiều subject |
| P6-02 | Activity timeline CRUD | `feature/p6-02-activity-timeline` | P6-01 | Timeline reusable, create/edit/delete, visibility và audit |
| P6-03 | Task domain và CRUD | `feature/p6-03-task-crud` | P6-01 | Assignee, due date, priority, status, polymorphic subject và policy |
| P6-04 | Checklist và comments | `feature/p6-04-task-collaboration` | P6-03 | Checklist ordering, comments, mentions foundation và audit |
| P6-05 | Task list và Kanban | `feature/p6-05-task-views` | P6-03, P6-04 | My tasks/team tasks, filters, list/Kanban và bulk state changes |
| P6-06 | Calendar và reminders | `feature/p6-06-calendar-reminders` | P6-03 | Calendar view, scheduler job, idempotent reminders và overdue state |
| P6-07 | Activity/Task checkpoint | `feature/p6-07-activity-task-checkpoint` | P6-01..P6-06 | Timeline/task/calendar/queue tests và checklist nghiệm thu |

Checkpoint: dừng để kiểm thử scheduler, Horizon và nhắc việc trước P7.

## Giai đoạn 7 — Dashboard và Reports

Mục tiêu: số liệu thực, bộ lọc dùng chung và báo cáo bán hàng có kiểm soát data scope.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P7-01 | Metrics query services | `feature/p7-01-metrics-services` | P5-09, P6-07 | Query objects cho lead/opportunity/task, date range và data scope |
| P7-02 | Dashboard filters và KPI | `feature/p7-02-dashboard-kpi` | P7-01 | Date/department/user/pipeline filters, KPI cards và URL state |
| P7-03 | Funnel report | `feature/p7-03-funnel-report` | P7-01 | Lead conversion và pipeline funnel bằng Chart.js, empty/loading states |
| P7-04 | Revenue và forecast report | `feature/p7-04-revenue-forecast` | P7-01 | Won revenue, weighted forecast, period comparison và decimal accuracy |
| P7-05 | Sales performance report | `feature/p7-05-sales-performance` | P7-01, P6-07 | Owner/team performance, activity/task indicators và scoped drill-down |
| P7-06 | Report cache và checkpoint | `feature/p7-06-report-checkpoint` | P7-02..P7-05 | Cache invalidation, query/performance tests và checklist reports |

Checkpoint: dừng để đối chiếu số liệu dashboard/report với dữ liệu mẫu trước P8.

## Giai đoạn 8 — Import, Export, Notifications và Audit

Mục tiêu: xử lý dữ liệu lớn qua queue, notification center và audit hoàn chỉnh.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P8-01 | Import upload và preview | `feature/p8-01-import-upload-preview` | P3-10, P4-07 | CSV/XLSX upload MinIO, giới hạn file, sample preview và job record |
| P8-02 | Column mapping và validation | `feature/p8-02-import-mapping-validation` | P8-01 | Map cột, saved mapping, row validation và localized errors |
| P8-03 | Chunk queue và duplicate strategy | `feature/p8-03-import-queue-duplicates` | P8-02 | Chunked jobs, skip/update/create strategy, transaction và idempotency |
| P8-04 | Import progress và error file | `feature/p8-04-import-progress-errors` | P8-03 | Realtime progress, counters, retry/cancel và downloadable error file |
| P8-05 | Queued export và signed download | `feature/p8-05-export-signed-download` | P7-06 | Scoped CSV/XLSX export, queue, MinIO, expiry và signed URL |
| P8-06 | Notification center | `feature/p8-06-notification-center` | P6-06, P8-04 | Database/email/broadcast notifications, unread state và preferences |
| P8-07 | Audit hardening và checkpoint | `feature/p8-07-audit-checkpoint` | P8-01..P8-06 | Immutable audit coverage, sensitive-field masking, import/export tests |

Checkpoint: dừng để kiểm thử file lớn, queue retry, notifications và audit trước hardening P9.

## Giai đoạn 9 — Hoàn thiện, CI/CD và deployment

Mục tiêu: đưa hệ thống tới trạng thái sẵn sàng triển khai và vận hành.

| Mã | Feature | Branch đề xuất | Phụ thuộc | Kết quả cần đạt |
|---|---|---|---|---|
| P9-01 | Responsive và accessibility audit | `feature/p9-01-accessibility-responsive` | P8-07 | Mobile/tablet/desktop, keyboard, focus, contrast và screen-reader fixes |
| P9-02 | Security hardening | `feature/p9-02-security-hardening` | P8-07 | OWASP review, rate limit, headers, upload safety, secret/cookie policy |
| P9-03 | Performance và database indexes | `feature/p9-03-performance-indexes` | P8-07 | Explain plans, N+1 fixes, indexes, cache/queue tuning và load baseline |
| P9-04 | Complete regression suite | `feature/p9-04-regression-suite` | P9-01..P9-03 | Critical E2E/feature/policy/jobs tests và stable fixtures |
| P9-05 | CI workflow | `feature/p9-05-ci-workflow` | P9-04 | Composer/npm audit, Pest, Pint, PHPStan, Vite build và image build gates |
| P9-06 | Production image và deployment | `feature/p9-06-production-deployment` | P9-05 | Optimized image, environment/secrets, TLS/reverse proxy và deploy/rollback docs |
| P9-07 | Backup, monitoring và final checkpoint | `feature/p9-07-final-checkpoint` | P9-06 | Backup/restore drill, logs/metrics/alerts, final smoke test và handover |

Checkpoint cuối: chỉ đóng dự án sau khi restore backup thử thành công, CI xanh và smoke test production đạt.
