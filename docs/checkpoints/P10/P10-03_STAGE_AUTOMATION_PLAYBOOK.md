# P10-03 — Stage Automation & Sales Playbook

## Trạng thái

Hoàn tất triển khai — chờ chủ dự án kiểm thử thủ công.

## Requirement và phạm vi

- `REQ-STAGE-PLAYBOOK`, `REQ-PIPELINE`, `REQ-WORK`, `REQ-NOTIFY`, `REQ-AUDIT`, `REQ-AUTH-NAV`, `REQ-RBAC-MANAGE`.
- `DEC-001`, `DEC-002`, `DEC-005`, `DEC-011`, `DEC-012`.
- Không triển khai Workflow Builder tổng quát, AI tạo playbook hoặc gửi email ngoài hệ thống.

## Đã triển khai

- Quản lý Sales Playbook dạng draft/published/archived và tạo phiên bản mới từ bản đã phát hành.
- Bước playbook hỗ trợ hướng dẫn, qualification question, required field, checklist, Task, Reminder và tài liệu.
- Gán một phiên bản đã phát hành vào Pipeline Stage; phiên bản published là bất biến.
- Snapshot playbook và từng step được lưu theo Opportunity run, không phụ thuộc template sau này.
- Repeat policy `once`, `every_entry`, `manual`.
- Exit criteria cưỡng chế trong Opportunity transition, close won/lost và reopen.
- Opportunity editor không được thay stage trực tiếp để tránh bỏ qua history, exit criteria và automation.
- Queued job `ActivateOpportunityPlaybookJob` chạy sau commit trên queue `automation`, retry/backoff và idempotency key.
- Task/Reminder tự động dùng `TaskManagementService`, `TaskPolicy`, subject visibility và ngày làm việc.
- Progress, next action và câu trả lời qualification hiển thị trong Opportunity Detail.
- Kanban hiển thị tiến độ ngắn gọn theo từng Opportunity.
- Viewer chỉ đọc; Admin/Sales Manager quản lý; Sales thực hiện trong data scope.
- Audit bao phủ create/update/publish/archive/activate/complete step và Request ID nền tảng.
- Seeder chuẩn tạo đầy đủ sáu Playbook cho toàn bộ **Quy trình Bán hàng Standard**, chạy lặp an toàn và đồng bộ lại sáu stage assignment theo cấu hình chuẩn.
- Bản nháp lưu giai đoạn dự kiến; lưu, tải lại và phát hành không làm mất stage đã chọn.
- Phiên bản đã phát hành có hành động **Chỉnh sửa (tạo phiên bản mới)**; Opportunity có thể **Chạy lại** Playbook với modal xác nhận và vẫn giữ lịch sử lượt trước.
- Playbook đã lưu trữ cũng có thể tạo phiên bản nháp mới để chỉnh sửa và phát hành lại.
- Opportunity đang nằm sẵn trong Stage có assignment nhưng chưa có run được phép bấm **Khởi chạy playbook**, không cần chuyển Stage ra rồi quay lại.
- Khi chuyển Stage, chỉ khối Playbook được remount theo Stage mới và poll ngắn hạn cho đến khi queue `automation` tạo run; không còn phát event reload toàn màn hình.

## Schema

- `sales_playbooks`
- `sales_playbook_steps`
- `stage_playbook_assignments`
- `opportunity_playbook_runs`
- `opportunity_playbook_step_runs`

Toàn bộ khóa chính/FK dùng PostgreSQL auto-incrementing `BIGINT`. Các unique/index chính bảo vệ code-version, stage assignment, idempotency key và run-step position.

## File chính

- `app/Livewire/SalesPlaybooks/SalesPlaybookManager.php`
- `app/Livewire/Opportunities/OpportunityPlaybookProgress.php`
- `app/Services/SalesPlaybookManagementService.php`
- `app/Services/OpportunityPlaybookService.php`
- `app/Jobs/ActivateOpportunityPlaybookJob.php`
- `app/Repositories/Contracts/SalesPlaybookRepository.php`
- `app/Repositories/EloquentSalesPlaybookRepository.php`
- `resources/views/livewire/sales-playbooks/sales-playbook-manager.blade.php`
- `resources/views/livewire/opportunities/opportunity-playbook-progress.blade.php`
- `tests/Feature/P1003StagePlaybookTest.php`
- `database/seeders/StandardSalesPlaybookSeeder.php`
- `tests/Feature/StandardSalesPlaybookSeederTest.php`

## Migration và seed

```bash
docker compose exec app php artisan migrate --force
# Ba migration P10-03 thành công.

docker compose exec app php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
# Permission catalog được đồng bộ, role customized vẫn được bảo toàn.

docker compose exec app php artisan db:seed --class=Database\\Seeders\\StandardSalesPlaybookSeeder --force
# Sáu Playbook chuẩn và stage assignment được tạo idempotent.
```

## Kiểm tra tự động

```bash
docker compose exec app php artisan test tests/Feature/StandardSalesPlaybookSeederTest.php
# 3 tests đạt, 45 assertions.

docker compose exec app php artisan test tests/Feature/P1003StagePlaybookTest.php
# 15 tests đạt, 53 assertions.

docker compose exec app php artisan test tests/Feature/RouteNavigationAuthorizationTest.php
# 5 tests đạt, 59 assertions.

docker compose exec app ./vendor/bin/pint --test <file P10-03>
# 31 file đạt chuẩn.

docker compose exec app ./vendor/bin/phpstan analyse --no-progress <file P10-03>
# Không có lỗi.

docker compose exec app php artisan view:cache
# Blade compile thành công.
```

Database local đã được đối chiếu đủ `6/6` Stage active với số bước lần lượt `5, 7, 7, 7, 5, 5`.

## Kiểm thử thủ công

Hướng dẫn vận hành đầy đủ từ Admin đến Sales:
[`docs/guides/P10-03_SALES_PLAYBOOK_USER_GUIDE.md`](../../guides/P10-03_SALES_PLAYBOOK_USER_GUIDE.md)

Kịch bản Sales kiểm thử trọn pipeline từ đầu đến Won/Lost:
[`docs/guides/P10-03_STANDARD_PIPELINE_SALES_TEST.md`](../../guides/P10-03_STANDARD_PIPELINE_SALES_TEST.md)

1. Đăng nhập Admin/Sales Manager, mở **Quản trị → Sales Playbook**.
2. Tạo playbook nháp, thêm đủ loại step, chọn repeat policy và lưu.
3. Phát hành và gán playbook vào một Pipeline Stage.
4. Chuyển Opportunity vào stage; kiểm tra Horizon tạo run/Task/Reminder đúng một lần.
5. Kiểm tra deadline bỏ qua thứ Bảy và Chủ nhật.
6. Mở Opportunity Detail, nhập câu trả lời qualification và hoàn tất checklist.
7. Thử rời stage khi còn exit criteria; hệ thống phải chặn và nêu rõ bước còn thiếu.
8. Hoàn tất điều kiện rồi chuyển bằng Detail và Kanban.
9. Kiểm tra Won/Lost/Reopen cũng không bỏ qua exit criteria.
10. Với `every_entry`, quay lại stage tạo run mới; retry job không tạo Task trùng.
11. Với `manual`, không tự tạo run và chỉ chạy sau khi bấm **Khởi chạy playbook**.
12. Tạo phiên bản mới; Opportunity đang chạy vẫn hiển thị snapshot phiên bản cũ.
13. Viewer nhìn thấy progress nhưng không có quyền hoàn tất.
14. Kiểm tra Audit Log, Request ID, desktop/mobile và dark mode.
15. Lưu bản nháp có chọn Stage, tải lại editor và xác nhận Stage vẫn được giữ.
16. Bấm **Chạy lại**, xác nhận run mới về 0% và run cũ vẫn còn trong database/audit.

## Lưu ý dữ liệu local

`RolePermissionSeeder` không ghi đè role đã được tùy chỉnh qua P2-X04. Nếu role Sales local đã có customization marker từ trước, Admin cần cấp `sales-playbook.view` tại **Ma trận phân quyền** trước khi kiểm thử bằng tài khoản Sales.

## Ngoài phạm vi

- Calendar ngày nghỉ lễ tùy chỉnh.
- Workflow Builder kéo thả.
- AI sinh playbook.
- Email automation bên ngoài.
