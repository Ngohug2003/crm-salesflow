# UI-T01 — Chuẩn hóa Data List và Data Table

## Trạng thái

- **Trạng thái:** Hoàn tất triển khai — chờ kiểm thử thủ công.
- **Branch thực tế:** `feature/p6-07-activity-task-checkpoint`.
- **Lý do dùng chung branch:** Chủ dự án xác nhận tự kiểm tra giao diện và commit cùng thay đổi hiện tại.
- **Requirement:** mục 5.1, 5.3, 5.4, 5.5 của `docs/requirements.md`.
- **Gap:** `GAP-UI-003`.

## Mục tiêu và quyết định

- Đồng nhất cấu trúc `tiêu đề → bộ lọc → loading → dữ liệu → empty state → pagination`.
- Dùng `flux:table` cho toàn bộ màn table; loại bỏ HTML `<table>` viết riêng tại Company và Contact.
- Giữ Opportunity, Pipeline và Task ở dạng feed vì mỗi dòng có nhiều metadata và hành động; chỉ chuẩn hóa shell và UI state.
- Dùng màu, spacing, label bộ lọc, cột thao tác, dark mode và responsive pattern nhất quán.
- Không thay đổi schema, repository, policy, data scope hoặc cài package mới.

## Thành phần dùng chung

- `resources/views/components/data-list/loading.blade.php`
- `resources/views/components/data-list/empty.blade.php`
- `resources/views/components/data-list/pagination.blade.php`
- Các class `data-list-*` trong `resources/css/app.css`

## Màn hình đã áp dụng

### Flux table

- Audit log.
- Company.
- Contact.
- Department.
- Lead.
- Lead trash.
- Session management.
- User management.

### List/feed

- Opportunity.
- Pipeline.
- Task.

## Xác minh

```text
php artisan view:cache
PASS — toàn bộ Blade biên dịch thành công.

php artisan test tests/Unit/DataListUiConsistencyTest.php
PASS — 11 tests, 72 assertions.

Nhóm feature test của các màn vừa chạm
PASS — Company, Contact, Department, Lead, Audit, Pipeline và Task.

npm run build
PASS — Vite build 730 modules.

pint --test (các file PHP vừa sửa)
PASS — 5 files.

phpstan analyse (các Livewire class vừa sửa)
PASS — không có lỗi.
```

Hai test không thuộc thay đổi giao diện vẫn đang đỏ khi chạy độc lập:

- `SessionManagementTest`: dashboard render gặp thiếu permission `tasks.view` trong fixture test.
- `OpportunityCrudTest` viewer delete: component chuyển exception authorization thành error state nên response là `200`, trong khi test cũ yêu cầu `403`.

Hai lỗi này không được sửa trong UI-T01 để tránh mở rộng sang authorization/test fixture ngoài phạm vi.

## Checklist nghiệm thu thủ công

1. Mở lần lượt User, Department, Lead, Lead trash, Company, Contact, Audit và Session.
2. Kiểm tra cùng kiểu tiêu đề danh sách, label bộ lọc, table header, hàng dữ liệu và cột thao tác.
3. Mở Opportunity, Pipeline và Task; kiểm tra shell/filter/loading/empty/pagination đồng nhất dù nội dung vẫn ở dạng feed.
4. Thử tìm kiếm có kết quả và không có kết quả; nút xóa bộ lọc phải đưa danh sách về mặc định.
5. Chuyển trang và quay lại; URL filter state hiện có không bị mất.
6. Kiểm tra desktop, mobile, light mode và dark mode.
