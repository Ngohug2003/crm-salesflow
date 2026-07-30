<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            RolePermissionSeeder::class,
            LeadTaxonomySeeder::class,
            VietnamAdministrativeUnitSeeder::class,
            QuoteApprovalSeeder::class,
        ]);

        $managementDepartment = Department::query()->where('code', 'MANAGEMENT')->firstOrFail();

        $admin = User::query()->updateOrCreate(['email' => 'admin@salesflow.test'], [
            'department_id' => $managementDepartment->getKey(),
            'name' => 'SalesFlow Admin',
            'email_verified_at' => now(),
            'password' => Hash::make('SalesFlow@123'),
            'is_active' => true,
        ]);

        $admin->syncRoles((string) config('crm.rbac.super_admin_role'));

        Staff::query()->updateOrCreate(['email' => $admin->email], [
            'user_id' => $admin->id,
            'staff_code' => 'NV-MGT-0000',
            'full_name' => $admin->name,
            'phone' => '0900000001',
            'birthday' => '1990-01-01',
            'address' => 'Trụ sở chính SalesFlow, Hà Nội',
            'department_id' => $admin->department_id,
            'position' => 'Super Admin System Owner',
            'join_date' => '2023-01-01',
            'is_active' => true,
        ]);

        $itDepartment = Department::query()->where('code', 'IT')->firstOrFail();
        $itAdmin = User::query()->updateOrCreate(['email' => 'it.admin@salesflow.test'], [
            'department_id' => $itDepartment->getKey(),
            'name' => 'SalesFlow IT Admin',
            'email_verified_at' => now(),
            'password' => Hash::make('SalesFlow@123'),
            'is_active' => true,
        ]);
        $itAdmin->syncRoles('admin');

        Staff::query()->updateOrCreate(['email' => $itAdmin->email], [
            'user_id' => $itAdmin->id,
            'staff_code' => 'NV-IT-0000',
            'full_name' => $itAdmin->name,
            'phone' => '0900000002',
            'birthday' => '1992-02-02',
            'address' => 'Trung tâm Công nghệ Thông tin SalesFlow',
            'department_id' => $itAdmin->department_id,
            'position' => 'IT System Administrator',
            'join_date' => '2023-02-01',
            'is_active' => true,
        ]);

        $this->call([
            DemoUserSeeder::class,
            DemoPipelineSeeder::class,
            FullDemoSeeder::class,
            StandardSalesPlaybookSeeder::class,
        ]);
    }
}
