# Hướng dẫn sử dụng Sales Playbook

Kịch bản kiểm thử trọn **Quy trình Bán hàng Standard** dành cho Sales:
[`P10-03_STANDARD_PIPELINE_SALES_TEST.md`](P10-03_STANDARD_PIPELINE_SALES_TEST.md).

Sales Playbook giúp quy định Sales phải làm gì ở từng giai đoạn của cơ hội bán hàng.

- **Admin/Sales Manager**: tạo và phát hành Playbook.
- **Sales**: thực hiện các bước được giao.
- **Viewer**: chỉ xem tiến độ.

## 1. Tạo Playbook

Mở **Quản trị → Sales Playbook → Tạo playbook**.

| Trường | Tác dụng | Ví dụ |
|---|---|---|
| **Tên playbook** | Tên quy trình mà Sales sẽ nhìn thấy | `Đánh giá nhu cầu khách hàng doanh nghiệp` |
| **Chính sách chạy lại** | Quy định Playbook có chạy lại khi Opportunity quay lại cùng giai đoạn hay không | Chọn `Chỉ một lần` cho lần đánh giá ban đầu |
| **Mục tiêu** | Kết quả Sales cần đạt sau khi hoàn thành | `Xác định nhu cầu, ngân sách và người ra quyết định` |
| **Gán vào giai đoạn khi phát hành** | Chọn giai đoạn sẽ tự kích hoạt Playbook | `Quy trình Bán hàng Standard → 2. Phân tích nhu cầu` |

### Chính sách chạy lại

| Lựa chọn | Tác dụng |
|---|---|
| **Chỉ một lần** | Mỗi Opportunity chỉ chạy Playbook một lần |
| **Mỗi lần vào stage** | Mỗi lần quay lại giai đoạn sẽ tạo một lượt thực hiện mới |
| **Khởi chạy thủ công** | Không tự chạy; Sales phải bấm **Khởi chạy playbook** |

## 2. Thêm bước thực hiện

Bấm **Thêm bước**, sau đó chọn **Loại bước**.

| Loại bước | Tác dụng | Ví dụ |
|---|---|---|
| **Mục tiêu/Hướng dẫn** | Chỉ hiển thị nội dung hướng dẫn, Sales không cần bấm hoàn tất | `Tìm hiểu vấn đề kinh doanh của khách hàng` |
| **Câu hỏi qualification** | Sales phải nhập câu trả lời rồi bấm **Hoàn tất** | `Ai là người ra quyết định?` |
| **Trường dữ liệu bắt buộc** | Hệ thống tự kiểm tra dữ liệu Opportunity | `Ngày đóng dự kiến phải được nhập` |
| **Checklist** | Sales tự xác nhận đã thực hiện một việc | `Đã xác nhận nhu cầu thực tế` |
| **Tạo công việc** | Tự tạo Task cho người phụ trách Opportunity | `Gọi lại xác nhận ngân sách` |
| **Tạo nhắc nhở** | Tự tạo Task có thời điểm nhắc | `Nhắc theo dõi báo giá sau 3 ngày` |
| **Tài liệu gợi ý** | Cung cấp nội dung hoặc đường dẫn tham khảo | `Kịch bản gọi đánh giá nhu cầu` |

### Các trường của một bước

| Trường | Tác dụng | Cách nhập |
|---|---|---|
| **Tiêu đề** | Tên việc Sales nhìn thấy | Viết dạng hành động: `Xác nhận người ra quyết định` |
| **Hướng dẫn thực hiện** | Giải thích Sales cần làm gì | `Hỏi họ tên và chức vụ người duyệt cuối cùng` |
| **Bước bắt buộc** | Bước được tính vào việc hoàn thành Playbook | Bật với các bước quan trọng |
| **Chặn rời stage khi chưa hoàn tất** | Không cho chuyển giai đoạn nếu bước chưa xong | Bật với điều kiện bắt buộc trước khi đi tiếp |
| **Hạn sau số ngày làm việc** | Thời hạn Task/Reminder | Nhập `2` để hết hạn sau 2 ngày làm việc |

Thứ Bảy và Chủ nhật không được tính là ngày làm việc.

### Trường Opportunity cần có

Trường này chỉ xuất hiện khi chọn **Trường dữ liệu bắt buộc**.

| Lựa chọn | Khi nào bước tự hoàn tất? |
|---|---|
| **Giá trị cơ hội** | Giá trị lớn hơn 0 |
| **Ngày đóng dự kiến** | Đã nhập ngày dự kiến chốt |
| **Doanh nghiệp** | Đã gắn một doanh nghiệp |
| **Người liên hệ** | Đã gắn một người liên hệ |
| **Người phụ trách** | Đã có Sales phụ trách |
| **Ghi chú** | Đã có nội dung ghi chú |

Sales không bấm hoàn tất bước này. Sales cập nhật Opportunity, hệ thống sẽ tự kiểm tra.

### Nút thao tác

| Nút | Tác dụng |
|---|---|
| **Lên/Xuống** | Thay đổi thứ tự các bước |
| **Xóa bước** | Xóa bước khỏi bản nháp |
| **Lưu bản nháp** | Lưu để sửa tiếp, chưa áp dụng cho Sales |
| **Phát hành** | Bắt đầu áp dụng Playbook cho giai đoạn đã chọn |
| **Chỉnh sửa (tạo phiên bản mới)** | Sao chép bản đã phát hành thành bản nháp mới để chỉnh sửa |
| **Lưu trữ** | Ngừng áp dụng cho Opportunity mới nhưng vẫn giữ lịch sử cũ |

Phiên bản đã phát hành không sửa trực tiếp được. Nếu muốn thay đổi, hãy bấm **Tạo phiên bản mới**.

## 3. Sales thực hiện Playbook

Sales mở **Cơ hội bán hàng → Chi tiết Opportunity → Sales Playbook**.

| Nội dung | Ý nghĩa |
|---|---|
| **3/4 bước – 75%** | Đã hoàn thành 3 trong tổng số 4 bước |
| **Việc cần làm tiếp theo** | Bước chưa hoàn tất đầu tiên Sales nên xử lý |
| **Điều kiện thoát** | Bước phải hoàn tất trước khi chuyển giai đoạn |
| **Mở công việc liên quan** | Mở Task được hệ thống tạo tự động |
| **Hoàn tất** | Xác nhận đã xong câu hỏi, checklist hoặc Task |
| **Khởi chạy playbook** | Bắt đầu Playbook khi Opportunity chưa có lượt thực hiện, ví dụ Playbook chạy thủ công hoặc vừa được gán vào Stage |
| **Chạy lại** | Tạo lượt thực hiện mới; tiến độ và công việc của lượt trước vẫn được giữ trong lịch sử |

### Cách xử lý từng bước

- **Câu hỏi qualification**: nhập câu trả lời → bấm **Hoàn tất**.
- **Checklist**: thực hiện việc được giao → bấm **Hoàn tất**.
- **Trường dữ liệu bắt buộc**: sửa Opportunity và nhập dữ liệu còn thiếu.
- **Task/Reminder**: bấm **Mở công việc liên quan**, xử lý và hoàn thành Task.

Nếu còn bước có nhãn **Điều kiện thoát**, hệ thống sẽ không cho chuyển Opportunity sang giai đoạn khác và sẽ thông báo bước còn thiếu.

Khi cần thực hiện lại toàn bộ quy trình, Sales bấm **Chạy lại → Xác nhận chạy lại**. Hệ thống đưa lượt hiện tại vào lịch sử và tạo một lượt mới theo phiên bản đang gán cho giai đoạn.

## 4. Ví dụ cấu hình hoàn chỉnh

### Thông tin Playbook

| Trường | Chọn/Nhập |
|---|---|
| Tên playbook | `Đánh giá nhu cầu khách hàng doanh nghiệp` |
| Chính sách chạy lại | `Chỉ một lần` |
| Mục tiêu | `Xác định nhu cầu, ngân sách và người ra quyết định` |
| Gán vào giai đoạn | `Quy trình Bán hàng Standard → 2. Phân tích nhu cầu` |

### Bước 1 — Hỏi người ra quyết định

| Trường | Chọn/Nhập |
|---|---|
| Loại bước | `Câu hỏi qualification` |
| Tiêu đề | `Ai là người ra quyết định?` |
| Hướng dẫn | `Nhập họ tên và chức vụ người duyệt cuối cùng` |
| Bước bắt buộc | Bật |
| Chặn rời stage | Bật |

### Bước 2 — Kiểm tra giá trị cơ hội

| Trường | Chọn/Nhập |
|---|---|
| Loại bước | `Trường dữ liệu bắt buộc` |
| Tiêu đề | `Cập nhật giá trị cơ hội` |
| Trường Opportunity cần có | `Giá trị cơ hội` |
| Bước bắt buộc | Bật |
| Chặn rời stage | Bật |

### Bước 3 — Tạo việc gọi lại

| Trường | Chọn/Nhập |
|---|---|
| Loại bước | `Tạo công việc` |
| Tiêu đề | `Gọi lại xác nhận nhu cầu` |
| Hướng dẫn | `Xác nhận ngân sách và thời gian dự kiến mua` |
| Hạn sau số ngày làm việc | `2` |
| Bước bắt buộc | Bật |
| Chặn rời stage | Tắt |

Sau khi **Phát hành**:

1. Sales chuyển Opportunity vào giai đoạn **Phân tích nhu cầu**.
2. Hệ thống hiển thị ba bước và tự tạo Task gọi lại.
3. Sales nhập người ra quyết định.
4. Sales cập nhật giá trị Opportunity.
5. Khi hai điều kiện thoát đã hoàn tất, Sales được chuyển sang giai đoạn tiếp theo.

## 5. Khi gặp vấn đề

| Vấn đề | Cách xử lý |
|---|---|
| Không thấy menu Sales Playbook | Liên hệ Admin để cấp quyền |
| Opportunity không hiện Playbook | Kiểm tra Playbook đã phát hành và gán đúng giai đoạn |
| Không chuyển được giai đoạn | Hoàn tất các bước có nhãn **Điều kiện thoát** |
| Task chưa xuất hiện | Chờ vài giây, tải lại trang; nếu vẫn chưa có hãy báo Admin |
| Opportunity vẫn hiện nội dung cũ | Đây là phiên bản cũ đã được lưu; phiên bản mới chỉ áp dụng cho lượt chạy sau |
