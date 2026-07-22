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
| 2 | Users, Departments, Roles, Permissions | Đang làm — P2-01 đến P2-07 hoàn tất | Chờ kiểm thử P2-07 |
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
| P2-02 | ✅ Quản lý phòng ban | `feature/p2-02-department-management` | P2-01 | Livewire list/create/edit/disable phòng ban, validation và test |
| P2-03 | ✅ Danh mục quyền và role seeder | `feature/p2-03-rbac-catalog-seeder` | P2-01 | `config/crm.php`, 5 role mặc định, permission idempotent và super-admin bypass |
| P2-04 | ✅ Data scope và policies nền tảng | `feature/p2-04-data-scope-policies` | P2-03 | Scope all/department/owned/read-only được cưỡng chế ở backend |
| P2-05 | ✅ Danh sách người dùng | `feature/p2-05-user-list` | P2-01, P2-04 | Tìm kiếm, lọc phòng ban/role/trạng thái, phân trang và URL state |
| P2-06 | ✅ Tạo và chỉnh sửa người dùng | `feature/p2-06-user-form` | P2-05 | Form tạo/sửa, active/locked state, email uniqueness và password rule |
| P2-07 | ✅ Gán phòng ban và role | `feature/p2-07-user-role-assignment` | P2-03, P2-06 | UI gán role/phòng ban, chống tự khóa admin cuối cùng, audit thay đổi |
| P2-07-01 | ✅ Audit log toàn hệ thống | `feature/p2-07-user-role-assignment` | P2-07 | Audit dùng chung, màn hình bảng/log, lọc và giới hạn truy cập cho Super Admin/Admin IT |
| P2-07-02 | ✅ Realtime Audit Log | `feature/p2-07-user-role-assignment` | P2-07-01 | Private Reverb channel, Echo client và Livewire tự cập nhật log mới |
| P2-08 | ✅ Authorization test và checkpoint | `feature/p2-08-authorization-checkpoint` | P2-02..P2-07-02 | Test đủ 5 role, navigation theo quyền, seed demo và checklist nghiệm thu |

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

### Nhật ký feature P2-02 — Quản lý phòng ban

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Màn hình Livewire tại `/settings/departments`, được bảo vệ bởi đăng nhập, email đã xác minh và tài khoản đang hoạt động.
- Danh sách phòng ban hiển thị phòng ban cha, số người dùng, `sort_order` và trạng thái; có tìm kiếm theo tên/mã và lọc active/inactive.
- Form dùng chung cho tạo và chỉnh sửa; chuẩn hóa mã sang chữ hoa, kiểm tra mã duy nhất và chỉ cho chọn phòng ban cha đang hoạt động.
- Chặn vòng lặp cây phòng ban khi chỉnh sửa (tự chọn chính nó hoặc một hậu duệ làm cha).
- Khi thay đổi trạng thái, chặn tắt phòng ban còn phòng ban con hoạt động và chặn bật phòng ban có cha đang tắt.
- Có xóa phòng ban qua hộp thoại xác nhận; chỉ cho xóa khi phòng ban không còn phòng ban con và không có người dùng để tránh làm mất liên kết tổ chức.
- Menu **Departments** được thêm vào sidebar. Phân quyền chi tiết theo role chưa nằm trong P2-02 và sẽ được bổ sung ở P2-03/P2-04.
- Mã P2-02 được tách theo Controller → Livewire/Form → Service → Repository; component Livewire chỉ giữ state và điều phối giao diện.
- Local Docker dùng bind mount, OPcache kiểm tra timestamp ở mọi request và Vite HMR ở cổng `5173`; sửa PHP/Blade/CSS/JS không cần build lại image.
- Navbar dùng Livewire Navigate với hover prefetch và transition ngắn; chuyển Dashboard ↔ Phòng ban không còn tải lại toàn bộ document.
- Audit dependency đã gỡ Axios, Concurrently, Laravel Sail và Laravel Pail; dự án dùng Livewire request, Docker Compose và Docker logs nên bốn package này không còn vai trò. Các package còn lại có usage hoặc nằm trong roadmap đã duyệt.
- Docker frontend build dùng `package-lock.json` + `npm ci`; Vite local chỉ cài lại dependency khi lock thay đổi.
- Vite entrypoint cài optional native dependency theo Alpine `musl`, tránh restart loop do binary Lightning CSS sai libc.
- 9 test feature bao phủ quyền truy cập nền tảng, tạo, validation, chỉnh sửa, chống cycle, quy tắc trạng thái, tìm kiếm, bộ lọc, xác nhận xóa và chặn xóa khi còn liên kết.

File chính:

- `app/Http/Controllers/DepartmentController.php`
- `app/Livewire/Departments/DepartmentManagement.php`
- `app/Livewire/Forms/DepartmentForm.php`
- `app/Services/DepartmentService.php`
- `app/Repositories/Contracts/DepartmentRepository.php`
- `app/Repositories/EloquentDepartmentRepository.php`
- `resources/views/livewire/departments/department-management.blade.php`
- `resources/views/departments/index.blade.php`
- `resources/views/layouts/app.blade.php`
- `routes/web.php`
- `tests/Feature/DepartmentManagementTest.php`
- `compose.override.yaml`, `docker/php/local.ini`, `vite.config.js`

Lệnh kiểm chứng:

```bash
docker compose up -d --force-recreate app nginx vite
docker compose ps app nginx vite
docker compose exec -T app php artisan test
docker compose exec -T app ./vendor/bin/pint --test
docker compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec -T --user node vite npm run build
curl -sS -o /dev/null -w '%{http_code}' http://127.0.0.1/settings/departments
curl -sS -o /dev/null -w '%{http_code}' http://127.0.0.1:5173/@vite/client
```

Kết quả cuối:

- Docker: `app`, `nginx` và `vite` đều ở trạng thái `healthy`; container PHP đọc trực tiếp source bind-mount.
- Pest: 22 test đạt, 92 assertions; riêng P2-02 có 9 test/59 assertions.
- Pint: 63 file đạt; PHPStan/Larastan không có lỗi.
- Composer audit và NPM audit không có lỗ hổng; production Docker build `app`/`nginx` đạt.
- Vite production build đạt; JavaScript giảm từ 46,31 kB xuống 0,40 kB, HMR endpoint trả `200` và `public/hot` trỏ tới `http://localhost:5173`.
- HTTP smoke test ứng dụng trả `302`, đúng với route yêu cầu đăng nhập khi gọi ở trạng thái guest.
- P2-02 không tạo migration mới và không thay đổi schema đã hoàn tất ở P2-01.

Checklist kiểm thử tay:

1. Đăng nhập rồi mở `http://localhost/settings/departments` hoặc chọn **Departments** trên sidebar.
2. Tạo một phòng ban, thử nhập mã chữ thường để xác nhận mã được chuẩn hóa thành chữ hoa.
3. Sửa tên, phòng ban cha, mô tả và `sort_order`; thử chọn một hậu duệ làm cha để xác nhận hệ thống chặn cycle.
4. Thử tìm kiếm theo tên/mã và lần lượt chọn bộ lọc active/inactive.
5. Thử tắt phòng ban còn phòng ban con active; sau đó tắt các phòng ban con trước và kiểm tra lại.
6. Thử xóa phòng ban trống và xác nhận bản ghi biến mất; thử xóa phòng ban còn phòng ban con hoặc người dùng để xác nhận hệ thống chặn.

Checkpoint P2-02: dừng tại đây để chủ dự án kiểm thử; chỉ bắt đầu P2-03 sau khi nhận xác nhận.

### Nhật ký feature P2-03 — Danh mục quyền và role seeder

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- `config/crm.php` là nguồn cấu hình duy nhất cho 45 permission đúng theo prompt, được chia thành 11 nhóm có nhãn tiếng Việt.
- Tạo 5 role mặc định: `super-admin`, `admin`, `sales-manager`, `sales`, `viewer`.
- Phạm vi mặc định lần lượt là `all`, `all`, `department`, `owned`, `read-only`; việc cưỡng chế scope ở repository/policy thuộc P2-04.
- `admin` có đủ 45 permission; `sales-manager` có 40; `sales` có 30; `viewer` có 8 quyền chỉ đọc.
- `super-admin` không được gán trực tiếp toàn bộ permission; `Gate::before` trả quyền cho mọi ability nhằm tránh phải đồng bộ lại role này khi module mới bổ sung permission.
- `RolePermissionSeeder` dùng `findOrCreate` và `syncPermissions`: chạy lặp lại không tạo dữ liệu trùng, đồng thời sửa lại permission của role nếu ma trận bị thay đổi thủ công.
- Seeder kiểm tra permission trùng, permission không tồn tại, role super-admin bị thiếu và data scope không hợp lệ trước khi ghi database.
- `DatabaseSeeder` gọi RBAC seeder và gán duy nhất role `super-admin` cho tài khoản demo `admin@salesflow.test` theo cách idempotent.
- Cập nhật `docs/permissions.md` với số quyền, data scope, ma trận module và lưu ý phải gọi `$user->can(...)`/Gate/Policy để super-admin bypass có hiệu lực.
- Xóa quy tắc `.gitignore` bỏ qua `tests/Feature`, bảo đảm các feature test mới được Git theo dõi.
- Không cài package mới, không dùng component UI mới và không tạo migration; `spatie/laravel-permission`, `HasRoles`, bảng RBAC đã có từ Giai đoạn 1.

File chính:

- `config/crm.php`
- `database/seeders/RolePermissionSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `app/Providers/AppServiceProvider.php`
- `tests/Feature/RbacCatalogSeederTest.php`
- `docs/permissions.md`
- `.gitignore`

Lệnh đã chạy:

```bash
docker compose exec -T app php artisan test tests/Feature/RbacCatalogSeederTest.php
docker compose exec -T app php artisan db:seed --force
docker compose exec -T -e XDG_CONFIG_HOME=/tmp app php artisan tinker --execute="dump(['roles' => Spatie\\Permission\\Models\\Role::count(), 'permissions' => Spatie\\Permission\\Models\\Permission::count(), 'admin_is_super_admin' => App\\Models\\User::where('email', 'admin@salesflow.test')->firstOrFail()->hasRole('super-admin')]);"
docker compose exec -T app php artisan test
docker compose exec -T app ./vendor/bin/pint --test
docker compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec -T --user node vite npm run build
```

Kết quả cuối:

- PostgreSQL local có đúng 5 role, 45 permission; tài khoản demo mang role `super-admin`.
- P2-03: 4 test đạt, 29 assertions; bao phủ catalog, idempotency, sửa ma trận khi seed lại, Gate bypass và tài khoản demo.
- Toàn dự án: 26 test đạt, 121 assertions.
- Pint: 66 file đạt; PHPStan/Larastan không có lỗi.
- Vite production build đạt; CSS 242,64 kB và JavaScript 0,40 kB trước gzip.
- Lần test đầu phát hiện PHP không cho spread mảng có string key vào `array_merge`; đã chuẩn hóa bằng `array_values` trước khi merge.
- Tinker mặc định không ghi được `/var/www/.config/psysh`; lệnh kiểm tra dùng `XDG_CONFIG_HOME=/tmp`, không ảnh hưởng runtime ứng dụng.

Checklist kiểm thử:

1. Chạy `docker compose exec app php artisan db:seed --force` lần thứ hai và xác nhận không báo duplicate.
2. Chạy `docker compose exec app php artisan test tests/Feature/RbacCatalogSeederTest.php` và xác nhận 4 test đạt.
3. Chạy `docker compose exec app php artisan permission:show` để xem 5 role và permission đã gán; cột `super-admin` hiển thị dấu chấm là đúng vì role này dùng Gate bypass thay vì gán trực tiếp.
4. Đăng nhập tài khoản demo và xác nhận Dashboard/Phòng ban vẫn truy cập bình thường; UI quản lý role chưa thuộc phạm vi P2-03.

Checkpoint P2-03: dừng tại đây để chủ dự án kiểm thử; chỉ bắt đầu P2-04 sau khi nhận xác nhận.

### Nhật ký feature P2-04 — Data scope và policies nền tảng

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo enum `DataScope` cho bốn phạm vi `all`, `department`, `owned`, `read-only`; khi một user có nhiều role, phạm vi rộng nhất được chọn theo thứ tự trên.
- `DataScopeResolver` đọc role/data scope từ `config/crm.php`; user không có role hợp lệ nhận mặc định an toàn `read-only`.
- `DataScopeService` cung cấp chung hai lớp bảo vệ: lọc query theo actor và xác nhận actor có được thao tác trên một record cụ thể hay không.
- Tạo `UserPolicy` kết hợp permission với record scope; `read-only` luôn chặn create/update/delete kể cả khi ai đó gán nhầm quyền ghi trực tiếp.
- Tạo `DepartmentPolicy`: admin/super-admin được quản lý; sales-manager có thể xem nhờ `users.view` nhưng không được sửa vì thiếu `settings.manage`; sales/viewer không được mở module.
- Controller và mọi Livewire action của Departments đều authorize ở backend. Nút/menu dùng `@can` để giao diện đúng quyền, nhưng policy vẫn là ranh giới bảo mật chính.
- Tạo `UserRepository`/`EloquentUserRepository`; query user được giới hạn `all`, cùng phòng ban hoặc chính user theo scope tương ứng.
- Tách đăng ký policy/Gate sang `AuthServiceProvider`, binding repository sang `RepositoryServiceProvider`; `AppServiceProvider` không còn gom các trách nhiệm này.
- Thêm named volume development cho `bootstrap/cache` để PHP trong container có thể làm mới provider cache khi code local thay đổi mà không gặp lỗi quyền ghi. Cấu hình production không bị thay đổi.
- Không cài package, không thêm component UI và không tạo migration mới.

File chính:

- `app/Enums/DataScope.php`
- `app/Services/Authorization/DataScopeResolver.php`
- `app/Services/Authorization/DataScopeService.php`
- `app/Policies/UserPolicy.php`
- `app/Policies/DepartmentPolicy.php`
- `app/Repositories/Contracts/UserRepository.php`
- `app/Repositories/EloquentUserRepository.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Providers/RepositoryServiceProvider.php`
- `app/Http/Controllers/DepartmentController.php`
- `app/Livewire/Departments/DepartmentManagement.php`
- `resources/views/livewire/departments/department-management.blade.php`
- `resources/views/layouts/app.blade.php`
- `bootstrap/providers.php`
- `compose.override.yaml`
- `tests/Feature/DataScopePolicyTest.php`
- `tests/Feature/DepartmentManagementTest.php`
- `docs/permissions.md`

Lệnh đã chạy:

```bash
docker compose exec -T app php artisan test tests/Feature/DepartmentManagementTest.php tests/Feature/RbacCatalogSeederTest.php tests/Feature/DataScopePolicyTest.php
docker compose up -d --force-recreate app horizon scheduler reverb nginx
docker compose exec -T app php artisan test
docker compose exec -T app ./vendor/bin/pint --test
docker compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec -T --user node vite npm run build
docker compose ps
```

Kết quả cuối:

- Riêng P2-04: 5 test đạt, 28 assertions; bao phủ scope resolver, query all/department/owned/read-only, UserPolicy, DepartmentPolicy, Livewire và menu.
- Nhóm kiểm thử P2-02 đến P2-04: 18 test đạt, 116 assertions.
- Toàn dự án: 31 test đạt, 149 assertions.
- Pint: 76 file đạt; PHPStan/Larastan không có lỗi.
- Vite production build đạt; CSS 242,64 kB và JavaScript 0,40 kB trước gzip.
- Tất cả service Docker đều chạy; các service có healthcheck đang ở trạng thái `healthy`.
- Lần chạy đầu phát hiện `bootstrap/cache/services.php` không ghi được do UID của bind mount; named volume development đã xử lý nguyên nhân này.
- Assertion giao diện ban đầu nhìn thấy chữ trong modal đóng sẵn; test được sửa để kiểm tra sự vắng mặt của action Livewire thay vì text HTML không hiển thị.

Checklist kiểm thử:

1. Chạy `docker compose exec app php artisan test tests/Feature/DataScopePolicyTest.php` và xác nhận 5 test/28 assertions đạt.
2. Đăng nhập `admin@salesflow.test`, mở **Departments** và thử tạo, sửa, bật/tắt, xóa để xác nhận super-admin vẫn thao tác đầy đủ.
3. Chạy `docker compose exec app php artisan test tests/Feature/DataScopePolicyTest.php --filter="allows managers"` để xác nhận manager xem được trang nhưng các action ghi bị backend trả `403`.
4. Chạy `docker compose exec app php artisan test tests/Feature/DataScopePolicyTest.php --filter="denies department pages"` để xác nhận viewer không mở được route/Livewire Departments và không thấy menu.
5. Khi sửa code provider ở local, chạy lại `docker compose ps` và tải trang; không còn lỗi quyền ghi `bootstrap/cache`.

Checkpoint P2-04: dừng tại đây để chủ dự án kiểm thử; chỉ bắt đầu P2-05 sau khi nhận xác nhận.

### Nhật ký feature P2-05 — Danh sách người dùng

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo route `/settings/users` và `UserController`; cả controller lẫn Livewire component đều authorize `UserPolicy::viewAny` ở backend.
- Thêm mục **Người dùng** vào sidebar theo `@can`; chuyển trang tiếp tục dùng `wire:navigate.hover` để không tải lại toàn bộ layout.
- Tách luồng theo trách nhiệm `Controller → Livewire → UserDirectoryService → UserRepository`; filter input được đóng gói trong `UserListFilters` thay vì truyền nhiều tham số rời.
- Repository luôn bắt đầu từ `visibleTo($actor)`, do đó data scope P2-04 được áp dụng trước tìm kiếm, bộ lọc và phân trang.
- Tìm kiếm không phân biệt hoa thường theo tên/email; lọc theo phòng ban, chưa gán phòng ban, role và trạng thái active/inactive.
- Tất cả filter dùng Livewire URL state: từ khóa là `q`, các filter còn lại là `department`, `role`, `status`; tải lại hoặc chia sẻ URL giữ nguyên trạng thái.
- Khi đổi filter, pagination tự trở về trang 1; kết quả được phân trang 15 user/trang và sắp xếp ổn định theo tên rồi ID.
- Query list eager-load đúng cột của `department` và `roles`, tránh N+1. Query lựa chọn phòng ban được tách riêng, không dùng `withCount` dư thừa từ màn hình quản lý phòng ban.
- Sales Manager chỉ thấy user và lựa chọn phòng ban trong chính phòng ban của mình; role thiếu `users.view` bị trả `403` và không thấy menu.
- Bảng hiển thị tên/email, phòng ban, role, trạng thái tài khoản và trạng thái xác thực email; empty state và nút xóa filter đã có.
- Thêm trang trợ giúp `/help/roles` dành cho mọi user đã đăng nhập: hiển thị role của chính user, data scope hiệu lực, số quyền và danh sách quyền của cả 5 role theo từng module. Role hiện tại được đánh dấu/mở sẵn; dữ liệu lấy trực tiếp từ `config/crm.php` qua `RoleGuideService` nên luôn đồng bộ với backend. Accordion dùng Alpine `x-collapse` đã được Livewire tích hợp sẵn, chỉ mở một role tại một thời điểm, không thêm package và không tạo request server khi đóng/mở.
- `DemoUserSeeder` tạo idempotent 20 tài khoản `demo01@salesflow.test` đến `demo20@salesflow.test`: 2 MANAGEMENT, 11 SALES, 7 MARKETING; gồm 2 admin, 2 sales-manager, 12 sales, 4 viewer, 3 inactive và 2 chưa xác thực. Mật khẩu demo dùng chung `SalesFlow@123`.
- Không triển khai tạo/sửa/gán role trong P2-05; các thao tác ghi lần lượt thuộc P2-06 và P2-07.
- Không cài package, không thêm migration và chỉ tái sử dụng component Flux UI đã có.

File chính:

- `app/Data/UserListFilters.php`
- `app/Http/Controllers/UserController.php`
- `app/Livewire/Users/UserList.php`
- `app/Services/UserDirectoryService.php`
- `app/Services/RoleGuideService.php`
- `app/Http/Controllers/RoleGuideController.php`
- `app/Repositories/Contracts/UserRepository.php`
- `app/Repositories/EloquentUserRepository.php`
- `app/Repositories/Contracts/DepartmentRepository.php`
- `app/Repositories/EloquentDepartmentRepository.php`
- `resources/views/users/index.blade.php`
- `resources/views/livewire/users/user-list.blade.php`
- `resources/views/help/roles.blade.php`
- `resources/views/layouts/app.blade.php`
- `routes/web.php`
- `database/seeders/DemoUserSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/UserListTest.php`
- `tests/Feature/DemoUserSeederTest.php`
- `tests/Feature/RoleGuideTest.php`

Lệnh đã chạy:

```bash
git switch -c feature/p2-05-user-list
docker compose exec -T app php artisan test tests/Feature/UserListTest.php
docker compose exec -T app php artisan test tests/Feature/RoleGuideTest.php
docker compose exec -T app ./vendor/bin/pint --test
docker compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec -T app php artisan test
docker compose exec -T --user node vite npm run build
docker compose ps
docker compose exec -T app php artisan db:seed --force
```

Kết quả cuối:

- Riêng P2-05: 6 test đạt, 40 assertions; bao phủ route/menu, từng filter, URL state, pagination, department scope và truy cập bị từ chối.
- Demo user seeder: 1 test đạt, 7 assertions; chạy hai lần vẫn giữ đúng 20 bản ghi, phân bổ/role/trạng thái đúng cấu hình.
- Trang hướng dẫn role: 4 test đạt, 24 assertions; guest bị chuyển login, Viewer truy cập được, role/scope/quyền hiện tại đúng, Alpine Accordion được render với accessibility attributes và số quyền catalog là 45/45/40/30/8.
- Toàn dự án: 42 test đạt, 220 assertions.
- Pint: 86 file đạt; PHPStan/Larastan không có lỗi.
- Vite production build đạt; CSS 245,20 kB và JavaScript 0,40 kB trước gzip.
- `app`, `horizon`, `mailpit`, `minio`, `nginx`, `postgres`, `redis`, `reverb`, `scheduler` và `vite` đều đang chạy; service có healthcheck đều `healthy`.
- Sau lần chạy đầu, query filter phòng ban được tối ưu từ query quản lý có `withCount` thành query options chỉ lấy `id`, `name`, `code`.
- PostgreSQL local đã được seed và kiểm tra thực tế: đúng 20 demo user, phân bổ MANAGEMENT 2, SALES 11, MARKETING 7.

Checklist kiểm thử:

1. Chạy `docker compose exec app php artisan test tests/Feature/UserListTest.php` và xác nhận 6 test/40 assertions đạt.
2. Đăng nhập `admin@salesflow.test`, chọn **Người dùng** trên sidebar hoặc mở `http://localhost/settings/users`.
3. Thử tìm theo một phần tên/email; lần lượt lọc phòng ban, role và trạng thái. Nhấn **Xóa bộ lọc** để trở về danh sách ban đầu.
4. Quan sát URL có các query `q`, `department`, `role`, `status`; tải lại trang và xác nhận filter vẫn được giữ.
5. Khi database có hơn 15 user, chuyển trang rồi đổi một filter và xác nhận danh sách quay về trang 1.
6. Chạy `docker compose exec app php artisan test tests/Feature/UserListTest.php --filter="limits managers"` để xác nhận Sales Manager không thấy user/phòng ban bên ngoài data scope.
7. Chạy `docker compose exec app php artisan test tests/Feature/UserListTest.php --filter="denies the user list"` để xác nhận viewer bị `403` và không thấy menu **Người dùng**.
8. Có thể đăng nhập một tài khoản active bất kỳ từ `demo01@salesflow.test` đến `demo20@salesflow.test` bằng mật khẩu `SalesFlow@123`; quyền truy cập phụ thuộc role đã được seed.
9. Chọn **Vai trò & quyền** trên sidebar, xác nhận khối đầu trang hiển thị đúng role/data scope của tài khoản đang đăng nhập và mở từng role để xem danh sách quyền.

Checkpoint P2-05: dừng tại đây để chủ dự án kiểm thử; chỉ bắt đầu P2-06 sau khi nhận xác nhận.

### Nhật ký feature P2-06 — Tạo và chỉnh sửa người dùng

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Thêm form tạo/chỉnh sửa ngay trên trang danh sách người dùng, gồm họ tên, email, phòng ban, active/locked state, mật khẩu và xác nhận mật khẩu.
- `UserForm` chịu trách nhiệm chuẩn hóa tên/email, validation và tạo payload; lỗi required/email/unique/department/password được hiển thị bằng tiếng Việt.
- Email được chuyển thành chữ thường trước khi kiểm tra unique; khi sửa, unique rule bỏ qua chính user hiện tại.
- Tạo mới bắt buộc mật khẩu và xác nhận; chỉnh sửa cho phép bỏ trống để giữ nguyên hash cũ. Nếu nhập mật khẩu mới, `Password::default()` và xác nhận khớp vẫn được áp dụng.
- Mật khẩu được hash qua cast `hashed` của model; form/service/repository không tự lưu plain text.
- User được tạo bởi Admin được đánh dấu email verified để có thể đăng nhập ngay; chưa được gán role vì role assignment thuộc P2-07.
- `is_active = false` là trạng thái locked và Fortify từ chối đăng nhập; bật lại cho phép tài khoản đăng nhập theo luồng hiện có.
- Chỉ phòng ban active được gán mới. Nếu user đang thuộc một phòng ban đã inactive, form vẫn cho giữ nguyên phòng ban đó khi sửa nhưng không cho gán user khác vào.
- `UserManagementService` bảo vệ department scope ở backend, ngăn writer có scope department/owned chuyển user ra ngoài phòng ban dù request bị sửa thủ công.
- Nút **Tạo người dùng** và **Sửa** dùng UserPolicy; action Livewire authorize lại nên role chỉ đọc bị trả `403` khi gọi trực tiếp.
- Repository được mở rộng với create/update; role hiện có được bảo toàn khi sửa thông tin tài khoản.
- Không thêm migration, không cài package và không thay đổi/gán role trong P2-06.

File chính:

- `app/Livewire/Forms/UserForm.php`
- `app/Livewire/Users/UserList.php`
- `app/Services/UserManagementService.php`
- `app/Services/UserDirectoryService.php`
- `app/Repositories/Contracts/UserRepository.php`
- `app/Repositories/EloquentUserRepository.php`
- `app/Repositories/Contracts/DepartmentRepository.php`
- `app/Repositories/EloquentDepartmentRepository.php`
- `app/Models/User.php`
- `resources/views/livewire/users/user-list.blade.php`
- `tests/Feature/UserFormTest.php`

Lệnh đã chạy:

```bash
docker compose exec -T app php artisan test tests/Feature/UserFormTest.php
docker compose exec -T app ./vendor/bin/pint --test
docker compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec -T app php artisan test
docker compose exec -T --user node vite npm run build
docker compose ps
```

Kết quả cuối:

- Riêng P2-06: 8 test đạt, 48 assertions; bao phủ UI/action, create, edit, email unique, password, inactive department, locked login, permissions và department scope.
- Toàn dự án: 50 test đạt, 268 assertions.
- Pint: 89 file đạt; PHPStan/Larastan không có lỗi.
- Vite production build đạt; CSS 245,24 kB và JavaScript 0,40 kB trước gzip.
- Tất cả service Docker đang chạy; service có healthcheck đều ở trạng thái `healthy`.
- Lần test đầu phát hiện Eloquent Builder không có `orWhereKey()`; query phòng ban đã dùng điều kiện `orWhere('id', ...)` tương thích và static analysis xác nhận sạch.

Checklist kiểm thử:

1. Chạy `docker compose exec app php artisan test tests/Feature/UserFormTest.php` và xác nhận 8 test/48 assertions đạt.
2. Đăng nhập `admin@salesflow.test`, mở **Người dùng**, nhấn **Tạo người dùng** và tạo một email mới với mật khẩu từ 8 ký tự.
3. Thử tạo lại cùng email ở dạng chữ hoa để xác nhận hệ thống chuẩn hóa và báo email đã được sử dụng.
4. Sửa user vừa tạo, đổi tên/phòng ban nhưng để trống hai ô mật khẩu; đăng nhập bằng mật khẩu cũ để xác nhận hash được giữ nguyên.
5. Đặt mật khẩu mới và đăng nhập lại; sau đó khóa chính user thử nghiệm (không khóa tài khoản admin) và xác nhận đăng nhập bị từ chối.
6. Mở khóa user thử nghiệm và xác nhận đăng nhập hoạt động trở lại.
7. Chạy `docker compose exec app php artisan test tests/Feature/UserFormTest.php --filter="rejects user form"` để xác nhận Sales Manager không có action ghi.
8. Xác nhận role hiện có không đổi sau khi sửa; việc gán role mới chỉ bắt đầu ở P2-07.

Checkpoint P2-06: dừng tại đây để chủ dự án kiểm thử; chỉ bắt đầu P2-07 sau khi nhận xác nhận.

### Nhật ký feature P2-07 — Gán phòng ban, role và audit thay đổi

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Mở rộng form người dùng với danh sách checkbox role; bắt buộc ít nhất một role và chỉ chấp nhận key tồn tại trong `config/crm.php`.
- Cho phép nhiều role trên cùng user; `DataScopeResolver` tiếp tục chọn scope rộng nhất theo thứ tự all/department/owned/read-only.
- Mỗi lựa chọn role hiển thị tên, mô tả và data scope. Super Admin thấy đủ 5 role; Admin thường chỉ thấy admin/sales-manager/sales/viewer.
- Admin thường không thể gán role `super-admin` bằng request sửa thủ công và không được sửa user đang mang role `super-admin`; quy tắc được cưỡng chế ở Policy và Service.
- Thêm `administrator_roles` vào catalog cấu hình, hiện gồm `super-admin` và `admin`.
- Một quản trị viên hoạt động được định nghĩa là `is_active = true` và mang ít nhất một role trong `administrator_roles`.
- `UserManagementService` chạy update user, sync role và audit trong cùng database transaction; query khóa các Admin active bằng `lockForUpdate` trước khi quyết định.
- Nếu khóa hoặc hạ quyền làm hệ thống không còn Admin active, toàn bộ transaction bị từ chối; thông tin user, role và audit đều không thay đổi.
- Khi còn một Admin active khác, hệ thống cho phép khóa/hạ quyền Admin mục tiêu.
- Mọi create/update user thành công ghi `activity_log`: actor, subject, event, old/new của tên, email, phòng ban, trạng thái và role.
- Audit không lưu mật khẩu plain text hoặc password hash; chỉ ghi cờ boolean `password_changed`.
- Role và phòng ban mới có hiệu lực ngay trên danh sách, UserPolicy và data scope backend.
- Không tạo migration mới vì bảng Spatie Permission và `activity_log` đã có; không cài package mới và chưa tạo UI xem audit log.

File chính:

- `config/crm.php`
- `app/Exceptions/UserOperationException.php`
- `app/Livewire/Forms/UserForm.php`
- `app/Livewire/Users/UserList.php`
- `app/Policies/UserPolicy.php`
- `app/Services/UserManagementService.php`
- `app/Services/UserDirectoryService.php`
- `app/Repositories/Contracts/UserRepository.php`
- `app/Repositories/EloquentUserRepository.php`
- `resources/views/livewire/users/user-list.blade.php`
- `tests/Feature/UserFormTest.php`
- `tests/Feature/UserRoleAssignmentTest.php`
- `docs/permissions.md`

Lệnh đã chạy:

```bash
docker compose exec -T app php artisan test tests/Feature/UserFormTest.php tests/Feature/UserRoleAssignmentTest.php
docker compose exec -T app ./vendor/bin/pint --test
docker compose exec -T app ./vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec -T app php artisan test
docker compose exec -T --user node vite npm run build
docker compose ps
```

Kết quả cuối:

- Riêng P2-07: 7 test đạt, 49 assertions; bao phủ role visibility, multi-role/data scope, privilege escalation, Admin cuối cùng, demotion hợp lệ, audit bảo mật và role không hợp lệ.
- Nhóm form P2-06/P2-07: 15 test đạt, 97 assertions.
- Toàn dự án: 57 test đạt, 317 assertions.
- Pint: 91 file đạt; PHPStan/Larastan không có lỗi.
- Vite production build đạt; CSS 245,40 kB và JavaScript 0,40 kB trước gzip.
- Tất cả service Docker đang chạy; service có healthcheck đều ở trạng thái `healthy`.

Checklist kiểm thử:

1. Chạy `docker compose exec app php artisan test tests/Feature/UserRoleAssignmentTest.php` và xác nhận 7 test/49 assertions đạt.
2. Đăng nhập `admin@salesflow.test`, mở **Người dùng**, sửa một user demo và chọn nhiều role; lưu rồi xác nhận các badge role/data scope thay đổi.
3. Dùng một tài khoản role `admin` thường và xác nhận form không có lựa chọn Super Admin, đồng thời không có nút sửa trên tài khoản `admin@salesflow.test` đang mang role `super-admin`.
4. Không khóa/hạ quyền toàn bộ Admin trên database local. Dùng test `--filter="last active administrator"` để kiểm tra an toàn quy tắc Admin cuối cùng trong database test cô lập.
5. Sau khi sửa role/phòng ban/trạng thái, mở bảng `activity_log` trong DBeaver; kiểm tra `causer_id`, `subject_id`, `event` và JSON `properties.old/new`.
6. Đổi mật khẩu một user thử nghiệm và xác nhận `properties` chỉ có `password_changed: true`, không chứa mật khẩu hoặc hash.
7. Chạy `docker compose exec app php artisan test tests/Feature/UserRoleAssignmentTest.php --filter="regular admins"` để xác nhận privilege escalation bị trả `403`.

Checkpoint P2-07: dừng tại đây để chủ dự án kiểm thử; chỉ bắt đầu P2-08 sau khi nhận xác nhận.

### Nhật ký feature P2-07-01 — Audit log toàn hệ thống

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Tạo `SystemAuditService` dùng chung, tự loại bỏ password, password hash, token, secret và remember token kể cả khi nằm trong mảng lồng nhau.
- Ghi actor, subject, event, mô tả, dữ liệu cũ/mới và metadata cho thao tác tạo/sửa user; tạo/sửa/bật-tắt/xóa phòng ban; đăng ký, đăng nhập, đăng xuất, cập nhật hồ sơ, đổi và đặt lại mật khẩu.
- Metadata đăng nhập có IP đã che bớt octet, user agent và cờ remember; không lưu credential hoặc session token.
- Toàn hệ thống dùng `Asia/Ho_Chi_Minh`: Laravel tạo timestamp theo giờ Việt Nam, session PostgreSQL cùng timezone và migration chuyển dữ liệu UTC cũ thêm 7 giờ; bộ lọc audit query trực tiếp theo ngày Việt Nam.
- Tạo trang `/settings/audit-logs` dạng chỉ đọc, có tìm kiếm, lọc phân hệ/sự kiện/người thực hiện/ngày và phân trang với URL state.
- Bộ lọc được gom thành panel responsive: trường tìm kiếm ưu tiên chiều rộng, label rõ ràng, ràng buộc khoảng ngày, badge tóm tắt điều kiện và nút đặt lại theo trạng thái.
- Có hai chế độ xem: **Bảng** để tra cứu và **Dòng log** nền console theo ảnh mẫu; chế độ xem được lưu vào query `view=log`.
- Mỗi bản ghi cho phép mở dữ liệu trước–sau và metadata; không có action sửa hoặc xóa audit trên giao diện.
- Chỉ `super-admin` hoặc user có role `admin`, permission `audit-logs.view` và thuộc department code `IT` được mở route, mount Livewire và nhìn thấy menu.
- Seeder tạo phòng `IT` và tài khoản local `it.admin@salesflow.test` / `SalesFlow@123`; Sales Manager không còn permission xem audit.
- Chỉ Super Admin được chuyển user vào/ra phòng IT hoặc sửa một thành viên IT, tránh Admin ngoài IT tự nâng quyền truy cập audit.
- Không cài package UI mới: Flux Free hiện tại không có Tabs, nên bộ chuyển dạng dùng Livewire + Tailwind nhẹ và accessible.

File chính:

- `app/Services/SystemAuditService.php`
- `app/Listeners/AuditAuthenticationActivity.php`
- `app/Policies/AuditLogPolicy.php`
- `app/Repositories/EloquentAuditLogRepository.php`
- `app/Services/AuditLogService.php`
- `app/Livewire/AuditLogs/AuditLogList.php`
- `resources/views/livewire/audit-logs/audit-log-list.blade.php`
- `resources/views/livewire/audit-logs/partials/details.blade.php`
- `tests/Feature/AuditLogAccessTest.php`
- `tests/Feature/AuthenticationAuditTest.php`

Kết quả xác minh:

- Toàn dự án: **63 test đạt**; gồm kiểm tra quyền xem audit, hai chế độ bảng/log, che dữ liệu nhạy cảm, login/logout, bảo vệ thành viên IT và biên lọc ngày theo giờ Việt Nam.
- Pint đạt trên 103 file; PHPStan/Larastan không có lỗi.
- Vite production build đạt; CSS 247,39 kB và JavaScript 0,40 kB trước gzip.
- Database local đã seed phòng `IT` và Admin IT; toàn bộ service Docker có healthcheck đều `healthy`.

Checklist kiểm thử thủ công:

1. Chạy `docker compose exec app php artisan db:seed --force`, đăng nhập `it.admin@salesflow.test` bằng mật khẩu `SalesFlow@123`.
2. Mở **Nhật ký kiểm toán**, chuyển giữa **Bảng** và **Dòng log**; tải lại URL có `?view=log` và xác nhận kiểu xem được giữ nguyên.
3. Thử tìm kiếm và lọc theo phân hệ, sự kiện, actor, ngày; mở một dòng để so sánh dữ liệu trước–sau.
4. Đăng nhập bằng Admin ngoài IT hoặc Sales Manager IT và xác nhận menu không xuất hiện, truy cập trực tiếp route trả `403`.
5. Tạo/sửa user hoặc phòng ban, đăng xuất/đăng nhập lại rồi xác nhận các sự kiện mới xuất hiện.
6. Kiểm tra JSON không có password, hash, token hoặc secret; IP đăng nhập được che một phần.

Checkpoint P2-07-01: dừng để chủ dự án kiểm thử audit trước khi bắt đầu P2-08.

### Nhật ký feature P2-07-02 — Realtime Audit Log

Trạng thái: **hoàn tất triển khai, chờ chủ dự án kiểm thử**.

Đã triển khai:

- Cài `laravel-echo` và `pusher-js`; Vite khởi tạo Echo trước Livewire và kết nối Reverb qua nginx.
- Tách địa chỉ server/browser: Laravel phát nội bộ tới `reverb:8080`, trình duyệt nối `localhost:80` qua WebSocket proxy `/app/`.
- `AuditLogCreated` chỉ broadcast `activity_id` trên private channel `audit-logs`; không gửi properties, old/new hoặc dữ liệu nhạy cảm.
- Event chạy sau database commit, phát ngay và dùng `ShouldRescue` để lỗi Reverb không làm hỏng thao tác nghiệp vụ hoặc đăng nhập.
- `AuditLogsChannel` dùng cùng Gate/Policy với route audit: chỉ Super Admin hoặc Admin IT active được subscribe.
- Livewire nghe `.audit.created`, giữ nguyên filter/tab, về trang mới nhất, tải lại dữ liệu và tô sáng activity vừa nhận.
- UI hiển thị trạng thái đang kết nối/đã kết nối/mất kết nối và thông báo khi nhận activity mới.
- WebSocket handshake qua nginx đạt `101 Switching Protocols`; thử broadcast Laravel → Reverb đạt.

File chính:

- `app/Events/AuditLogCreated.php`
- `app/Broadcasting/AuditLogsChannel.php`
- `app/Services/SystemAuditService.php`
- `app/Livewire/AuditLogs/AuditLogList.php`
- `resources/js/echo.js`
- `resources/js/app.js`
- `routes/channels.php`
- `tests/Feature/RealtimeAuditLogTest.php`

Kết quả xác minh:

- Realtime riêng: **5 test đạt, 10 assertions**; bao phủ payload tối thiểu, private channel Super Admin/Admin IT, từ chối Admin ngoài IT và Livewire refresh/highlight.
- Toàn dự án: **68 test đạt**; Pint đạt trên 106 file và PHPStan/Larastan không có lỗi.
- NPM cài 3 package dependency, audit 0 vulnerability; Vite production build đạt với CSS 249,71 kB và JavaScript 74,10 kB trước gzip.
- WebSocket nginx → Reverb trả `101 Switching Protocols`; Laravel → Reverb thử nghiệm trả `broadcast-ok`.

Checklist kiểm thử thủ công:

1. Mở `/settings/audit-logs` bằng `it.admin@salesflow.test`; xác nhận badge **Realtime đã kết nối**.
2. Giữ nguyên trang audit, mở trình duyệt ẩn danh và đăng nhập một tài khoản khác.
3. Xác nhận log **Đăng nhập hệ thống thành công** xuất hiện ngay, không reload trang và dòng mới được tô sáng.
4. Đăng xuất tài khoản thứ hai; xác nhận log đăng xuất tiếp tục xuất hiện realtime.
5. Thử filter phân hệ/sự kiện: activity mới chỉ xuất hiện nếu phù hợp filter hiện tại.
6. Admin ngoài IT không thấy trang audit và không được phép join private channel.

Checkpoint P2-07-02: dừng để chủ dự án kiểm thử bằng hai phiên trình duyệt trước khi bắt đầu P2-08.

### Nhật ký feature P2-08 — Authorization test và checkpoint

Trạng thái: **hoàn tất triển khai, chờ chủ dự án nghiệm thu Giai đoạn 2**.

Mục tiêu đã đạt:

- Có bộ test checkpoint độc lập chạy trên dữ liệu thật của `DatabaseSeeder`, bao phủ đủ 5 role: `super-admin`, `admin`, `sales-manager`, `sales` và `viewer`.
- Mỗi role được kiểm tra cả route trực tiếp và link navigation; việc ẩn menu không được dùng thay cho authorization backend.
- Ma trận Gate kiểm tra quyền tạo/sửa/xóa User và Department; Sales Manager chỉ đọc User/Department trong phạm vi phòng ban.
- Audit Log chỉ mở cho Super Admin hoặc Admin thuộc phòng IT; Admin ngoài IT bị chặn cả route, Gate và navigation.
- Tài khoản inactive bị đăng xuất khỏi phiên hiện có; tài khoản chưa xác minh email bị chuyển đến trang xác minh.
- Policy User được gia cố để Admin thường không thể sửa hoặc xóa Super Admin.
- Seeder có tài khoản active, verified cho đủ 5 role và có thể chạy lại không tạo dữ liệu trùng.

Tài khoản kiểm thử local (mật khẩu chung: `SalesFlow@123`):

| Role | Email | Phòng ban | Kỳ vọng chính |
|---|---|---|---|
| Super Admin | `admin@salesflow.test` | MANAGEMENT | Toàn quyền, xem Audit Log |
| Admin | `it.admin@salesflow.test` | IT | Quản lý User/Department, xem Audit Log |
| Sales Manager | `demo03@salesflow.test` | SALES | Xem User/Department trong phòng ban, không được ghi |
| Sales | `demo04@salesflow.test` | SALES | Không thấy trang quản trị User/Department/Audit |
| Viewer | `demo12@salesflow.test` | SALES | Chỉ đọc các module CRM được cấp quyền |

Trường hợp kiểm thử bổ sung:

- `demo01@salesflow.test`: Admin ngoài IT, không được xem Audit Log.
- `demo07@salesflow.test`: chưa xác minh email.
- `demo08@salesflow.test`: tài khoản đã khóa.

File chính:

- `tests/Feature/AuthorizationCheckpointTest.php`
- `app/Policies/UserPolicy.php`
- `docs/authorization-checkpoint.md`
- `docs/permissions.md`

Kết quả xác minh:

- Test checkpoint riêng: **15 test đạt, 115 assertions**.
- Nhóm tích hợp authorization Giai đoạn 2: **58 test đạt, 337 assertions**.
- Toàn dự án: **83 test đạt, 477 assertions**.
- Pint đạt trên 107 file; PHPStan/Larastan không có lỗi.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.
- Ma trận bao phủ route, navigation, Gate backend, data scope, trạng thái tài khoản, audit, realtime và seed demo.

Checklist nghiệm thu đầy đủ và lệnh thực hiện nằm tại `docs/authorization-checkpoint.md`.

Checkpoint P2-08: dừng tại đây để chủ dự án kiểm thử; chỉ bắt đầu P3-01 sau khi checkpoint Giai đoạn 2 được xác nhận.

## Giai đoạn 3 — Leads

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

Conversion transaction tạo Company/Contact/Opportunity được tích hợp ở P5-09 sau khi đủ schema đích; cách chia này loại bỏ phụ thuộc vòng giữa các giai đoạn.

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

Kết quả xác minh:

- Test riêng P3-01: **7 test đạt, 20 assertions**.
- Toàn dự án: **90 test đạt, 497 assertions**.
- Pint đạt trên 114 file; PHPStan/Larastan không có lỗi.
- Migration P3-01 đã chạy trên PostgreSQL; database local có đúng 8 nguồn Lead và 6 tag.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.
- Không cài thêm Composer/NPM package và không cần build lại image Docker.

Lệnh đã chạy:

```bash
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan test tests/Feature/LeadTaxonomyDomainTest.php
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose exec app php artisan migrate:status
docker compose ps
```

Checklist kiểm thử thủ công:

1. Chạy `docker compose exec app php artisan migrate --seed --force`.
2. Trong DBeaver, mở `lead_sources`; xác nhận có 8 bản ghi từ `WEBSITE` đến `MANUAL`, `sort_order` từ 10 đến 80.
3. Mở `tags`; xác nhận có 6 bản ghi và các slug như `moi`, `tiem-nang-cao`, `vip`.
4. Chạy lại seeder và xác nhận số lượng vẫn là 8 nguồn, 6 tag.
5. Chạy `docker compose exec app php artisan test tests/Feature/LeadTaxonomyDomainTest.php`.
6. Trên branch P3-01 chưa có `lead_tag`; từ P3-02 bảng này được tạo cùng `leads`.

Checkpoint P3-01: dừng tại đây để chủ dự án kiểm thử taxonomy trước khi bắt đầu P3-02.

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
- Chưa tạo `converted_company_id`/`converted_contact_id` vì bảng đích chỉ có ở Giai đoạn 4; P5-09 sẽ thêm foreign key và transaction chuyển đổi sau khi đủ schema.
- Chưa tạo dữ liệu Lead demo hoặc UI; các phần này thuộc feature danh sách/form tiếp theo.

File chính:

- `database/migrations/2026_07_22_220000_create_leads_table.php`
- `app/Models/Lead.php`
- `app/Enums/LeadStatus.php`, `app/Enums/LeadPriority.php`
- `database/factories/LeadFactory.php`
- `app/Models/LeadSource.php`, `app/Models/Tag.php`, `app/Models/User.php`, `app/Models/Department.php`
- `tests/Feature/LeadDomainTest.php`
- `docs/database.md`, `docs/architecture.md`

Kết quả xác minh:

- Test riêng P3-02: **9 test đạt, 50 assertions**.
- Nhóm domain P3-01/P3-02: **16 test đạt, 70 assertions**.
- Toàn dự án: **99 test đạt, 547 assertions**.
- Pint đạt trên 120 file; PHPStan/Larastan không có lỗi.
- Migration P3-02 đã chạy trên PostgreSQL; `leads`, `lead_tag` và 11 index tồn tại đúng thiết kế.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.
- Không cài thêm Composer/NPM package và không cần build lại image Docker.

Lệnh đã chạy:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan test tests/Feature/LeadTaxonomyDomainTest.php tests/Feature/LeadDomainTest.php
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose exec app php artisan migrate:status
docker compose ps
```

Checklist kiểm thử thủ công:

1. Chạy `docker compose exec app php artisan migrate --force` và xác nhận không còn migration pending.
2. Trong DBeaver, mở bảng `leads`; xác nhận ID là `bigint`, `estimated_value` là `numeric(15,2)` và có `deleted_at`.
3. Mở `lead_tag`; xác nhận có `lead_id`, `tag_id`, timestamps và unique constraint cho cặp Lead–Tag.
4. Kiểm tra Foreign Keys của `leads` đều dùng `ON DELETE SET NULL`; hai foreign key pivot dùng `ON DELETE CASCADE`.
5. Chạy `docker compose exec app php artisan test tests/Feature/LeadDomainTest.php` và xác nhận 9 test đạt.
6. Bảng `leads` chưa có dữ liệu demo là đúng phạm vi P3-02; dùng factory trong test cho đến khi feature danh sách/form bổ sung seed trực quan.

Checkpoint P3-02: dừng tại đây để chủ dự án kiểm thử schema/domain trước khi bắt đầu P3-03.

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
- P3-03 chỉ cưỡng chế phạm vi bản ghi. Permission mở route và hành động CRUD sẽ do `LeadPolicy` ở P3-04 cưỡng chế.
- Không tạo migration, không cài package và chưa tạo UI trong feature này.

File chính:

- `app/Data/LeadFilterData.php`
- `app/Repositories/Contracts/LeadRepository.php`
- `app/Repositories/EloquentLeadRepository.php`
- `app/Providers/RepositoryServiceProvider.php`
- `tests/Feature/LeadRepositoryTest.php`
- `docs/architecture.md`, `docs/permissions.md`

Kết quả xác minh:

- Test riêng P3-03: **7 test đạt, 27 assertions**.
- Nhóm domain/query P3-01..P3-03: **23 test đạt, 97 assertions**.
- Toàn dự án: **106 test đạt, 574 assertions**.
- Pint đạt trên 124 file; PHPStan/Larastan không có lỗi.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.

Lệnh đã chạy:

```bash
docker compose exec app php artisan test tests/Feature/LeadRepositoryTest.php
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose ps
```

Checklist kiểm thử:

1. Chạy `docker compose exec app php artisan test tests/Feature/LeadRepositoryTest.php` và xác nhận 7 test đạt.
2. Xác nhận test 4 data scope trả đúng Lead cho Admin, Sales Manager, Sales và Viewer.
3. Xác nhận search tìm được tên/email/điện thoại/công ty không phân biệt hoa thường và bỏ qua Lead đã soft delete.
4. Xác nhận filter kết hợp source/tag/status/priority/owner/department/ngày chỉ trả đúng Lead phù hợp.
5. Xác nhận test sort độc hại không làm thay đổi câu SQL và bảng `leads` vẫn tồn tại.
6. P3-03 chưa có trang web để kiểm thử thủ công; repository sẽ được nối vào Lead List ở P3-05 sau khi P3-04 khóa Policy.

Checkpoint P3-03: dừng tại đây để chủ dự án kiểm thử repository trước khi bắt đầu P3-04.

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
- Không thêm permission mới, migration, package, route hoặc UI trong feature này.

File chính:

- `app/Policies/LeadPolicy.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Services/Authorization/DataScopeService.php`
- `tests/Feature/LeadPolicyTest.php`
- `docs/permissions.md`, `docs/architecture.md`

Kết quả xác minh:

- Test riêng P3-04: **6 test đạt, 89 assertions**.
- Toàn dự án: **112 test đạt, 663 assertions**.
- Pint đạt trên 126 file; PHPStan/Larastan không có lỗi.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.

Lệnh đã chạy:

```bash
docker compose exec app php artisan test tests/Feature/LeadPolicyTest.php
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose ps
```

Checklist kiểm thử:

1. Chạy `docker compose exec app php artisan test tests/Feature/LeadPolicyTest.php` và xác nhận 6 test đạt.
2. Xác nhận Super Admin có toàn bộ ability gồm force delete; Admin có toàn bộ ability trừ force delete.
3. Xác nhận Sales Manager không view/update Lead phòng ban khác dù có `view-all/update-all`.
4. Xác nhận Sales chỉ view/update/delete/restore/convert Lead sở hữu và không assign.
5. Xác nhận Viewer được gán nhầm quyền ghi vẫn bị `DataScope::ReadOnly` chặn mutation.
6. Xác nhận Lead đã soft delete chỉ được restore bởi actor còn quyền trên owner/department gốc.
7. P3-04 chưa có trang Lead; route và navigation sẽ được nối với Policy ở P3-05.

Checkpoint P3-04: dừng tại đây để chủ dự án kiểm thử authorization trước khi bắt đầu P3-05.

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
- Không thêm migration hoặc package. P3-05 chỉ đọc dữ liệu; create/edit/detail và bulk action thực tế thuộc các feature tiếp theo.

Luồng chạy:

1. Trình duyệt gọi `GET /leads`; `LeadController` kiểm tra `LeadPolicy::viewAny` rồi render trang chứa Livewire component.
2. `LeadList` đọc và chuẩn hóa trạng thái URL, sau đó chuyển state sang `LeadDirectoryService`.
3. Service tạo `LeadFilterData` typed và gọi `LeadRepository`; repository áp dụng data scope trước filter, sort allowlist và pagination.
4. Livewire render bảng/card từ paginator đã eager-load; thao tác filter hoặc phân trang chỉ cập nhật component qua Livewire và giữ URL đồng bộ.
5. Bulk selection chỉ chấp nhận ID nằm trên trang dữ liệu hiện tại của actor, tạo nền an toàn cho action ở P3-07/P3-08.

File chính:

- `app/Http/Controllers/LeadController.php`
- `app/Livewire/Leads/LeadList.php`
- `app/Services/LeadDirectoryService.php`
- `resources/views/leads/index.blade.php`
- `resources/views/livewire/leads/lead-list.blade.php`
- `database/seeders/DemoLeadSeeder.php`
- `tests/Feature/LeadListTest.php`
- `tests/Feature/DemoLeadSeederTest.php`

Kết quả xác minh:

- Test riêng P3-05: **7 test đạt, 93 assertions**.
- Toàn dự án: **119 test đạt, 756 assertions**.
- PostgreSQL local có đúng **30 Lead demo**: Sales 18, Marketing 12 và 60 liên kết tag.
- Pint đạt trên 132 file; PHPStan/Larastan không có lỗi.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.

Lệnh đã chạy:

```bash
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan test tests/Feature/DemoLeadSeederTest.php tests/Feature/LeadListTest.php
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec vite npm run build
docker compose ps
```

Checklist kiểm thử thủ công:

1. Đăng nhập `admin@salesflow.test`, mở `/leads` và xác nhận thấy 30 Lead demo.
2. Thử kết hợp tìm kiếm, trạng thái, ưu tiên, nguồn và bộ lọc nâng cao; tải lại trang và xác nhận filter vẫn nằm trong URL.
3. Đăng nhập tài khoản Sales Manager, Sales và Viewer để xác nhận danh sách/owner/phòng ban chỉ hiện đúng data scope.
4. Thu nhỏ trình duyệt để xác nhận bảng chuyển thành card mobile mà không tràn ngang.
5. Chọn một số Lead, chọn toàn trang, chuyển trang hoặc đổi filter và xác nhận selection được xóa.
6. Chọn một bộ lọc không có kết quả rồi xóa bộ lọc; xác nhận hai empty state hiển thị đúng ngữ cảnh.
7. P3-05 chưa có nút tạo/sửa/xóa Lead; các thao tác này được triển khai từ P3-06 trở đi.

Checkpoint P3-05: dừng tại đây để chủ dự án kiểm thử danh sách Lead trước khi bắt đầu P3-06.

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
- Không thêm migration, package hoặc seed mới trong P3-06.

Luồng chạy:

1. `LeadController` kiểm tra route-level Policy; detail/edit tải Lead bằng `LeadRepository::findVisibleOrFail` nên ID ngoài data scope trả 404.
2. `LeadEditor` nạp option từ `LeadDirectoryService`; `LeadForm` giữ state và validate payload tại backend.
3. `LeadManagementService` kiểm tra lại Policy, giới hạn owner theo data scope và tự đồng bộ phòng ban.
4. Trong một transaction, `LeadRepository` create/update Lead, sync tag, sau đó `SystemAuditService` ghi audit old/new.
5. Lưu thành công chuyển về trang chi tiết bằng `wire:navigate` và hiển thị flash message.

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

Kết quả xác minh:

- Test riêng P3-06: **7 test đạt, 86 assertions**.
- Nhóm form/list/policy Lead: **19 test đạt, 260 assertions**.
- Toàn dự án: **126 test đạt, 842 assertions**.
- Pint đạt trên 136 file; PHPStan/Larastan không có lỗi; toàn bộ Blade template compile thành công.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.

Lệnh đã chạy:

```bash
docker compose exec app php artisan test tests/Feature/LeadFormDetailTest.php tests/Feature/LeadListTest.php tests/Feature/LeadPolicyTest.php
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec app php artisan view:cache
docker compose exec vite npm run build
docker compose ps
```

Checklist kiểm thử thủ công:

1. Đăng nhập Admin, mở `/leads`, tạo Lead có source, nhiều tag và owner; xác nhận chuyển về trang chi tiết và phòng ban khớp owner.
2. Mở Lead vừa tạo, sửa thông tin/tag rồi kiểm tra old/new ở màn Nhật ký hệ thống bằng tài khoản Super Admin hoặc Admin phòng IT; owner được chuyển qua workflow từ P3-07.
3. Thử email/website sai, giá trị âm hoặc bỏ họ tên; xác nhận form báo lỗi và không tạo Lead.
4. Đăng nhập Sales Manager; xác nhận dropdown owner chỉ có người trong cùng phòng ban và không mở được Lead phòng ban khác.
5. Đăng nhập Sales; tạo Lead và xác nhận hệ thống tự gán chính Sales, không hiển thị dropdown phân công.
6. Đăng nhập Viewer; xác nhận xem được chi tiết trong phạm vi nhưng không có nút tạo/sửa và URL create/edit trả 403.
7. Xác nhận form chưa cho chuyển status; tính năng chuyển trạng thái và lịch sử thuộc P3-07.

Checkpoint P3-06: dừng tại đây để chủ dự án kiểm thử form, trang chi tiết và audit Lead trước khi bắt đầu P3-07.

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
- Không thêm package hoặc seed mới.

Transition matrix:

```text
new         -> contacted, unqualified, lost
contacted   -> qualified, unqualified, lost
qualified   -> contacted, lost
unqualified -> new
lost        -> new
converted   -> (terminal, không có transition thủ công)
```

Luồng phân công:

1. `LeadWorkflow` validate input cơ bản và gọi `LeadAssignmentService`.
2. Service mở transaction, dùng scoped repository khóa Lead và kiểm tra Policy.
3. Owner mới phải đang hoạt động và nằm trong data scope; `department_id` được suy ra ở backend.
4. Repository cập nhật Lead, tạo assignment history; `SystemAuditService` ghi audit `assigned`.
5. Transaction commit xong mới dispatch `LeadAssigned`, sau đó UI điều hướng lại trang chi tiết.

Luồng chuyển trạng thái:

1. UI chỉ hiển thị các trạng thái kế tiếp do service trả về.
2. Backend vẫn parse enum và kiểm tra transition matrix sau khi khóa Lead; request sửa DOM không thể vượt qua.
3. Lead, status history và audit `status_changed` được ghi trong cùng transaction.
4. Commit xong mới dispatch `LeadStatusChanged`; timeline mới xuất hiện khi trang chi tiết reload bằng `wire:navigate`.

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

Kết quả xác minh:

- Test riêng P3-07: **8 test đạt, 68 assertions**.
- Toàn bộ nhóm Lead P3-01..P3-07: **50 test đạt, 425 assertions**.
- Toàn dự án: **134 test đạt, 910 assertions**.
- Migration P3-07 đã chạy trên PostgreSQL; hai bảng history và 7 index tồn tại đúng thiết kế.
- Pint đạt trên 149 file; PHPStan/Larastan không có lỗi; toàn bộ Blade template compile thành công.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.

Lệnh đã chạy:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan test tests/Feature/LeadWorkflowTest.php
docker compose exec app php artisan test tests/Feature/LeadWorkflowTest.php tests/Feature/LeadFormDetailTest.php tests/Feature/LeadListTest.php tests/Feature/LeadPolicyTest.php tests/Feature/LeadRepositoryTest.php tests/Feature/LeadDomainTest.php tests/Feature/LeadTaxonomyDomainTest.php
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec app php artisan view:cache
docker compose exec vite npm run build
docker compose ps
```

Checklist kiểm thử thủ công:

1. Tạo Lead mới có owner, mở chi tiết và xác nhận timeline có `Khởi tạo → Mới` cùng assignment ban đầu.
2. Đăng nhập Admin/Sales Manager, đổi owner kèm lý do; xác nhận owner, phòng ban, timeline và Audit Log cùng thay đổi.
3. Với Sales Manager, xác nhận dropdown không có user phòng ban khác; với Sales, xác nhận hoàn toàn không có form phân công.
4. Từ Lead `Mới`, chuyển sang `Đã liên hệ`, sau đó `Đủ điều kiện`; xác nhận chỉ trạng thái hợp lệ được hiển thị.
5. Thử chuyển sang `Đã mất` hoặc `Không đủ điều kiện` mà không nhập lý do; xác nhận backend từ chối.
6. Xác nhận không thể đặt `Đã chuyển đổi` thủ công và Lead converted không còn form chuyển trạng thái.
7. Đăng nhập Viewer và xác nhận chỉ thấy timeline, không có hai form mutation.
8. Dùng Super Admin/Admin phòng IT kiểm tra Audit Log có event `assigned` và `status_changed`, đúng actor/reason/old/new.

Checkpoint P3-07: dừng tại đây để chủ dự án kiểm thử assignment, transition và timeline trước khi bắt đầu P3-08.

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
- Không cung cấp force-delete trên UI; giới hạn Super Admin trong Policy vẫn giữ nguyên.
- Không thêm package hoặc seed mới.

Luồng duplicate:

1. `LeadForm` validate input; `LeadEditor` tạo contact signature.
2. `DuplicateLeadService` chuẩn hóa lần nữa ở backend và gọi scoped repository query trên active + trash.
3. Không có candidate thì lưu bình thường; có candidate thì dừng và mở cảnh báo, chưa ghi database.
4. `Vẫn lưu riêng` chỉ có hiệu lực khi signature không đổi; dữ liệu contact thay đổi sẽ tạo cảnh báo mới.

Luồng delete/restore:

1. Livewire component kiểm tra UI-level Policy, service tải lại và khóa Lead bằng scoped repository.
2. Delete/restore Policy được kiểm tra trong transaction; request ID ngoài scope trả 404 hoặc 403.
3. Lead state và audit cùng commit; tag/workflow history không bị xóa bởi Soft Delete.
4. Restore owner không hoạt động tự bỏ owner và append assignment history trong cùng transaction.

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

Kết quả xác minh:

- Test riêng P3-08: **8 test đạt, 58 assertions**.
- Toàn bộ nhóm Lead P3-01..P3-08: **58 test đạt, 483 assertions**.
- Toàn dự án: **142 test đạt, 968 assertions**.
- Migration/backfill P3-08 đã chạy trên PostgreSQL; 3 normalized index tồn tại đúng thiết kế.
- Pint đạt trên 156 file; PHPStan/Larastan không có lỗi; Blade template compile thành công.
- Vite production build đạt; toàn bộ 10 service Docker đang chạy và các service có healthcheck đều `healthy`.

Lệnh đã chạy:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan test tests/Feature/LeadDuplicateLifecycleTest.php
docker compose exec app php artisan test tests/Feature/LeadDuplicateLifecycleTest.php tests/Feature/LeadWorkflowTest.php tests/Feature/LeadFormDetailTest.php tests/Feature/LeadListTest.php tests/Feature/LeadPolicyTest.php tests/Feature/LeadRepositoryTest.php tests/Feature/LeadDomainTest.php tests/Feature/LeadTaxonomyDomainTest.php
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress
docker compose exec app php artisan view:cache
docker compose exec vite npm run build
docker compose ps
```

Checklist kiểm thử thủ công:

1. Tạo Lead với email khác hoa/thường so với Lead hiện có; xác nhận modal duplicate xuất hiện và database chưa lưu.
2. Nhập cùng số dưới dạng `+84`, `0084`, có dấu cách/gạch; xác nhận vẫn phát hiện trùng.
3. Trong cảnh báo, thử mở Lead hiện có, quay lại sửa và `Vẫn lưu riêng`; xác nhận chỉ lựa chọn cuối tạo bản ghi thứ hai.
4. Sau khi cảnh báo, đổi contact sang một contact trùng khác rồi xác nhận; hệ thống phải cảnh báo lại thay vì dùng xác nhận cũ.
5. Xóa một Lead có tag/timeline, mở `/leads/trash`, xác nhận Lead không còn ở danh sách chính nhưng lịch sử vẫn giữ.
6. Khôi phục Lead và kiểm tra tag, owner/phòng ban, workflow history cùng Audit Log `deleted`/`restored`.
7. Khóa owner rồi restore bằng Admin; xác nhận Lead được bỏ owner và timeline có assignment history tự động.
8. Đăng nhập Manager/Sales/Viewer để xác nhận data scope của trash và Viewer không có quyền truy cập.

Checkpoint P3-08: dừng tại đây để chủ dự án kiểm thử duplicate warning, soft delete và restore trước khi bắt đầu P3-09.

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
