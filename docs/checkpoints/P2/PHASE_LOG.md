# Giai đoạn 2 — Users, Departments, Roles, Permissions

## Kế hoạch và Nhật ký triển khai chi tiết

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
| P2-X04 | ✅ Cấu hình quyền theo Role | `feature/p2-x04-role-permission-management` | P2-03, P2-X01, P2-07-01 | Chỉnh Role–Permission trên UI, chống privilege escalation, audit và bảo toàn customization khi seed |

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
- `app/Repositories/Contracts/DepartmentRepository.php`
- `app/Repositories/EloquentDepartmentRepository.php`
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
