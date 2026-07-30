# Kiểm thử thủ công Playbook — Quy trình Bán hàng Standard

Tài liệu này dành cho nhân viên Sales kiểm tra trọn luồng từ **Liên hệ ban đầu** đến một trong hai kết quả **Won** hoặc **Lost**.

> Won và Lost là hai nhánh kết thúc. Một Opportunity không đi lần lượt qua cả hai nhánh; hãy dùng hai Opportunity riêng để kiểm tra.

## 1. Dữ liệu và tài khoản

Admin chạy seed:

```bash
docker compose exec app php artisan db:seed --class=Database\\Seeders\\StandardSalesPlaybookSeeder --force
```

Tài khoản Sales mẫu:

| Thông tin | Giá trị |
|---|---|
| Email | `demo04@salesflow.test` |
| Mật khẩu | `SalesFlow@123` |
| Vai trò | Sales |

Nếu Sales không thấy Playbook, Admin mở **Cấu hình quyền theo vai trò** và cấp quyền `sales-playbook.view` cho role Sales.

## 2. Xác nhận cấu hình trước khi test

Admin/Sales Manager mở **Quản trị → Sales Playbook** và kiểm tra đủ sáu Playbook đã phát hành:

| Stage | Playbook | Chính sách chạy |
|---|---|---|
| Liên hệ ban đầu | 01 — Tiếp cận và xác nhận liên hệ | Chỉ một lần |
| Phân tích nhu cầu | 02 — Phân tích nhu cầu B2B | Chỉ một lần |
| Gửi Báo giá / Đề xuất | 03 — Chuẩn bị và gửi đề xuất | Mỗi lần vào stage |
| Thương lượng hợp đồng | 04 — Thương lượng và chốt điều khoản | Mỗi lần vào stage |
| Chốt thành công (Won) | 05 — Bàn giao khách hàng thành công | Chỉ một lần |
| Thất bại (Lost) | 06 — Đóng hồ sơ và rút kinh nghiệm | Chỉ một lần |

Mỗi Stage phải hiển thị đúng Playbook được gán. Khi chạy lại, seeder sẽ đưa cả sáu assignment của pipeline Standard về bộ cấu hình chuẩn; Playbook tùy chỉnh cũ vẫn còn trong thư viện nhưng không còn được gán vào các Stage này.

## 3. Kịch bản A — Bán hàng thành công

### Bước 1 — Tạo Opportunity

1. Đăng nhập bằng tài khoản Sales.
2. Mở **Cơ hội bán hàng → Tạo mới**.
3. Nhập tên: `TEST PLAYBOOK WON - <ngày giờ>`.
4. Chọn **Quy trình Bán hàng Standard**.
5. Chọn Stage **Liên hệ ban đầu**.
6. Chọn chính mình ở **Người phụ trách**.
7. Chọn Công ty và Người liên hệ nếu đã có.
8. Lưu Opportunity rồi mở trang chi tiết.

Kết quả mong đợi:

- Khối **Sales Playbook** hiển thị `01 — Tiếp cận và xác nhận liên hệ`.
- Hệ thống tạo Task `Theo dõi sau lần liên hệ đầu tiên`.
- Không sinh hai lượt Playbook hoặc hai Task khi tải lại trang.

### Bước 2 — Hoàn tất Liên hệ ban đầu

1. Tại câu hỏi **Kênh liên hệ và phản hồi ban đầu**, nhập:
   `Đã gọi điện, khách hàng đồng ý họp khảo sát vào 09:00 ngày mai.`
2. Bấm **Hoàn tất**.
3. Hoàn tất checklist **Đã xác nhận cuộc trao đổi tiếp theo**.
4. Mở Task liên quan, xử lý và đánh dấu hoàn tất.
5. Bấm Stage **Phân tích nhu cầu**.

Thử chuyển Stage trước khi hoàn tất câu hỏi/checklist. Hệ thống phải chặn và nêu đúng nội dung còn thiếu. Sau khi hoàn tất, Opportunity được chuyển Stage và Playbook số 02 xuất hiện.

### Bước 3 — Hoàn tất Phân tích nhu cầu

1. Trả lời **Vấn đề kinh doanh cần giải quyết**, ví dụ:
   `Đội Sales đang theo dõi khách hàng bằng Excel, mất lịch sử và khó dự báo doanh thu.`
2. Trả lời **Người ra quyết định và quy trình duyệt**, ví dụ:
   `Giám đốc Kinh doanh đề xuất, CFO duyệt ngân sách, CEO ký hợp đồng.`
3. Mở **Chỉnh sửa Opportunity**:
   - Nhập **Giá trị cơ hội**, ví dụ `250000000`.
   - Chọn **Ngày dự kiến chốt**, ví dụ sau 30 ngày.
4. Quay lại chi tiết và kiểm tra hai bước trường bắt buộc tự chuyển sang hoàn tất.
5. Hoàn tất checklist **Đã xác nhận mức độ phù hợp giải pháp**.
6. Hoàn tất Task biên bản nhu cầu.
7. Chuyển sang **Gửi Báo giá / Đề xuất**.

Kết quả mong đợi: hệ thống chặn nếu thiếu câu trả lời, giá trị, ngày chốt hoặc checklist; khi đủ dữ liệu thì cho chuyển Stage.

### Bước 4 — Hoàn tất Gửi Báo giá / Đề xuất

1. Nếu các bước Công ty/Người liên hệ còn thiếu, mở chỉnh sửa Opportunity và chọn đúng dữ liệu.
2. Kiểm tra giá trị Opportunity khớp báo giá mới nhất.
3. Hoàn tất checklist **Báo giá đã được kiểm tra và phát hành**.
4. Hoàn tất checklist **Khách hàng xác nhận đã nhận đề xuất**.
5. Kiểm tra Reminder **Theo dõi phản hồi báo giá** có hạn sau hai ngày làm việc.
6. Chuyển sang **Thương lượng hợp đồng**.

Kết quả mong đợi: ba trường dữ liệu tự hoàn tất khi đã có dữ liệu; hai checklist phải hoàn tất trước khi rời Stage.

### Bước 5 — Hoàn tất Thương lượng hợp đồng

1. Trả lời **Phản đối hoặc vướng mắc còn lại**, ví dụ:
   `Khách hàng đề nghị giảm 5% và chia thanh toán thành hai đợt.`
2. Trả lời **Lộ trình phê duyệt cuối cùng**, ví dụ:
   `CFO duyệt chiết khấu trước thứ Sáu, CEO ký trong tuần tới.`
3. Cập nhật lại **Ngày dự kiến chốt** nếu lịch đã thay đổi.
4. Hoàn tất hai checklist:
   - **Đã thống nhất điều khoản thương mại**.
   - **Đã thống nhất hành động chốt tiếp theo**.
5. Hoàn tất Task theo dõi bước chốt.
6. Bấm **Đánh dấu Thành công** và xác nhận.

Kết quả mong đợi: nếu còn điều kiện thoát, thao tác Won bị chặn. Khi đủ điều kiện, Opportunity chuyển sang **Chốt thành công (Won)** và Playbook bàn giao xuất hiện.

### Bước 6 — Hoàn tất sau khi Won

1. Hoàn tất checklist lưu hợp đồng/xác nhận chốt.
2. Hoàn tất checklist bàn giao thông tin khách hàng.
3. Mở và hoàn tất Task bàn giao nội bộ.
4. Kiểm tra Reminder theo dõi onboarding có hạn sau hai ngày làm việc.

Kết quả mong đợi:

- Opportunity có trạng thái **Thành công**.
- Playbook sau Won không ngăn việc chốt.
- Tiến độ Playbook đạt 100% sau khi các bước bắt buộc hoàn tất.

## 4. Kịch bản B — Cơ hội thất bại

1. Tạo Opportunity thứ hai với tên `TEST PLAYBOOK LOST - <ngày giờ>`.
2. Hoàn tất điều kiện thoát của Stage hiện tại.
3. Bấm **Đánh dấu Thất bại**.
4. Nhập lý do thất bại theo form và xác nhận.
5. Tại Playbook số 06:
   - Nhập nguyên nhân và đối thủ.
   - Hoàn tất checklist dữ liệu kết quả cuối.
   - Hoàn tất Task rút kinh nghiệm.
   - Kiểm tra Reminder nuôi dưỡng lại có hạn sau 30 ngày làm việc.

Kết quả mong đợi:

- Opportunity có trạng thái **Thất bại**.
- Playbook Lost xuất hiện nhưng không chặn thao tác đóng.
- Lý do thất bại và bài học được giữ trong lịch sử.

## 5. Kiểm tra chạy lại và lịch sử

Tại Stage **Gửi Báo giá / Đề xuất** hoặc **Thương lượng hợp đồng**:

1. Hoàn tất một lượt Playbook.
2. Chuyển Opportunity sang Stage khác.
3. Chuyển lại Stage cũ.

Kết quả mong đợi: do chính sách **Mỗi lần vào stage**, hệ thống tạo lượt mới và giữ lịch sử lượt cũ.

Tại trang chi tiết, bấm **Chạy lại → Xác nhận chạy lại**:

- Lượt mới bắt đầu từ 0%.
- Lượt trước không bị xóa.
- Task/Reminder của lượt mới chỉ được tạo một lần.

## 6. Checklist nghiệm thu nhanh

- [ ] Đủ sáu Playbook và sáu Stage assignment.
- [ ] Sales chỉ thấy Opportunity trong data scope của mình.
- [ ] Task/Reminder được tạo đúng tên và đúng ngày làm việc.
- [ ] Tải lại trang không tạo dữ liệu trùng.
- [ ] Điều kiện thoát chặn cả chuyển Stage, Won và Lost.
- [ ] Required field tự hoàn tất sau khi cập nhật Opportunity.
- [ ] Won/Lost kích hoạt đúng Playbook hậu xử lý.
- [ ] Chạy lại giữ nguyên lịch sử cũ.
- [ ] Audit Log có sự kiện kích hoạt và hoàn tất bước kèm Request ID.
