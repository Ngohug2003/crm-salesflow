<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DemoOpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $pipeline = Pipeline::query()->where('is_default', true)->first();
        if ($pipeline === null) {
            $this->call(DemoPipelineSeeder::class);
            $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
        }

        $stages = $pipeline->stages()->orderBy('position', 'asc')->get();
        if ($stages->isEmpty()) {
            return;
        }

        $admin = User::query()->where('email', 'admin@salesflow.test')->first();
        $companies = Company::query()->take(3)->get();
        $contacts = Contact::query()->take(3)->get();

        $opportunityDataList = [
            [
                'code' => 'OPP-DEMO-001',
                'title' => 'Dự án Hợp đồng Phần mềm CRM Doanh nghiệp Vin',
                'amount' => 150000000.00,
                'stage' => $stages->firstWhere('code', 'proposal-quote') ?? $stages[2] ?? $stages[0],
                'expected_close_date' => now()->addDays(20)->format('Y-m-d'),
                'notes' => 'Khách hàng yêu cầu tùy chỉnh thêm mô-đun Báo cáo Doanh thu.',
                'is_won' => false,
                'is_lost' => false,
            ],
            [
                'code' => 'OPP-DEMO-002',
                'title' => 'Tư vấn Thiết lập Quy trình Bán hàng FPT Telecom',
                'amount' => 85000000.00,
                'stage' => $stages->firstWhere('code', 'negotiation') ?? $stages[3] ?? $stages[0],
                'expected_close_date' => now()->addDays(10)->format('Y-m-d'),
                'notes' => 'Đang đàm phán giảm 5% giá trị hợp đồng nếu thanh toán trước 100%.',
                'is_won' => false,
                'is_lost' => false,
            ],
            [
                'code' => 'OPP-DEMO-003',
                'title' => 'Nâng cấp Gói bản quyền CRM Năm 2026',
                'amount' => 220000000.00,
                'stage' => $stages->firstWhere('is_won', true) ?? $stages->last(),
                'expected_close_date' => now()->subDays(5)->format('Y-m-d'),
                'actual_close_date' => now()->subDays(5)->format('Y-m-d'),
                'notes' => 'Hợp đồng đã ký thành công ngày 19/07/2026.',
                'is_won' => true,
                'is_lost' => false,
            ],
            [
                'code' => 'OPP-DEMO-004',
                'title' => 'Khảo sát Khả thi Dự án ERP Chuỗi Siêu thị',
                'amount' => 45000000.00,
                'stage' => $stages->firstWhere('code', 'initial-contact') ?? $stages[0],
                'expected_close_date' => now()->addDays(45)->format('Y-m-d'),
                'notes' => 'Tiếp cận ban đầu thông qua Sự kiện Triển lãm Tech Expo.',
                'is_won' => false,
                'is_lost' => false,
            ],
            [
                'code' => 'OPP-DEMO-005',
                'title' => 'Triển khai Tự động hóa Email Marketing',
                'amount' => 30000000.00,
                'stage' => $stages->firstWhere('is_lost', true) ?? $stages->last(),
                'expected_close_date' => now()->subDays(12)->format('Y-m-d'),
                'actual_close_date' => now()->subDays(12)->format('Y-m-d'),
                'lost_reason' => 'Đối thủ cạnh tranh giảm giá 30% và tặng kèm gói hạ tầng.',
                'notes' => 'Đối tác chọn giải pháp gói cố định của bên khác.',
                'is_won' => false,
                'is_lost' => true,
            ],
        ];

        foreach ($opportunityDataList as $index => $oppData) {
            $company = $companies->get($index % max(1, $companies->count()));
            $contact = $contacts->get($index % max(1, $contacts->count()));
            $stage = $oppData['stage'];

            Opportunity::query()->updateOrCreate(
                ['code' => $oppData['code']],
                [
                    'title' => $oppData['title'],
                    'amount' => $oppData['amount'],
                    'pipeline_id' => $pipeline->id,
                    'stage_id' => $stage->id,
                    'company_id' => $company?->id,
                    'contact_id' => $contact?->id,
                    'owner_id' => $admin?->id,
                    'department_id' => $admin?->department_id,
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                    'expected_close_date' => $oppData['expected_close_date'],
                    'actual_close_date' => $oppData['actual_close_date'] ?? null,
                    'lost_reason' => $oppData['lost_reason'] ?? null,
                    'notes' => $oppData['notes'],
                    'is_won' => $oppData['is_won'],
                    'is_lost' => $oppData['is_lost'],
                ],
            );
        }
    }
}
