# Checkpoint P10-04: At-Risk Deal Detection

## 1. Feature Overview
- **Feature Code**: `P10-04`
- **Module**: Sales / Opportunity Automation
- **Target Outcome**: Phát hiện sớm các Cơ hội bán hàng (Opportunity) có dấu hiệu đứng yên, quá hạn hoặc có chỉ số nguy cơ trượt Deal (Risk Score 0-100) dựa trên hệ luật điểm rủi ro tự động. Cung cấp Risk Dashboard cho Manager/Sales để ghi nhận trạng thái xử lý rủi ro.

---

## 2. Rule Engine & Scoring Metrics
Hệ thống chấm điểm rủi ro `0 - 100` với 4 cấp độ:
- `low`: 0 - 25 điểm (An toàn)
- `medium`: 26 - 50 điểm (Rủi ro trung bình)
- `high`: 51 - 75 điểm (Rủi ro cao)
- `critical`: 76 - 100 điểm (Nguy hiểm / Rất cao)

### Các yếu tố chấm điểm rủi ro MVP:
1. `NO_ACTIVITY_7_DAYS` (+25 điểm): Không có tương tác/ghi chú/cuộc gọi nào > 7 ngày.
2. `STAGNANT_STAGE_14_DAYS` (+20 điểm): Đứng yên tại 1 giai đoạn (Stage) > 14 ngày.
3. `PAST_EXPECTED_CLOSE_DATE` / `NEAR_EXPECTED_CLOSE_DATE` (+25 / +15 điểm): Quá hạn ngày chốt dự kiến hoặc sắp đến ngày chốt trong vòng <= 3 ngày.
4. `REJECTED_OR_EXPIRED_QUOTE` (+20 điểm): Có Báo giá bị khách hàng từ chối hoặc hết hiệu lực.
5. `OVERDUE_TASKS` (+15 điểm): Có công việc/nhiệm vụ liên quan quá hạn chưa hoàn thành.
6. `MISSING_PRIMARY_CONTACT` (+15 điểm): Chưa gán Người liên hệ chính (Primary Contact) cho Deal.

---

## 3. Database Architecture & Models
- `opportunity_risk_snapshots`: Lưu lịch sử & snapshot điểm rủi ro hiện tại của Opportunity (`score`, `level`, `is_current`, `evaluated_at`).
- `opportunity_risk_factors`: Lưu các yếu tố đóng góp điểm cho snapshot (`code`, `title`, `points`, `recommended_action`, `details`).
- `opportunity_risk_acknowledgements`: Lưu ghi nhận xác nhận/khắc phục từ Manager/Sales (`user_id`, `status`, `note`, `acted_at`).
- `opportunity_risk_notification_logs`: Quản lý cooldown gửi thông báo cảnh báo rủi ro cho User.

---

## 4. Key Components Developed
1. **Service**: [`App\Services\OpportunityRiskService`](file:///home/hungnv/crm-salesflow/app/Services/OpportunityRiskService.php)
2. **Artisan Command**: `php artisan salesflow:detect-at-risk-deals` ([`App\Console\Commands\DetectAtRiskDeals`](file:///home/hungnv/crm-salesflow/app/Console/Commands/DetectAtRiskDeals.php))
3. **Livewire Risk Dashboard**: [`App\Livewire\Opportunities\OpportunityRiskDashboard`](file:///home/hungnv/crm-salesflow/app/Livewire/Opportunities/OpportunityRiskDashboard.php)
4. **View**: [`resources/views/livewire/opportunities/opportunity-risk-dashboard.blade.php`](file:///home/hungnv/crm-salesflow/resources/views/livewire/opportunities/opportunity-risk-dashboard.blade.php)
5. **Route**: `/opportunities/at-risk` (`opportunities.risk-dashboard`)
6. **Feature Tests**: [`Tests\Feature\OpportunityRiskDetectionTest`](file:///home/hungnv/crm-salesflow/tests/Feature/OpportunityRiskDetectionTest.php)

---

## 5. Verification & Quality Gates
- **PHPStan**: 0 errors (`[OK] No errors`)
- **Pint**: PASS 562 files (`PASS`)
- **PHPUnit Feature Tests**: 4/4 tests PASS (14 assertions)
