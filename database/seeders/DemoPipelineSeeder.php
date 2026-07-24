<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

final class DemoPipelineSeeder extends Seeder
{
    public function run(): void
    {
        $pipeline = Pipeline::query()->updateOrCreate(
            ['code' => 'standard-sales-pipeline'],
            [
                'name' => 'Quy trình Bán hàng Standard',
                'description' => 'Quy trình quản lý cơ hội bán hàng 6 giai đoạn tiêu chuẩn CRM SalesFlow',
                'is_default' => true,
                'is_active' => true,
            ],
        );

        $stages = [
            [
                'code' => 'initial-contact',
                'name' => 'Liên hệ ban đầu',
                'description' => 'Tiếp cận đầu tiên, xác nhận thông tin cơ bản khách hàng',
                'position' => 1,
                'probability' => 10,
                'color' => '#64748B',
                'is_won' => false,
                'is_lost' => false,
                'is_system' => false,
            ],
            [
                'code' => 'needs-analysis',
                'name' => 'Phân tích nhu cầu',
                'description' => 'Khảo sát nhu cầu chi tiết và đánh giá ngân sách đối tác',
                'position' => 2,
                'probability' => 30,
                'color' => '#3B82F6',
                'is_won' => false,
                'is_lost' => false,
                'is_system' => false,
            ],
            [
                'code' => 'proposal-quote',
                'name' => 'Gửi Báo giá / Đề xuất',
                'description' => 'Xây dựng giải pháp kỹ thuật và gửi báo giá thương mại',
                'position' => 3,
                'probability' => 50,
                'color' => '#8B5CF6',
                'is_won' => false,
                'is_lost' => false,
                'is_system' => false,
            ],
            [
                'code' => 'negotiation',
                'name' => 'Thương lượng hợp đồng',
                'description' => 'Đàm phán điều khoản hợp đồng và thống nhất lịch nghiệm thu',
                'position' => 4,
                'probability' => 80,
                'color' => '#F59E0B',
                'is_won' => false,
                'is_lost' => false,
                'is_system' => false,
            ],
            [
                'code' => 'closed-won',
                'name' => 'Chốt thành công (Won)',
                'description' => 'Hợp đồng đã ký kết chính thức, chuyển giao thực thi',
                'position' => 5,
                'probability' => 100,
                'color' => '#10B981',
                'is_won' => true,
                'is_lost' => false,
                'is_system' => true,
            ],
            [
                'code' => 'closed-lost',
                'name' => 'Thất bại (Lost)',
                'description' => 'Khách hàng từ chối hoặc dự án bị dừng/hủy',
                'position' => 6,
                'probability' => 0,
                'color' => '#EF4444',
                'is_won' => false,
                'is_lost' => true,
                'is_system' => true,
            ],
        ];

        foreach ($stages as $stage) {
            PipelineStage::query()->updateOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'code' => $stage['code'],
                ],
                $stage,
            );
        }
    }
}
