<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, int> $departmentIds */
        $departmentIds = Department::query()
            ->whereIn('code', ['MANAGEMENT', 'SALES', 'MARKETING'])
            ->pluck('id', 'code')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();

        $password = Hash::make('SalesFlow@123');

        foreach ($this->users() as $index => $definition) {
            $user = User::query()->updateOrCreate(
                ['email' => sprintf('demo%02d@salesflow.test', $index + 1)],
                [
                    'department_id' => $departmentIds[$definition['department']],
                    'name' => $definition['name'],
                    'email_verified_at' => $definition['verified'] ? now() : null,
                    'password' => $password,
                    'is_active' => $definition['active'],
                ],
            );

            $user->syncRoles($definition['role']);
        }
    }

    /** @return list<array{name: string, department: string, role: string, active: bool, verified: bool}> */
    private function users(): array
    {
        return [
            ['name' => 'Nguyễn Minh Anh', 'department' => 'MANAGEMENT', 'role' => 'admin', 'active' => true, 'verified' => true],
            ['name' => 'Trần Thu Hà', 'department' => 'MANAGEMENT', 'role' => 'admin', 'active' => true, 'verified' => true],
            ['name' => 'Lê Quốc Bảo', 'department' => 'SALES', 'role' => 'sales-manager', 'active' => true, 'verified' => true],
            ['name' => 'Phạm Gia Huy', 'department' => 'SALES', 'role' => 'sales', 'active' => true, 'verified' => true],
            ['name' => 'Hoàng Ngọc Mai', 'department' => 'SALES', 'role' => 'sales', 'active' => true, 'verified' => true],
            ['name' => 'Đỗ Đức Long', 'department' => 'SALES', 'role' => 'sales', 'active' => true, 'verified' => true],
            ['name' => 'Vũ Khánh Linh', 'department' => 'SALES', 'role' => 'sales', 'active' => true, 'verified' => false],
            ['name' => 'Bùi Thanh Tùng', 'department' => 'SALES', 'role' => 'sales', 'active' => false, 'verified' => true],
            ['name' => 'Ngô Thảo Vy', 'department' => 'SALES', 'role' => 'sales', 'active' => true, 'verified' => true],
            ['name' => 'Đặng Thành Nam', 'department' => 'SALES', 'role' => 'sales', 'active' => true, 'verified' => true],
            ['name' => 'Dương Hải Yến', 'department' => 'SALES', 'role' => 'sales', 'active' => true, 'verified' => true],
            ['name' => 'Lý Quang Minh', 'department' => 'SALES', 'role' => 'viewer', 'active' => true, 'verified' => true],
            ['name' => 'Hồ Bảo Trâm', 'department' => 'SALES', 'role' => 'viewer', 'active' => false, 'verified' => true],
            ['name' => 'Phan Lan Anh', 'department' => 'MARKETING', 'role' => 'sales-manager', 'active' => true, 'verified' => true],
            ['name' => 'Mai Tuấn Kiệt', 'department' => 'MARKETING', 'role' => 'sales', 'active' => true, 'verified' => true],
            ['name' => 'Tạ Ngọc Hân', 'department' => 'MARKETING', 'role' => 'sales', 'active' => true, 'verified' => true],
            ['name' => 'Cao Nhật Quang', 'department' => 'MARKETING', 'role' => 'sales', 'active' => true, 'verified' => false],
            ['name' => 'Đinh Mỹ Duyên', 'department' => 'MARKETING', 'role' => 'sales', 'active' => false, 'verified' => true],
            ['name' => 'Trịnh Anh Khoa', 'department' => 'MARKETING', 'role' => 'viewer', 'active' => true, 'verified' => true],
            ['name' => 'Võ Hoàng Nam', 'department' => 'MARKETING', 'role' => 'viewer', 'active' => true, 'verified' => true],
        ];
    }
}
