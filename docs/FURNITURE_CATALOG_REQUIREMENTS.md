# SalesFlow Furniture Catalog — Requirements

## 1. Mục tiêu

P11-F01 xây dựng catalog nội thất văn phòng phục vụ Sale B2B từ tư vấn đến báo giá. Catalog nằm trong CRM; không phải hệ thống kho, sản xuất, vận chuyển hoặc bán lẻ.

## 2. Aggregate nghiệp vụ

```text
Category → Product → Variant
                   ↘ Supplier offer
```

- **Category**: phân cấp Bàn, Ghế, Tủ, Vách ngăn, Sofa, Phụ kiện và Dịch vụ.
- **Product**: mẫu sản phẩm chung, nhà sản xuất/brand, mô tả, đơn vị và trạng thái thương mại.
- **Variant**: SKU bán được với kích thước, vật liệu, màu, hoàn thiện, giá chuẩn, VAT, bảo hành và thời gian giao.
- **Supplier**: nhà cung cấp; một Variant có thể có nhiều Supplier offer với mã NCC, giá mua, lead time và một nguồn ưu tiên.
- **Product Media**: ảnh chung hoặc ảnh gắn Variant; một Product có gallery được sắp thứ tự và tối đa một ảnh chính.

## 3. Quy tắc

- PostgreSQL auto-increment `BIGINT`; tiền dùng `decimal(15,2)`.
- Product và Variant dùng soft delete; SKU Product/Variant unique.
- Mỗi Product chỉ có tối đa một Variant mặc định.
- Mỗi cặp Supplier–Variant unique; mỗi Variant chỉ có tối đa một Supplier ưu tiên.
- Không thay đổi hồi tố Opportunity/Quote snapshot khi giá hoặc cấu hình catalog đổi.
- Product list/detail/mutation áp ProductPolicy và Data Scope. Taxonomy/Supplier là master data dùng quyền Product tương ứng.
- Audit tạo/sửa Product, Variant và Supplier, gồm old/new và Request ID.
- Ảnh lưu trên filesystem disk cấu hình (S3/MinIO ở local/production); database chỉ lưu đường dẫn và metadata, không lưu base64/blob.
- Ảnh hỗ trợ JPG, PNG, WebP, tối đa 5 MB/tệp và 8 ảnh/lần tải. Đọc ảnh trong CRM phải qua ProductPolicy/Data Scope.
- Mỗi Product chỉ có tối đa một ảnh chính. Khi xóa ảnh chính, ảnh đầu tiên còn lại tự được chọn thay thế.
- Trạng thái thương mại chỉ phản ánh `active`, `made_to_order`, `discontinued`; không đại diện tồn kho.

## 4. UI nghiệm thu

- List tìm kiếm và lọc theo Category, Supplier, trạng thái; URL state giữ khi reload/back.
- Product create/edit là full page; detail hiển thị Variant và Supplier offer.
- Product list hiển thị thumbnail ảnh chính; detail hiển thị ảnh lớn và gallery. Người có quyền sửa được tải nhiều ảnh, preview, gắn Variant, đổi ảnh chính, đổi thứ tự và xóa.

## 6. Client catalog công khai — P11-C01

- Client dùng route `/catalog/products` và `/catalog/products/{id}` với layout riêng, không yêu cầu đăng nhập.
- Public query chỉ trả Product `is_active = true` và không có trạng thái `discontinued`; chỉ hiển thị Variant đang bán.
- Client được xem ảnh, mô tả, giá tham khảo, cấu hình, vật liệu, màu, kích thước, bảo hành và lead time.
- Client không được xem giá mua, Supplier, owner, department, audit, margin hoặc dữ liệu nội bộ khác.
- Public image route phải có rate limit và chỉ đọc media thuộc Product đang public; không expose hostname nội bộ của MinIO.
- P11-C01 chỉ là catalog mô phỏng; giỏ hàng, checkout, đặt hàng, API public và Next.js thuộc phase sau.
- Admin/Sales Manager có quyền tạo/sửa catalog; Sale chỉ xem và sử dụng theo permission.
- Form Variant có kích thước dài/rộng/cao (mm), vật liệu, màu, hoàn thiện, giá, VAT, bảo hành và lead time.
- Empty/loading/processing/validation, responsive và dark mode tuân theo `docs/requirements.md`.

## 5. Ngoài phạm vi

- BOQ theo khu vực, thiết kế/version, project triển khai, kho, BOM, serial, giao hàng và website public.
- Các phần này lần lượt thuộc P11-F02 trở đi.
