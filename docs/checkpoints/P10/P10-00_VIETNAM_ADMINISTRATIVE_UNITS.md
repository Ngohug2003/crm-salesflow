# P10-00 — Danh mục đơn vị hành chính Việt Nam

## Trạng thái

Hoàn tất triển khai — chờ chủ dự án kiểm thử thủ công.

## Mục tiêu

- Lưu danh mục hành chính Việt Nam theo cấu trúc hai cấp Tỉnh/Thành phố → Phường/Xã.
- Thay trường địa chỉ nhập tự do bằng select phụ thuộc trên form Lead, Company và Contact.
- Chuẩn hóa dữ liệu địa bàn phục vụ tìm kiếm, báo cáo và Lead routing về sau.
- Không làm mất dữ liệu địa chỉ text đã tồn tại.

## Phạm vi đã hoàn thành

- Tạo bảng `provinces`, `wards` bằng khóa chính PostgreSQL `BIGINT` tự tăng.
- Thêm khóa ngoại nullable `province_id`, `ward_id` cho `leads`, `companies`, `contacts`.
- Lưu bản sao dữ liệu nguồn tại `database/data/vietnam_administrative_units.json`.
- Seeder/import idempotent cho 34 Tỉnh/Thành phố và 3.321 Phường/Xã.
- Backfill an toàn dữ liệu cũ; bỏ qua tên Phường/Xã bị mơ hồ.
- Repository và Service dùng chung cho danh mục địa bàn.
- Backend kiểm tra Phường/Xã thuộc đúng Tỉnh/Thành phố.
- Đồng bộ cột text `province`, `city`, `country` để tương thích dữ liệu cũ.
- Form tạo/sửa Lead, Company, Contact dùng searchable select Tỉnh/Thành phố và Phường/Xã phụ thuộc.
- Cặp trường Tỉnh/Thành phố → Phường/Xã được đóng gói thành component dùng chung `x-forms.administrative-unit-select`.
- Dùng `williamug/searchable-select` v3 để có searchable select tương thích Laravel 12, Livewire 3/4 và Tailwind 4 mà không phải cài thêm một UI theme đầy đủ.
- Component hỗ trợ tìm kiếm phía client, bàn phím, dark mode, trạng thái disabled, validation và dependent dropdown.
- Select nghiệp vụ toàn hệ thống dùng `x-forms.smart-select`: từ 5 lựa chọn thực tế trở lên có tìm kiếm, dưới 5 lựa chọn giữ Flux UI nguyên bản.
- Màn chi tiết ưu tiên hiển thị quan hệ chuẩn, có fallback về text cũ.

## Lệnh vận hành

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan administrative-units:import
```

Muốn chỉ nhập danh mục mà không backfill dữ liệu CRM cũ:

```bash
docker compose exec app php artisan administrative-units:import --no-backfill
```

## Nghiệm thu tự động

Theo quy ước của chủ dự án, feature này chỉ chạy file test mới:

```bash
docker compose exec app php artisan test tests/Feature/VietnamAdministrativeUnitTest.php
```

File test xác nhận:

- Import đủ 34 Tỉnh/Thành phố và 3.321 Phường/Xã, chạy lặp không nhân bản.
- Command import hoạt động.
- Lead từ chối Phường/Xã không thuộc Tỉnh/Thành phố đã chọn.
- Lead, Company và Contact lưu đúng khóa ngoại lẫn text tương thích.

## Checklist kiểm thử thủ công

1. Mở form tạo Lead, tìm Tỉnh/Thành phố theo tên rồi xác nhận danh sách Phường/Xã thay đổi theo tỉnh.
2. Đổi Tỉnh/Thành phố và xác nhận Phường/Xã cũ được xóa.
3. Lưu Lead rồi mở màn chi tiết/chỉnh sửa để kiểm tra dữ liệu được giữ đúng.
4. Lặp lại luồng trên với Company và Contact.
5. Mở một bản ghi cũ chưa map được để kiểm tra hệ thống vẫn hiển thị địa chỉ text và cảnh báo cần chuẩn hóa.

## Ghi chú dữ liệu

- File nguồn có một số `code_name` Phường/Xã trùng nhau trong cùng Tỉnh/Thành phố sau sáp nhập. Vì vậy `code` chính thức là khóa duy nhất; `province_id + code_name` chỉ được đánh index, không đặt unique.
- Backfill không đoán dữ liệu mơ hồ nhằm tránh gán sai địa bàn.
