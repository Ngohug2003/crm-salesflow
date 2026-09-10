# Furniture Catalog Phase Log

| Feature | Branch | Trạng thái |
|---|---|---|
| P11-F01 — Furniture catalog, category, supplier và variant | `feature/p11-f01-furniture-catalog` | Hoàn tất triển khai — chờ kiểm thử thủ công |
| P11-F02 — Opportunity BOQ và snapshot | `feature/p11-f02-opportunity-boq` | Chưa bắt đầu |
| P11-F03 — Thiết kế, file và quote revision | `feature/p11-f03-design-quote-revision` | Chưa bắt đầu |
| P11-F04 — Project triển khai sau Won | `feature/p11-f04-delivery-project` | Chưa bắt đầu |
| P11-F05 — Nghiệm thu và bảo hành | `feature/p11-f05-acceptance-warranty` | Chưa bắt đầu |
| P11-C01 — Public Product Catalog Client | branch hiện tại | Hoàn tất triển khai — chờ kiểm thử thủ công |

## P11-F01 checkpoint

- Requirement: `docs/FURNITURE_CATALOG_REQUIREMENTS.md`, `REQ-AUTH-NAV`, `REQ-AUDIT`, DEC-001/004/007/011.
- Tái sử dụng Product/Category/Brand chung; migration nối tiếp bổ sung Supplier và Variant.
- Ngoài phạm vi: BOQ, Project, tồn kho và public client.
- Checkpoint chi tiết: `docs/checkpoints/P11/P11-F01_FURNITURE_CATALOG.md`.

## P11-C01 checkpoint

- Route public: `/catalog/products`, `/catalog/products/{id}` và route ảnh public tương ứng.
- Chỉ hiển thị Product đang hoạt động, không ngừng kinh doanh; không lộ giá nhập, nhà cung cấp, owner hoặc dữ liệu nội bộ.
- Dùng Product, Category, Variant và Product Media thật từ CRM; không tạo database catalog thứ hai.
- Chưa bao gồm giỏ hàng, checkout, đặt hàng, tài khoản khách hàng hoặc API Next.js.
- Checkpoint chi tiết: `docs/checkpoints/P11/P11-C01_PUBLIC_PRODUCT_CATALOG.md`.
