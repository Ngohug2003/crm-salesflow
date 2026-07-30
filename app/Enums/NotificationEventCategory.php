<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationEventCategory: string
{
    case TasksSla = 'tasks_sla';
    case OpportunitiesQuotes = 'opportunities_quotes';
    case LeadsCustomers = 'leads_customers';
    case ImportExport = 'import_export';
    case SystemAccount = 'system_account';

    public function label(): string
    {
        return match ($this) {
            self::TasksSla => 'Nhiệm vụ & SLA chăm sóc',
            self::OpportunitiesQuotes => 'Cơ hội bán hàng & Báo giá',
            self::LeadsCustomers => 'Lead & Khách hàng',
            self::ImportExport => 'Tác vụ Import & Export dữ liệu',
            self::SystemAccount => 'Hệ thống & An toàn tài khoản',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TasksSla => 'Nhắc nhở công việc đến hạn, phân công task, cảnh báo SLA chăm sóc quá hạn.',
            self::OpportunitiesQuotes => 'Phân công cơ hội mới, chuyển giai đoạn bán hàng, phản hồi báo giá.',
            self::LeadsCustomers => 'Phân công Lead mới, thông báo chuyển đổi Lead thành doanh nghiệp.',
            self::ImportExport => 'Thông báo tiến độ và kết quả khi hoàn thành tác vụ Import/Export Excel.',
            self::SystemAccount => 'Cảnh báo đăng nhập thiết bị lạ, chấp nhận lời mời, thông báo hệ thống.',
        };
    }
}
