<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Province;
use App\Models\Staff;
use App\Models\User;
use App\Models\Ward;
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

        $hanoi = Province::query()->where('code_name', 'ha_noi')->first();
        $hanoiWard = $hanoi ? Ward::query()->where('province_id', $hanoi->id)->first() : null;

        $hcm = Province::query()->where('code_name', 'thanh_pho_ho_chi_minh')->first();
        $hcmWard = $hcm ? Ward::query()->where('province_id', $hcm->id)->first() : null;

        $danang = Province::query()->where('code_name', 'da_nang')->first();
        $danangWard = $danang ? Ward::query()->where('province_id', $danang->id)->first() : null;

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

            $assignedProvince = match ($index % 3) {
                0 => $hanoi,
                1 => $hcm,
                default => $danang,
            };

            $assignedWard = match ($index % 3) {
                0 => $hanoiWard,
                1 => $hcmWard,
                default => $danangWard,
            };

            $deptCode = match ($definition['department']) {
                'MANAGEMENT' => 'MGT',
                'SALES' => 'SALES',
                'MARKETING' => 'MKT',
                default => 'GEN',
            };

            Staff::query()->updateOrCreate(
                ['email' => $user->email],
                [
                    'user_id' => $user->id,
                    'staff_code' => sprintf('NV-%s-%04d', $deptCode, $index + 1),
                    'full_name' => $user->name,
                    'phone' => sprintf('09%08d', 80000000 + $index * 1234),
                    'birthday' => '1995-05-15',
                    'province_id' => $assignedProvince?->id,
                    'ward_id' => $assignedWard?->id,
                    'department_id' => $user->department_id,
                    'position' => match ($definition['role']) {
                        'admin' => 'Trưởng ban Quản trị',
                        'sales-manager' => 'Trưởng phòng Bán hàng',
                        'sales' => 'Chuyên viên Bán hàng (Sales Rep)',
                        default => 'Nhân viên',
                    },
                    'join_date' => '2024-01-15',
                    'is_active' => $definition['active'],
                ]
            );
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
