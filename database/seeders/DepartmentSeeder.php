<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $management = Department::query()->updateOrCreate(
            ['code' => 'MANAGEMENT'],
            [
                'parent_id' => null,
                'name' => 'Ban Giám đốc',
                'description' => 'Điều hành và quản trị SalesFlow CRM.',
                'is_active' => true,
                'sort_order' => 10,
            ],
        );

        Department::query()->updateOrCreate(
            ['code' => 'SALES'],
            [
                'parent_id' => $management->getKey(),
                'name' => 'Phòng Kinh doanh',
                'description' => 'Quản lý hoạt động bán hàng và quan hệ khách hàng.',
                'is_active' => true,
                'sort_order' => 20,
            ],
        );

        Department::query()->updateOrCreate(
            ['code' => 'MARKETING'],
            [
                'parent_id' => $management->getKey(),
                'name' => 'Phòng Marketing',
                'description' => 'Quản lý nguồn lead và các chiến dịch marketing.',
                'is_active' => true,
                'sort_order' => 30,
            ],
        );

        Department::query()->updateOrCreate(
            ['code' => 'IT'],
            [
                'parent_id' => $management->getKey(),
                'name' => 'Phòng Công nghệ thông tin',
                'description' => 'Quản trị hệ thống, bảo mật và nhật ký kiểm toán.',
                'is_active' => true,
                'sort_order' => 40,
            ],
        );
    }
}
