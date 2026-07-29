<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Province;
use App\Models\User;
use App\Services\StaffService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\VietnamAdministrativeUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private StaffService $staffService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DepartmentSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(VietnamAdministrativeUnitSeeder::class);

        $this->staffService = app(StaffService::class);
    }

    public function test_it_creates_a_staff_record_with_address_and_birthday(): void
    {
        $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();
        $hanoi = Province::query()->where('code_name', 'ha_noi')->first();

        $staff = $this->staffService->saveStaff([
            'staff_code' => 'NV9999',
            'full_name' => 'Nguyễn Thị Hoa',
            'email' => 'hoa.nguyen@salesflow.test',
            'phone' => '0912345678',
            'birthday' => '1996-08-20',
            'address' => 'Phố Hoàn Kiếm, Hà Nội',
            'province_id' => $hanoi?->id,
            'department_id' => $salesDept->id,
            'position' => 'Chuyên viên Bán hàng',
            'join_date' => '2024-03-01',
            'is_active' => true,
        ]);

        self::assertDatabaseHas('staff', [
            'id' => $staff->id,
            'staff_code' => 'NV9999',
            'full_name' => 'Nguyễn Thị Hoa',
            'email' => 'hoa.nguyen@salesflow.test',
            'phone' => '0912345678',
            'department_id' => $salesDept->id,
        ]);
        self::assertSame('1996-08-20', $staff->birthday?->format('Y-m-d'));
    }

    public function test_it_creates_staff_record_and_user_invitation_when_inviting_via_email(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();

        $staff = $this->staffService->inviteStaff(
            'Trần Văn Nam',
            'nam.tran@salesflow.test',
            $salesDept->id,
            'sales',
            $admin
        );

        self::assertDatabaseHas('staff', [
            'id' => $staff->id,
            'full_name' => 'Trần Văn Nam',
            'email' => 'nam.tran@salesflow.test',
            'department_id' => $salesDept->id,
        ]);

        self::assertDatabaseHas('user_invitations', [
            'email' => 'nam.tran@salesflow.test',
            'name' => 'Trần Văn Nam',
        ]);
    }

    public function test_it_generates_staff_code_by_department(): void
    {
        $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();
        $code = $this->staffService->generateStaffCode($salesDept->id);

        self::assertStringStartsWith('NV-SALES-', $code);
    }
}
