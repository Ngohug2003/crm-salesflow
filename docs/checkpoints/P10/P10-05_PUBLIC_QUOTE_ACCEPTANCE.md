# Checkpoint P10-05 — Public Quote Link & Customer Acceptance

- **Mã tính năng**: `P10-05`
- **Tên tính năng**: Public Quote Link & Customer Acceptance
- **Trạng thái**: Hoàn tất triển khai — chờ kiểm thử thủ công
- **Phụ thuộc**: `P10-02`, `P9-02`
- **Branch**: `feature/p10-05-public-quote-acceptance`

---

## 1. Kết quả thực hiện

### Database Migration
- Đã chạy migration `2026_08_15_000001_create_public_quote_tables.php` tạo 3 bảng:
  - `quote_public_links`: Lưu HASH SHA-256 token ngẫu nhiên 64 ký tự, version báo giá, hết hạn (expires_at), thu hồi (revoked_at), access code hash, số lượt xem.
  - `quote_customer_responses`: Lưu phản hồi từ khách hàng (`accept`, `decline`, `comment`), thông tin người ký (Họ tên, Email, Chức danh, Ghi chú), IP Address, User Agent và thời gian thực hiện.
  - `quote_public_events`: Lưu lịch sử truy cập công khai (`viewed`, `pdf_downloaded`, `response_submitted`).
- Bổ sung migration `2026_08_15_000002_harden_public_quote_links.php`:
  - Lưu `public_snapshot` cố định trên từng link; public page/PDF không đọc dữ liệu CRM hiện hành.
  - Unique partial index, mỗi link chỉ có một quyết định cuối `accept` hoặc `decline`.

### Business & Service Layer
- Implemented [`QuotePublicLinkService`](file:///home/hungnv/crm-salesflow/app/Services/QuotePublicLinkService.php):
  - `generateLink()`: Chỉ cho người có quyền `quotes.issue` phát hành quote đã có issued snapshot; tạo token 64 ký tự, lưu hash SHA-256, access code hash tùy chọn và thu hồi link đang hoạt động.
  - `resolveLinkFromToken()`: Kiểm tra hash, expiry, revoke và version snapshot; token không hợp lệ không tiết lộ quote.
  - `recordView()`: Ghi nhận số lượt xem và nhật ký truy cập.
  - `recordResponse()`: Khóa bản ghi trong transaction, ghi nhận comment/accept/decline; chỉ một quyết định cuối; cập nhật `accepted` hoặc `declined`, timeline Opportunity và notification database/broadcast/email cho Sale.
  - Khi phát hành version mới, `QuoteDocumentService` thu hồi toàn bộ public link của version cũ.

### Endpoint Công Khai & Interface
- Route công khai không cần đăng nhập: `GET /q/{token}` -> [`PublicQuoteViewer`](file:///home/hungnv/crm-salesflow/app/Livewire/Public/PublicQuoteViewer.php), rate-limit 30 request/phút/IP; thao tác submit/mã truy cập cũng rate-limit.
- Layout công khai nhẹ: [`resources/views/layouts/guest.blade.php`](file:///home/hungnv/crm-salesflow/resources/views/layouts/guest.blade.php) (Branded, responsive, không chứa navigation/sidebar nội bộ CRM).
- Blade View [`public-quote-viewer.blade.php`](file:///home/hungnv/crm-salesflow/resources/views/livewire/public/public-quote-viewer.blade.php): Hiển thị issued snapshot, tổng tiền, điều khoản, modal accept/decline/comment; mã bảo vệ hiển thị riêng trước khi xem nội dung.
- Nút **"Tạo link công khai"** chỉ hiện cho quyền `issue` và quote đã phát hành; có thể nhập mã bảo vệ tùy chọn trên màn chi tiết Báo giá.
- Nút **"Tạo lại PDF"** trên màn chi tiết chỉ hiện cho quyền `issue`; render đè file `ready` cũ theo đúng snapshot/version hiện tại.

---

## 2. Kết quả Quality Gates

1. **Laravel Pint (các file P10-05 đã thay đổi)**: `PASS 9 files`.
2. **Feature Test Suite**: `11 passed (36 assertions)` trong [`tests/Feature/PublicQuoteAcceptanceTest.php`](file:///home/hungnv/crm-salesflow/tests/Feature/PublicQuoteAcceptanceTest.php).

---

## 3. Quy trình nghiệm thu thủ công (Manual Acceptance Checks)

1. Đăng nhập hệ thống bằng tài khoản NVKD (Sales).
2. Mở một Báo giá ở trạng thái Đã phát hành (Issued) tại `/quotes/{id}`. Có thể nhập mã bảo vệ ít nhất 6 ký tự, sau đó bấm **"Tạo link công khai"**.
3. Sao chép liên kết dạng `http://localhost/q/{token}`.
4. Mở cửa sổ trình duyệt ẩn danh (Incognito/Guest mode) không đăng nhập -> Dán đường dẫn public link.
5. Kiểm tra giao diện hiển thị thông tin Báo giá đầy đủ, chuyên nghiệp, không lộ menu/sidebar CRM.
6. Thao tác bấm **"Chấp thuận Báo giá"** -> Điền Họ tên, Email, Chức danh -> Bấm Gửi.
7. Kiểm tra trên màn hình CRM: Báo giá chuyển trạng thái `ACCEPTED` và Activity timeline/notification của Sale ghi nhận thông tin chấp thuận.
8. Tạo link có mã, mở ở Incognito: chưa nhập đúng mã thì không được thấy mã số, khách hàng hay số tiền của báo giá; PDF cũng bị từ chối.
9. Tạo link mới hoặc phát hành version mới: link cũ hiển thị trạng thái không hợp lệ. Với một link, gửi comment trước rồi accept/decline; gửi quyết định cuối lần hai phải bị chặn.
