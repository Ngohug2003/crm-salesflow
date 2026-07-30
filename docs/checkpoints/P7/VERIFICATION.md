# Xác minh P7-FIX — Dashboard và báo cáo

> Feature hợp nhất: `feature/p7-fix-report-consistency`

## Quality gates

| Hạng mục | Kết quả |
|---|---|
| Test mới `Phase7ReportConsistencyTest` | 8 test — đạt |
| Test mới `ReportAnalyticsDemoSeederTest` | 1 test, 8 assertion — đạt |
| PHPStan trên mã P7-FIX | Không có lỗi |
| Pint trên mã P7-FIX | Đạt |
| Vite production build | Đạt, không có dependency vulnerability |
| Blade view cache | Biên dịch thành công |
| Trình duyệt Dashboard → Phễu → `this_week` → Doanh thu | Canvas hiển thị đúng, 0 JavaScript exception |

Theo yêu cầu của chủ dự án, lượt này chỉ chạy file test mới của feature; không chạy lặp lại các test suite P7 cũ.

## Trường hợp đã được kiểm tra

- Khoảng ngày custom sai hoặc đảo ngược.
- Revenue theo ngày đóng thực tế.
- Forecast theo ngày dự kiến đóng và chỉ gồm cơ hội mở.
- Funnel dựa trên lịch sử stage.
- Filter option theo owned scope.
- Làm mới metrics không xóa cache phân hệ khác.
- Task được tính cho người phụ trách qua pivot.
- Bốn màn Dashboard/Report render thành công với giao diện chuẩn hóa.
- Bộ lọc không truyền sang màn khác; trạng thái Lead hiển thị bằng tiếng Việt.
- Chart không còn animation race khi Livewire thay canvas.
- Chart chuyển dữ liệu bằng animation `easeOutQuart` trong 320ms và tôn trọng reduced motion.
- Seeder tạo đủ 100 kịch bản báo cáo trên 24 tháng.

## Phân bố dữ liệu demo đã xác minh

- Năm 2024: 17 bản ghi.
- Năm 2025: 48 bản ghi.
- Năm 2026: 35 bản ghi.
- Có dữ liệu từ quý 3/2024 đến quý 3/2026.
- Mốc cũ nhất: `03/08/2024 09:30`.
- Mốc mới nhất: `26/07/2026 10:15`.

## Điểm cần chủ dự án kiểm tra

- Cảm nhận tốc độ cập nhật chart khi đổi filter.
- Mức độ phù hợp của màu biểu đồ với dữ liệu thật.
- Bảng trên màn hình nhỏ và dark mode.
- Đối chiếu số liệu thật với các cơ hội có ngày đóng/dự kiến đóng cụ thể.
