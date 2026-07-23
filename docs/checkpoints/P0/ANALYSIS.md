# Giai đoạn 0 — Phân tích kiến trúc và dữ liệu

## Mục tiêu

- Phân tích nghiệp vụ và module map.
- Mô tả business flow bằng Mermaid.
- Thiết kế ERD ở mức domain.
- Xây permission matrix.
- Đưa ra timeline, man-day, rủi ro và tiêu chí MVP.

## File đã tạo

- `docs/architecture.md`: business analysis, module map, business flow, ERD, estimate 90 man-day, risk và MVP criteria.
- `docs/permissions.md`: ma trận quyền cho 5 role mặc định.
- `docs/database.md`: định hướng PostgreSQL và khóa chính `BIGINT` tự tăng.
- `docs/api.md`: định hướng API v1/Sanctum.
- `docs/deployment.md`: topology Docker/production ban đầu.

## Việc cần chủ dự án kiểm tra

- Đọc `docs/architecture.md` và xác nhận luồng Lead → Company/Contact → Opportunity.
- Đọc `docs/permissions.md` và xác nhận data scope của role `sales` và `sales-manager`.

## Trạng thái

Tài liệu ban đầu đã được tạo nhưng **chưa có xác nhận của chủ dự án**.
