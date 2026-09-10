# P11-F01 — Furniture catalog, category, supplier và variant

## Kết quả

Trạng thái: **Hoàn tất triển khai — chờ kiểm thử thủ công**.

P11-F01 chuyển catalog thử nghiệm sang nghiệp vụ nội thất văn phòng B2B và thiết lập aggregate:

```text
Category → Product → Variant ↔ Supplier
```

- `Product` là mẫu hoặc dòng sản phẩm chung.
- `ProductVariant` là SKU Sale có thể bán, có kích thước, vật liệu, màu, hoàn thiện, giá, VAT, bảo hành và lead time riêng.
- `ProductSupplier` là nhà cung cấp.
- Bảng nối Supplier–Variant lưu mã phía nhà cung cấp, giá mua, lead time và nguồn ưu tiên.

## Phạm vi đã triển khai

- Schema `product_suppliers`, `product_variants`, `product_supplier_variant` dùng BIGINT tự tăng.
- Unique SKU cho Product/Variant và unique cặp Supplier–Variant.
- Service transaction bảo đảm tối đa một biến thể mặc định trên mỗi Product và một Supplier ưu tiên trên mỗi Variant.
- Danh sách Product có tìm kiếm, lọc Category/Supplier/trạng thái và URL state.
- Full-page tạo/sửa Product; trang chi tiết quản lý Variant và Supplier offer bằng modal ngắn.
- Màn hình quản trị Category và Supplier.
- ProductPolicy/Data Scope bảo vệ Product; thao tác Variant/Supplier offer kiểm tra quyền update trên Product cha.
- Audit Product, Category, Supplier, Variant và Supplier offer.
- Seed nội thất gồm 8 danh mục, 4 hãng, 4 nhà cung cấp, 22 mẫu sản phẩm và 26 biến thể.
- Ba bảng giá nội thất tạo 66 dòng giá theo phân khúc tiêu chuẩn, dự án và khách hàng chiến lược.
- FullDemoSeeder lấy Product/PriceBook thật để tạo Opportunity items và sao chép snapshot sang Quote items.
- ReportAnalyticsDemoSeeder tạo thêm 100 kịch bản nội thất trên 24 tháng; mọi Opportunity đều có dòng sản phẩm hợp lệ.
- DatabaseSeeder gọi toàn bộ chuỗi catalog → demo CRM → analytics; các standalone Opportunity demo cũng đã đổi sang ngữ cảnh nội thất.
- Seeder dọn cứng catalog demo cũ (`SF-ENTERPRISE`, Laptop/MacBook/Desktop/Màn hình và các hãng máy tính); snapshot Opportunity lịch sử được giữ với khóa Product đặt null theo FK.
- Sidebar tiếp tục dùng quyền `products.view`; route cấu hình dùng `products.update`.
- Sidebar tách nhóm **Sản phẩm & Bảng giá** khỏi nhóm CRM nhưng giữ nguyên permission boundary.
- Select **Danh mục cha** trong modal dùng component modal-safe để dropdown không bị Flux dialog che hoặc cắt.
- Select **Sản phẩm từ catalog** tại chi tiết Cơ hội dùng component modal-safe, hỗ trợ tìm theo SKU/tên và cập nhật giá/VAT ngay lần chọn đầu.
- Product Media lưu ảnh trên filesystem disk cấu hình và metadata trong `product_media`; hỗ trợ ảnh chính duy nhất, gallery có thứ tự và liên kết Variant tùy chọn.
- Product list hiển thị thumbnail; detail cho phép upload nhiều ảnh có preview, đổi ảnh chính, di chuyển thứ tự và xóa. Route đọc ảnh áp ProductPolicy/Data Scope.

## Quyết định kỹ thuật

- Không tạo module tree mới; giữ `Models`, `Repositories`, `Services`, `Policies`, `Livewire`.
- Giữ migration taxonomy hiện có `2026_08_17_000001_add_computer_catalog_taxonomy.php` vì migration đã được áp dụng; tên file cũ không ảnh hưởng domain và không được đổi hồi tố.
- Product Price Book hiện tại vẫn tham chiếu Product cha. Việc chọn Variant, BOQ và snapshot cấu hình vào Opportunity/Quote thuộc P11-F02.
- Không dùng trạng thái catalog để thay cho tồn kho.

## File chính

- `app/Models/ProductVariant.php`
- `app/Models/ProductSupplier.php`
- `app/Models/ProductMedia.php`
- `app/Http/Controllers/ProductMediaController.php`
- `app/Services/FurnitureCatalogService.php`
- `app/Repositories/EloquentProductCatalogRepository.php`
- `app/Livewire/Products/ProductList.php`
- `app/Livewire/Products/ProductEditor.php`
- `app/Livewire/Products/ProductDetail.php`
- `app/Livewire/Products/ProductCatalogSettings.php`
- `database/migrations/2026_08_18_000001_create_furniture_variants_and_suppliers.php`
- `database/seeders/FurnitureCatalogSeeder.php`
- `tests/Feature/FurnitureCatalogTest.php`
- `tests/Feature/ProductMediaTest.php`

## Quality gates đã chạy

```bash
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan db:seed --class=FurnitureCatalogSeeder --force
docker compose exec -T app php artisan test tests/Feature/FurnitureCatalogTest.php
docker compose exec -T app php artisan test tests/Feature/FurnitureDemoDataSeederTest.php
docker compose exec -T app php artisan test tests/Feature/RouteNavigationAuthorizationTest.php
docker compose exec -T app php artisan test tests/Feature/SearchableSelectModalTest.php tests/Feature/OpportunityLineItemsTest.php
docker compose exec -T app php artisan test tests/Feature/ProductMediaTest.php
docker compose exec -T app vendor/bin/phpstan analyse --no-progress <P11-F01 paths>
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan migrate:fresh --seed --force
```

Kết quả:

- FurnitureCatalogTest: 4 test, 17 assertion — pass.
- FurnitureDemoDataSeederTest: 1 test, 20 assertion — pass.
- RouteNavigationAuthorizationTest: 5 test, 59 assertion — pass.
- SearchableSelectModalTest + OpportunityLineItemsTest: 5 test, 33 assertion — pass.
- ProductMediaTest: 3 test, 17 assertion — pass.
- PHPStan phạm vi P11-F01 — không có lỗi.
- Pint phạm vi P11-F01 — pass.
- Blade view cache — pass.
- Database local đã reset thành công; dữ liệu cuối gồm 130 Lead, 120 Opportunity, 159 Opportunity items, 14 Quote và 33 Quote items.

## Checklist kiểm thử thủ công

1. Đăng nhập Admin/Super Admin, mở **Sản phẩm**.
2. Kiểm tra bộ lọc danh mục, nhà cung cấp, trạng thái và thao tác back/reload.
3. Mở **Danh mục & nhà cung cấp**, tạo/sửa một Category và một Supplier.
4. Tạo mẫu sản phẩm mới; kiểm tra định dạng tiền và validation.
5. Tại trang chi tiết, tạo hai Variant; lần lượt đặt cả hai là mặc định và xác nhận chỉ Variant cuối có badge **Mặc định**.
6. Gắn hai Supplier vào một Variant; đổi Supplier ưu tiên và xác nhận chỉ còn một badge **Ưu tiên**.
7. Đăng nhập Sale chỉ có `products.view`; xác nhận xem được catalog nhưng không thấy nút tạo/sửa/cấu hình.
8. Kiểm tra dark mode, mobile, modal không bị che và không reload toàn trang khi lưu modal.
9. Xác nhận sidebar hiển thị nhóm **Sản phẩm & Bảng giá** riêng và active state đúng.
10. Kiểm tra ba bảng giá đều có 22 sản phẩm và Opportunity/Quote không còn SKU phần mềm hoặc máy tính.
11. Kiểm tra Dashboard/Reports theo tháng, quý và năm có dữ liệu từ tháng 08/2024 đến tháng 07/2026.
12. Mở một Cơ hội, bấm **Thêm sản phẩm**, mở **Sản phẩm từ catalog**, tìm theo SKU/tên và xác nhận tên, SKU, đơn giá, VAT được điền ngay sau lần chọn đầu.
13. Mở chi tiết Product, tải đồng thời nhiều ảnh, chọn ảnh chính, đổi thứ tự và xóa ảnh; xác nhận thumbnail ở danh sách cập nhật đúng và ảnh không bị mất khi reload.

## Ngoài phạm vi

- Chọn Variant/BOQ trên Opportunity.
- Thiết kế, revision và file dự án.
- Project triển khai sau Won, nghiệm thu, bảo hành.
- Kho, tồn, mua hàng và website public.
