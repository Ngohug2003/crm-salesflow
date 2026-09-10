# P11-C01 — Public Product Catalog Client

## Mục tiêu

Tạo khu vực client mô phỏng để khách hàng xem sản phẩm nội thất từ chính catalog CRM, gồm danh sách và trang chi tiết, không lộ dữ liệu vận hành nội bộ.

## Đã triển khai

- `GET /catalog/products`: tìm kiếm tên/SKU, lọc danh mục, URL state và phân trang.
- `GET /catalog/products/{id}`: chi tiết sản phẩm, giá tham khảo, gallery, mô tả và Variant bán được.
- Layout client riêng, responsive và dùng Livewire navigation.
- Public image route có rate limit, chỉ phục vụ ảnh Product đang active và không discontinued.
- Repository có public query riêng; không áp Data Scope nội bộ và không eager-load Supplier/giá mua.

## Kiểm thử

```bash
docker compose exec -T app php artisan test tests/Feature/PublicProductCatalogTest.php
```

- PublicProductCatalogTest: 4 test, 18 assertions — pass.

## Checklist thủ công

1. Mở `http://localhost/catalog/products` khi chưa đăng nhập.
2. Tìm theo tên hoặc SKU, chọn danh mục và kiểm tra URL giữ bộ lọc.
3. Mở một sản phẩm để kiểm tra giá tham khảo, gallery và cấu hình Variant.
4. Xác nhận không có giá mua, nhà cung cấp hoặc thông tin owner/department.
5. Mở URL sản phẩm ngừng kinh doanh và xác nhận 404.
6. Mở trên mobile và kiểm tra layout, ảnh, nút liên hệ tư vấn.
7. Bấm từng thumbnail để đổi ảnh lớn; chọn từng Variant và xác nhận giá/thông số cập nhật không reload toàn trang.

## Ngoài phạm vi

- Giỏ hàng, checkout, đặt hàng, tài khoản khách hàng.
- API public và ứng dụng Next.js.
- Đồng bộ tồn kho, vận chuyển và thanh toán.
