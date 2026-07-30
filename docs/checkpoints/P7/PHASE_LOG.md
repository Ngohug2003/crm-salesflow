# Nhật ký triển khai Giai đoạn 7 — Dashboard và báo cáo

> Trạng thái: P7-01 đến P7-06 đã hoàn thành. Feature hợp nhất **P7-FIX** đã triển khai và đang chờ chủ dự án kiểm thử giao diện thủ công.

## Danh sách feature

| Mã | Nội dung | Trạng thái |
|---|---|---|
| P7-01 | Metrics query services | Hoàn thành |
| P7-02 | Dashboard filters và KPI | Hoàn thành |
| P7-03 | Funnel report | Hoàn thành |
| P7-04 | Revenue và forecast report | Hoàn thành |
| P7-05 | Sales performance report | Hoàn thành |
| P7-06 | Report cache và checkpoint | Hoàn thành |
| P7-FIX | Chuẩn hóa số liệu, data scope, cache, UI và Chart.js | Chờ kiểm thử thủ công |

## Thay đổi của P7-FIX

### Số liệu và quyền dữ liệu

- Bộ lọc ngày không hợp lệ được đưa về tháng hiện tại; khoảng ngày đảo ngược được tự chuẩn hóa.
- Tùy chọn phòng ban, người dùng và quy trình chỉ hiển thị trong data scope của tài khoản.
- Dashboard và toàn bộ trang báo cáo cùng kiểm tra permission `reports.view`.
- Doanh thu thắng dùng `actual_close_date`.
- Dự báo chỉ dùng cơ hội đang mở có `expected_close_date` trong kỳ.
- Phễu dùng `opportunity_stage_histories`, mỗi cơ hội chỉ được tính một lần tại mỗi giai đoạn đã đi qua.
- Công việc hoàn thành được tính theo `completed_at` và bảng `task_assignees`.

### Kiến trúc và hiệu năng

- `EloquentMetricsRepository` chỉ điều phối cache và các metrics query.
- Query được tách theo nhóm tại `app/Repositories/Metrics`.
- Hiệu suất nhân viên được tổng hợp theo nhóm, không chạy lại nhiều query cho từng user.
- Cache report dùng version riêng, TTL 5 phút; không còn `Cache::flush()`.
- Lead, Opportunity, Activity, Task và lịch sử chuyển stage tự tăng version cache khi thay đổi.
- Bổ sung index phục vụ ngày chốt, ngày dự kiến chốt, owner và công việc hoàn thành.

### Giao diện

- Dùng chung header, điều hướng, filter bar và loading state.
- Bộ lọc độc lập theo từng màn; chuyển màn bắt đầu với bộ lọc mặc định của màn đích.
- Chuẩn hóa bảng bằng Flux table.
- Bỏ emoji, nhãn tiếng Anh không cần thiết, màu và font quá nặng.
- Trạng thái Lead trên biểu đồ dùng label tiếng Việt từ `LeadStatus`.
- Thêm loading, empty state, responsive và dark mode.
- Cài `chart.js` trực tiếp qua Vite, không dùng wrapper package.
- Biểu đồ có nội dung thay thế cho screen reader và giảm animation khi người dùng bật reduced motion.
- Chart dùng animation ngắn 320ms; animation luôn được dừng trước khi Livewire thay canvas và Alpine quản lý đúng một instance.

### Dữ liệu demo báo cáo

- `ReportAnalyticsDemoSeeder` tạo 100 kịch bản gồm Lead, Opportunity, Activity và Task.
- Dữ liệu được phân bố ổn định trên 24 tháng, từ năm 2024 đến 2026, để kiểm tra bộ lọc tháng, quý và năm.
- Seeder có thể chạy lại an toàn mà không nhân bản dữ liệu:

```bash
docker compose exec -T app php artisan db:seed --class=ReportAnalyticsDemoSeeder --force
```

## Tệp chính

- `app/Repositories/EloquentMetricsRepository.php`
- `app/Repositories/Metrics/*`
- `app/Services/Analytics/ReportFilterOptionsService.php`
- `app/Livewire/Concerns/InteractsWithReportFilters.php`
- `resources/views/components/reports/chart.blade.php`
- `resources/views/livewire/reports/partials/*`
- `resources/js/app.js`
- `database/seeders/ReportAnalyticsDemoSeeder.php`
- `tests/Feature/Phase7ReportConsistencyTest.php`
- `tests/Feature/ReportAnalyticsDemoSeederTest.php`

## Kiểm thử thủ công

1. Mở `/dashboard`, đổi từng bộ lọc và kiểm tra biểu đồ cập nhật không cần tải lại toàn trang.
2. Đăng nhập user scope `owned` và `department`; kiểm tra không thấy tùy chọn ngoài phạm vi.
3. Mở `/reports/funnel`; chọn pipeline và đối chiếu lịch sử chuyển stage của một cơ hội.
4. Mở `/reports/revenue`; đối chiếu ngày chốt thực tế, ngày dự kiến chốt và tổng forecast.
5. Mở `/reports/performance`; kiểm tra công việc có nhiều người phụ trách.
6. Chuyển light/dark mode, kiểm tra biểu đồ, bảng và chữ.
7. Thu nhỏ màn hình xuống mobile; kiểm tra filter, chart và table.
8. Bấm **Làm mới dữ liệu** và xác nhận bộ lọc hiện tại không bị đặt lại.
9. Chạy seeder demo rồi kiểm tra lần lượt preset hôm nay, tuần này, tháng này, quý này và năm nay trên từng màn báo cáo.
