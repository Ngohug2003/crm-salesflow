<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

final class PermissionMatrixService
{
    /**
     * Get complete permission matrix dataset including roles, module groups, permissions, and matrix lookup.
     *
     * @return array{
     *     roles: list<array{name: string, label: string, description: string, data_scope: string}>,
     *     modules: list<array{
     *         key: string,
     *         label: string,
     *         permissions: list<array{name: string, label: string, roles: array<string, bool>}>
     *     }>
     * }
     */
    public function getMatrix(): array
    {
        $roles = $this->getRolesDefinition();
        $modules = $this->getModulesDefinition();

        // Build active permission map per role from DB if roles exist
        $rolePermissionsMap = [];
        foreach ($roles as $roleDef) {
            $rolePermissionsMap[$roleDef['name']] = [];
            try {
                $roleModel = Role::findByName($roleDef['name'], 'web');
                /** @var list<string> $permNames */
                $permNames = $roleModel->permissions()->pluck('name')->all();
                foreach ($permNames as $pName) {
                    $rolePermissionsMap[$roleDef['name']][$pName] = true;
                }
            } catch (Throwable) {
                // Fallback if role does not exist in DB
            }
        }

        // Hydrate roles matrix mapping for each permission
        foreach ($modules as $mIndex => $module) {
            foreach ($module['permissions'] as $pIndex => $perm) {
                $pName = $perm['name'];
                $matrix = [];
                foreach ($roles as $roleDef) {
                    $rName = $roleDef['name'];
                    // Super admin has all permissions
                    if ($rName === (string) config('crm.rbac.super_admin_role', 'super-admin')) {
                        $matrix[$rName] = true;
                    } else {
                        $matrix[$rName] = $rolePermissionsMap[$rName][$pName] ?? $this->defaultPermissionCheck($rName, $pName);
                    }
                }
                $modules[$mIndex]['permissions'][$pIndex]['roles'] = $matrix;
            }
        }

        return [
            'roles' => $roles,
            'modules' => $modules,
        ];
    }

    /**
     * Get 5 core system roles with human-readable labels and Data Scope rules.
     *
     * @return list<array{name: string, label: string, description: string, data_scope: string}>
     */
    public function getRolesDefinition(): array
    {
        return [
            [
                'name' => 'super-admin',
                'label' => 'Super Admin',
                'description' => 'Quản trị viên tối cao có toàn quyền trên toàn hệ thống.',
                'data_scope' => 'Toàn bộ dữ liệu (All)',
            ],
            [
                'name' => 'admin',
                'label' => 'Admin / IT Admin',
                'description' => 'Quản trị viên vận hành hệ thống, quản lý người dùng và cấu hình.',
                'data_scope' => 'Toàn bộ dữ liệu (All)',
            ],
            [
                'name' => 'sales-manager',
                'label' => 'Sales Manager',
                'description' => 'Trưởng phòng kinh doanh quản lý công việc và báo cáo phân hệ.',
                'data_scope' => 'Cây phòng ban (Department Tree)',
            ],
            [
                'name' => 'sales',
                'label' => 'NVKD (Sales)',
                'description' => 'Nhân viên kinh doanh trực tiếp xử lý Lead, Khách hàng & Cơ hội.',
                'data_scope' => 'Dữ liệu cá nhân (Own Only)',
            ],
            [
                'name' => 'viewer',
                'label' => 'Người xem (Viewer)',
                'description' => 'Tài khoản chỉ đọc dữ liệu theo phạm vi phòng ban.',
                'data_scope' => 'Phòng ban nội bộ (Department Only)',
            ],
        ];
    }

    /**
     * Get permissions catalog grouped by 7 modules.
     *
     * @return list<array{key: string, label: string, permissions: list<array{name: string, label: string, roles: array<string, bool>}>}>
     */
    public function getModulesDefinition(): array
    {
        return [
            [
                'key' => 'customers',
                'label' => '1. Khách hàng (Leads, Companies & Contacts)',
                'permissions' => [
                    ['name' => 'leads.view', 'label' => 'Xem danh sách & chi tiết Lead', 'roles' => []],
                    ['name' => 'leads.create', 'label' => 'Tạo mới Lead', 'roles' => []],
                    ['name' => 'leads.update', 'label' => 'Cập nhật thông tin Lead', 'roles' => []],
                    ['name' => 'leads.delete', 'label' => 'Xóa / Thùng rác Lead', 'roles' => []],
                    ['name' => 'leads.export', 'label' => 'Xuất danh sách Lead', 'roles' => []],
                    ['name' => 'leads.import', 'label' => 'Import danh sách Lead', 'roles' => []],
                    ['name' => 'companies.view', 'label' => 'Xem danh sách Doanh nghiệp', 'roles' => []],
                    ['name' => 'companies.create', 'label' => 'Tạo Doanh nghiệp mới', 'roles' => []],
                    ['name' => 'companies.update', 'label' => 'Cập nhật Doanh nghiệp', 'roles' => []],
                    ['name' => 'companies.delete', 'label' => 'Xóa Doanh nghiệp', 'roles' => []],
                    ['name' => 'contacts.view', 'label' => 'Xem danh sách Người liên hệ', 'roles' => []],
                    ['name' => 'contacts.create', 'label' => 'Tạo Người liên hệ mới', 'roles' => []],
                    ['name' => 'contacts.update', 'label' => 'Cập nhật Người liên hệ', 'roles' => []],
                    ['name' => 'contacts.delete', 'label' => 'Xóa Người liên hệ', 'roles' => []],
                ],
            ],
            [
                'key' => 'sales',
                'label' => '2. Quy trình & Cơ hội bán hàng (Pipelines & Opportunities)',
                'permissions' => [
                    ['name' => 'pipelines.view', 'label' => 'Xem quy trình bán hàng', 'roles' => []],
                    ['name' => 'pipelines.manage', 'label' => 'Quản lý / Cấu hình Quy trình & Giai đoạn', 'roles' => []],
                    ['name' => 'opportunities.view', 'label' => 'Xem danh sách & Kanban Cơ hội', 'roles' => []],
                    ['name' => 'opportunities.create', 'label' => 'Tạo Cơ hội mới', 'roles' => []],
                    ['name' => 'opportunities.update', 'label' => 'Cập nhật stage / thông tin Cơ hội', 'roles' => []],
                    ['name' => 'opportunities.delete', 'label' => 'Xóa Cơ hội bán hàng', 'roles' => []],
                ],
            ],
            [
                'key' => 'tasks',
                'label' => '3. Hoạt động & Công việc (Activities & Tasks)',
                'permissions' => [
                    ['name' => 'activities.view', 'label' => 'Xem nhật ký hoạt động', 'roles' => []],
                    ['name' => 'activities.create', 'label' => 'Ghi nhận cuộc gọi / email / cuộc họp', 'roles' => []],
                    ['name' => 'tasks.view', 'label' => 'Xem danh sách công việc', 'roles' => []],
                    ['name' => 'tasks.create', 'label' => 'Tạo mới công việc', 'roles' => []],
                    ['name' => 'tasks.update', 'label' => 'Cập nhật trạng thái / tiến độ công việc', 'roles' => []],
                    ['name' => 'tasks.delete', 'label' => 'Xóa công việc', 'roles' => []],
                ],
            ],
            [
                'key' => 'reports',
                'label' => '4. Báo cáo & Phân tích (Reports & Analytics)',
                'permissions' => [
                    ['name' => 'reports.view', 'label' => 'Xem Báo cáo Funnel, Doanh thu & Hiệu suất', 'roles' => []],
                    ['name' => 'reports.export', 'label' => 'Xuất file dữ liệu báo cáo', 'roles' => []],
                ],
            ],
            [
                'key' => 'io',
                'label' => '5. Nhập / Xuất dữ liệu (Import & Export)',
                'permissions' => [
                    ['name' => 'imports.manage', 'label' => 'Quản lý & thực thi Batch Import', 'roles' => []],
                    ['name' => 'exports.create', 'label' => 'Yêu cầu xuất file dữ liệu hệ thống', 'roles' => []],
                ],
            ],
            [
                'key' => 'administration',
                'label' => '6. Quản trị & Tổ chức (Users, Roles & Departments)',
                'permissions' => [
                    ['name' => 'users.view', 'label' => 'Xem danh sách tài khoản người dùng', 'roles' => []],
                    ['name' => 'users.manage', 'label' => 'Tạo, sửa, khóa tài khoản người dùng', 'roles' => []],
                    ['name' => 'departments.view', 'label' => 'Xem cây sơ đồ phòng ban', 'roles' => []],
                    ['name' => 'departments.manage', 'label' => 'Quản lý phòng ban & tổ chức', 'roles' => []],
                    ['name' => 'roles.view', 'label' => 'Xem vai trò & ma trận phân quyền', 'roles' => []],
                    ['name' => 'roles.assign', 'label' => 'Phân gán vai trò người dùng', 'roles' => []],
                ],
            ],
            [
                'key' => 'system',
                'label' => '7. Hệ thống & Kiểm toán (System Console & Audit Logs)',
                'permissions' => [
                    ['name' => 'system-console.view', 'label' => 'Truy cập System Console & Health Check', 'roles' => []],
                    ['name' => 'audit-logs.view', 'label' => 'Xem nhật ký kiểm toán hệ thống (Audit Logs)', 'roles' => []],
                ],
            ],
        ];
    }

    /**
     * Fallback mapping check based on RBAC Catalog definitions.
     */
    private function defaultPermissionCheck(string $role, string $permission): bool
    {
        if ($role === 'admin') {
            return true;
        }

        if ($role === 'sales-manager') {
            return ! str_contains($permission, 'system-console') && ! str_contains($permission, 'users.manage') && ! str_contains($permission, 'departments.manage');
        }

        if ($role === 'sales') {
            return in_array($permission, [
                'leads.view', 'leads.create', 'leads.update', 'leads.export',
                'companies.view', 'companies.create', 'companies.update',
                'contacts.view', 'contacts.create', 'contacts.update',
                'pipelines.view', 'opportunities.view', 'opportunities.create', 'opportunities.update',
                'activities.view', 'activities.create', 'tasks.view', 'tasks.create', 'tasks.update',
                'reports.view', 'exports.create',
            ], true);
        }

        if ($role === 'viewer') {
            return str_contains($permission, '.view');
        }

        return false;
    }
}
