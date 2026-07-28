# SalesFlow CRM — Spec Tính năng Nâng cao B2B Sales Enablement (P10)

> **Tài liệu đặc tả (Specification)** này định nghĩa các tính năng nâng cao cấp doanh nghiệp (Enterprise Grade) nhằm gia tăng giá trị thương mại cho SalesFlow CRM khi trình diễn và chào bán cho các doanh nghiệp B2B.

---

## 1. Mục tiêu & Giá trị Thương mại

1. **Tự động hóa vận hành Sales (Sales Automation)**: Giảm tối đa thời gian chờ của Lead, loại bỏ sự can thiệp cảm tính trong phân chia dữ liệu và ngăn chặn tình trạng "ngâm Lead".
2. **Kiểm soát Doanh thu & Biên lợi nhuận (Margin & Discount Governance)**: Chuẩn hóa quy trình phát hành Báo giá thương mại, siết chặt hạn mức chiết khấu thông qua luồng phê duyệt (Approval Workflow) trước khi gửi khách hàng.
3. **Nâng tầm Hình ảnh Thương hiệu (Professional B2B PDF)**: Phát hành Báo giá PDF chuẩn doanh nghiệp Việt Nam có logo, dấu mẫu và điều khoản thanh toán rõ ràng.

---

## 2. Bảng tổng hợp Feature P10

| Thứ tự | Mã Feature | Tên Feature | Branch đề xuất | Phụ thuộc | Requirement / GAP | Trạng thái | Mục tiêu chính |
|---:|---|---|---|---|---|---|---|
| 1 | **P10-01** | Smart Lead Auto-Routing & SLA Reassign | `feature/p10-01-lead-auto-routing` | P3-10, P8-06 | REQ-LEAD, REQ-WORK, REQ-NOTIFY | ⏳ Chờ duyệt | Phân bổ Lead tự động theo xoay vòng/địa bàn và thu hồi Lead ngâm theo SLA. |
| 2 | **P10-02** | B2B PDF Quote Builder & Discount Approval | `feature/p10-02-pdf-quote-approval` | P5-X02, P8-06 | REQ-PIPELINE, REQ-IO, REQ-RBAC | ⏳ Chờ duyệt | Xuất PDF Báo giá chuyên nghiệp và duyệt chiết khấu nhiều cấp trước khi phát hành. |

---

## 3. Chi tiết đặc tả (Feature Specifications)

### P10-01 — Smart Lead Auto-Routing & SLA Reassign (Phân bổ Lead tự động & Thu hồi SLA)

#### 1. Mục tiêu
Tự động hóa phân chia Lead mới tiếp nhận cho nhân viên kinh doanh phù hợp và áp dụng cơ chế tự động thu hồi/chuyển giao nếu Lead không được xử lý đúng hạn cam kết (SLA).

#### 2. Phạm vi tính năng
- **Quy tắc phân bổ (Routing Rules Engine)**:
  - **Round-Robin (Xoay vòng)**: Tự động chia đều cho các nhân viên Sales đang kích hoạt (`is_active = true`) thuộc phòng ban Sales.
  - **Theo Địa bàn (Territory Routing)**: Ánh xạ Tỉnh/Thành phố của Lead đến Team miền Bắc / Miền Nam / Miền Trung.
  - **Theo Nguồn / Quy mô**: Gán Lead có giá trị dự kiến cao (`estimated_value >= 100M`) cho Trưởng nhóm/Senior Sales.
- **Cấu hình SLA & Thu hồi (Auto-Reassign Engine)**:
  - Cho phép Admin/Manager cấu hình thời gian SLA phản hồi đầu tiên (mặc định: 2 giờ làm việc).
  - CronJob/Scheduler tự động quét các Lead ở trạng thái `Mới (New)` đã quá hạn SLA mà chưa có Activity cuộc gọi/họp hoặc Note tiếp cận.
  - Tự động thu hồi Lead về kho chung hoặc chuyển giao cho Sale tiếp theo trong danh sách xoay vòng.
  - Gửi thông báo Realtime/Email cho Sale cũ (thông báo thu hồi) và Sale mới (thông báo nhận Lead).
- **Lịch sử & Audit**:
  - Ghi vết lịch sử phân bổ tự động vào `LeadAssignmentHistory` với lý do `AUTO_ROUTING` hoặc `SLA_REASSIGN`.

#### 3. Không làm trong scope này
- Không làm thuật toán AI phức tạp tự đo tải trọng tâm lý nhân viên.
- Không tích hợp với hệ thống chấm công bên thứ ba.

#### 4. Checklist Nghiệm thu thủ công (Acceptance Criteria)
- [ ] Lead mới tạo từ Form/Web/Import tự động có chủ sở hữu (`owner_id`) theo quy tắc xoay vòng.
- [ ] Lead thuộc địa bàn Hà Nội tự động được gán cho Sale thuộc Team Hà Nội.
- [ ] Quá hạn SLA 2 giờ mà Lead chưa có tương tác $\rightarrow$ CronJob chạy thu hồi Lead và gán cho Sale khác.
- [ ] Lịch sử phân công ghi nhận rõ nguồn gốc gán tự động do SLA/Round-Robin.

---

### P10-02 — B2B PDF Quote Builder & Discount Approval (Xuất PDF Báo giá & Phê duyệt Chiết khấu)

#### 1. Mục tiêu
Chuẩn hóa quy trình báo giá B2B chuyên nghiệp: tự động tạo Báo giá PDF có nhận diện thương hiệu và ép duyệt chiết khấu khi mức giảm giá vượt quá hạn mức cho phép của nhân viên Sales.

#### 2. Phạm vi tính năng
- **Quy trình Phê duyệt Chiết khấu (Discount Approval Workflow)**:
  - Bổ sung trạng thái Báo giá: `Bản nháp (Draft)` $\rightarrow$ `Chờ duyệt (Pending Approval)` $\rightarrow$ `Đã duyệt (Approved)` / `Từ chối (Rejected)` $\rightarrow$ `Đã gửi (Sent)`.
  - Cấu hình Hạn mức Chiết khấu (Discount Matrix):
    - **Chiết khấu $\le 10\%$**: Sale có quyền tự duyệt và xuất/gửi Báo giá.
    - **Chiết khấu $> 10\%$ và $\le 20\%$**: Yêu cầu `sales-manager` duyệt.
    - **Chiết khấu $> 20\%$**: Yêu cầu `admin` / `super-admin` duyệt.
  - Giao diện Modal duyệt nhanh cho Manager kèm lý do đồng ý / từ chối.
  - Khoá không cho phép sửa/tải PDF Báo giá khi đang ở trạng thái `Pending Approval`.
- **Trình xuất Báo giá PDF (PDF Quote Generator)**:
  - Sinh file PDF Báo giá thương mại đẹp mắt từ Template Blade:
    - Header: Logo công ty, Tên công ty, Mã số thuế, Địa chỉ, Hotline.
    - Thông tin Khách hàng: Tên công ty đối tác, Người đại diện, Email, SĐT, Ngày báo giá, Ngày hết hạn.
    - Bảng danh mục sản phẩm (Line Items): STT, Tên sản phẩm/dịch vụ, Đơn vị tính, Số lượng, Đơn giá, Chiết khấu %, Thành tiền.
    - Tổng hợp tài chính: Tiền hàng trước thuế, Tiền thuế VAT (8%/10%), Tổng tiền thanh toán bằng số và bằng chữ.
    - Footer: Điều khoản thanh toán, Số tài khoản ngân hàng, Dấu mẫu/Chữ ký mẫu đại diện.
  - Tải file PDF an toàn qua đường dẫn ký số (Signed URL) có kiểm tra phân quyền.

#### 3. Không làm trong scope này
- Không tích hợp cổng ký số pháp lý bên thứ ba ( như VNPT-CA, Viettel-CA).
- Không làm công cụ kéo-thả thiết kế mẫu PDF (PDF Designer).

#### 4. Checklist Nghiệm thu thủ công (Acceptance Criteria)
- [ ] Báo giá có mức chiết khấu 15% tự động chuyển sang trạng thái `Chờ duyệt` khi Sale bấm Phát hành.
- [ ] Nhân viên Sale không thể tải PDF Báo giá khi chưa được Manager bấm `Duyệt`.
- [ ] Manager nhận thông báo Realtime và bấm `Duyệt` $\rightarrow$ Báo giá chuyển `Đã duyệt` và cho phép tải PDF.
- [ ] File PDF xuất ra hiển thị đầy đủ logo, bảng sản phẩm, tính toán thuế/chiết khấu chính xác từng đồng và có ký số URL bảo mật.

---

## 4. Tiêu chuẩn Kỹ thuật & Quality Gates

1. **Backend**: Laravel Service/Action pattern, Policy authorization, DB Transaction cho luồng Approval/Routing.
2. **Frontend**: Flux UI Free components, Modal xác nhận, Blade PDF Template render qua Dompdf/Snappy.
3. **Quality Gates**:
   - `PHPStan`: Không có lỗi static analysis.
   - `Laravel Pint`: Format code chuẩn 100%.
   - `Feature Tests`: Đạt 100% assertions cho luồng Routing, SLA và Approval logic.
