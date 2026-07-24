<?php

namespace Database\Seeders;

use App\Models\Department;
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

        $itDepartment = Department::query()->where('code', 'IT')->firstOrFail();
        $itAdmin = User::query()->updateOrCreate(['email' => 'it.admin@salesflow.test'], [
            'department_id' => $itDepartment->getKey(),
            'name' => 'SalesFlow IT Admin',
            'email_verified_at' => now(),
            'password' => Hash::make('SalesFlow@123'),
            'is_active' => true,
        ]);
        $itAdmin->syncRoles('admin');

        $this->call([
            DemoUserSeeder::class,
            DemoLeadSeeder::class,
            DemoCompanySeeder::class,
            DemoContactSeeder::class,
            DemoPipelineSeeder::class,
            DemoOpportunitySeeder::class,
        ]);
    }
}
