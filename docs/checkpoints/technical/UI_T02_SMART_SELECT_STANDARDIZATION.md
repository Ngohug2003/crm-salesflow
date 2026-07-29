# UI-T02 — Chuẩn hóa Smart Select

## Trạng thái

Hoàn tất triển khai — chờ chủ dự án kiểm thử thủ công.

## Mục tiêu

- Loại bỏ việc lặp giao diện chọn Tỉnh/Thành phố và Phường/Xã trên các form CRM.
- Đồng nhất hành vi select trên toàn hệ thống.
- Select có dưới 5 lựa chọn tiếp tục dùng Flux UI để giữ giao diện gọn và nhẹ.
- Select có từ 5 lựa chọn thực tế trở lên tự động có ô tìm kiếm.

## Phạm vi đã hoàn thành

- Tạo `x-forms.smart-select` làm component điều phối select dùng chung.
- Tự đếm các lựa chọn thực tế, không tính dòng placeholder rỗng và lựa chọn bị disabled.
- Từ 5 lựa chọn trở lên dùng `williamug/searchable-select`; dưới 5 lựa chọn dùng `flux:select`.
- Giữ nguyên `wire:model`, validation, disabled/required state, placeholder và dark mode.
- Giữ nhóm `optgroup` khi select dài chuyển sang dạng tìm kiếm.
- Thay toàn bộ `flux:select` tại các màn Livewire hiện có bằng component dùng chung.
- Tạo `x-forms.administrative-unit-select` cho cặp Tỉnh/Thành phố → Phường/Xã phụ thuộc.
- Dùng component địa bàn chung tại form Lead, Company và Contact.
- Đồng bộ việc lưu nhanh người thực hiện trên màn chi tiết công việc sau khi select dài chuyển sang component tìm kiếm.
- Bổ sung `x-forms.modal-searchable-select` cho Flux Modal dùng native `<dialog>`; dropdown được giữ trong browser top-layer thay vì teleport ra `body`.
- Form tạo/chỉnh sửa Hồ sơ Nhân viên dùng component modal riêng cho Phòng ban, Tỉnh/Thành phố, Phường/Xã và Địa bàn làm việc.

## Quy tắc sử dụng

Với select thông thường, view chỉ khai báo một component và các option như trước:

```blade
<x-forms.smart-select wire:model="ownerId" label="Người phụ trách">
    <option value="">Chưa phân công</option>
    @foreach ($owners as $owner)
        <option value="{{ $owner->id }}">{{ $owner->name }}</option>
    @endforeach
</x-forms.smart-select>
```

Component tự quyết định có bật tìm kiếm hay không. Không gọi trực tiếp `flux:select` hoặc `x-searchable-select` tại các màn nghiệp vụ mới.

Với địa bàn Việt Nam:

```blade
<x-forms.administrative-unit-select
    province-model="provinceId"
    ward-model="wardId"
    :province-options="$provinceOptions"
    :ward-options="$wardOptions"
    :province-id="$provinceId"
/>
```

## Nghiệm thu tự động

Theo quy ước của chủ dự án, chỉ chạy file test mới của task:

```bash
docker compose exec app php artisan test tests/Feature/SmartSelectComponentTest.php
docker compose exec app php artisan test tests/Feature/SearchableSelectModalTest.php
```

Test xác nhận:

- 4 lựa chọn thực tế dùng Flux select.
- Đúng 5 lựa chọn thực tế chuyển sang searchable select.
- Component địa bàn dùng chung render đúng hai trường phụ thuộc.
- Các nhóm lựa chọn được giữ khi chuyển sang searchable select.
- Searchable select trong Flux Modal mở/đóng được và dropdown nằm đúng bên trong `<dialog>`.

## Checklist kiểm thử thủ công

1. Mở form Lead, Company và Contact; tìm kiếm Tỉnh/Thành phố rồi chọn Phường/Xã.
2. Đổi Tỉnh/Thành phố và kiểm tra danh sách Phường/Xã được tải lại.
3. Mở các màn danh sách Lead, Company, Contact, User và Task; kiểm tra select dài có tìm kiếm.
4. Kiểm tra select ít lựa chọn như trạng thái/độ ưu tiên vẫn là Flux select không có ô tìm kiếm.
5. Kiểm tra select Người phụ trách tại chi tiết công việc vẫn lưu ngay sau khi chọn.
6. Kiểm tra form chuyển đổi Lead vẫn hiển thị đúng nhóm gợi ý trùng và danh sách còn lại.
7. Kiểm tra dark mode, thao tác bàn phím và giao diện mobile.
