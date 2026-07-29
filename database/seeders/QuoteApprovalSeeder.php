<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\QuoteApprovalRule;
use App\Models\QuoteBrandingSetting;
use Illuminate\Database\Seeder;

final class QuoteApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Tự động duyệt trong hạn mức Sales',
                'minimum_discount_percent' => '0.00',
                'maximum_discount_percent' => '10.00',
                'required_role' => null,
                'auto_approve' => true,
                'priority' => 10,
            ],
            [
                'name' => 'Sales Manager duyệt',
                'minimum_discount_percent' => '10.01',
                'maximum_discount_percent' => '20.00',
                'required_role' => 'sales-manager',
                'auto_approve' => false,
                'priority' => 20,
            ],
            [
                'name' => 'Ban quản trị duyệt',
                'minimum_discount_percent' => '20.01',
                'maximum_discount_percent' => null,
                'required_role' => 'admin',
                'auto_approve' => false,
                'priority' => 30,
            ],
        ];

        foreach ($rules as $rule) {
            QuoteApprovalRule::query()->updateOrCreate(
                ['name' => $rule['name']],
                [...$rule, 'is_active' => true],
            );
        }

        QuoteBrandingSetting::query()->firstOrCreate([], [
            'company_name' => 'SalesFlow CRM',
            'tax_code' => '0100000000',
            'address' => 'Hà Nội, Việt Nam',
            'hotline' => '0900 000 000',
            'email' => 'sales@salesflow.test',
            'payment_terms' => "Báo giá có hiệu lực theo thời hạn ghi trên tài liệu.\nThanh toán bằng chuyển khoản theo thỏa thuận.",
        ]);
    }
}
