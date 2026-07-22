<?php

declare(strict_types=1);

$permissionGroups = [
    'users' => [
        'label' => 'Người dùng',
        'permissions' => [
            'users.view' => 'Xem người dùng',
            'users.create' => 'Tạo người dùng',
            'users.update' => 'Cập nhật người dùng',
            'users.delete' => 'Xóa người dùng',
        ],
    ],
    'leads' => [
        'label' => 'Khách hàng tiềm năng',
        'permissions' => [
            'leads.view' => 'Xem lead trong phạm vi dữ liệu',
            'leads.view-all' => 'Xem lead ngoài phạm vi sở hữu',
            'leads.create' => 'Tạo lead',
            'leads.update' => 'Cập nhật lead trong phạm vi dữ liệu',
            'leads.update-all' => 'Cập nhật lead ngoài phạm vi sở hữu',
            'leads.delete' => 'Xóa lead',
            'leads.assign' => 'Gán người phụ trách lead',
            'leads.convert' => 'Chuyển đổi lead',
            'leads.import' => 'Nhập lead',
            'leads.export' => 'Xuất lead',
        ],
    ],
    'companies' => [
        'label' => 'Công ty',
        'permissions' => [
            'companies.view' => 'Xem công ty',
            'companies.create' => 'Tạo công ty',
            'companies.update' => 'Cập nhật công ty',
            'companies.delete' => 'Xóa công ty',
        ],
    ],
    'contacts' => [
        'label' => 'Người liên hệ',
        'permissions' => [
            'contacts.view' => 'Xem người liên hệ',
            'contacts.create' => 'Tạo người liên hệ',
            'contacts.update' => 'Cập nhật người liên hệ',
            'contacts.delete' => 'Xóa người liên hệ',
        ],
    ],
    'opportunities' => [
        'label' => 'Cơ hội bán hàng',
        'permissions' => [
            'opportunities.view' => 'Xem cơ hội trong phạm vi dữ liệu',
            'opportunities.view-all' => 'Xem cơ hội ngoài phạm vi sở hữu',
            'opportunities.create' => 'Tạo cơ hội',
            'opportunities.update' => 'Cập nhật cơ hội',
            'opportunities.delete' => 'Xóa cơ hội',
            'opportunities.change-stage' => 'Chuyển giai đoạn cơ hội',
            'opportunities.close' => 'Đóng cơ hội',
        ],
    ],
    'pipelines' => [
        'label' => 'Pipeline',
        'permissions' => [
            'pipelines.view' => 'Xem pipeline',
            'pipelines.manage' => 'Quản lý pipeline',
        ],
    ],
    'activities' => [
        'label' => 'Hoạt động',
        'permissions' => [
            'activities.view' => 'Xem hoạt động',
            'activities.create' => 'Tạo hoạt động',
            'activities.update' => 'Cập nhật hoạt động',
            'activities.delete' => 'Xóa hoạt động',
        ],
    ],
    'tasks' => [
        'label' => 'Công việc',
        'permissions' => [
            'tasks.view' => 'Xem công việc trong phạm vi dữ liệu',
            'tasks.view-all' => 'Xem công việc ngoài phạm vi sở hữu',
            'tasks.create' => 'Tạo công việc',
            'tasks.update' => 'Cập nhật công việc',
            'tasks.delete' => 'Xóa công việc',
            'tasks.assign' => 'Gán người thực hiện công việc',
        ],
    ],
    'reports' => [
        'label' => 'Báo cáo',
        'permissions' => [
            'reports.view' => 'Xem báo cáo',
            'reports.export' => 'Xuất báo cáo',
        ],
    ],
    'settings' => [
        'label' => 'Cài đặt',
        'permissions' => [
            'settings.manage' => 'Quản lý cài đặt hệ thống',
        ],
    ],
    'audit-logs' => [
        'label' => 'Nhật ký kiểm toán',
        'permissions' => [
            'audit-logs.view' => 'Xem nhật ký kiểm toán',
        ],
    ],
];

$allPermissions = array_merge(...array_values(array_map(
    static fn (array $group): array => array_keys($group['permissions']),
    $permissionGroups,
)));

return [
    'display_timezone' => env('CRM_DISPLAY_TIMEZONE', 'Asia/Ho_Chi_Minh'),

    // Only rendered by the login page when APP_ENV=local.
    'local_login_password' => env('CRM_LOCAL_LOGIN_PASSWORD', 'SalesFlow@123'),

    'rbac' => [
        'guard' => 'web',
        'super_admin_role' => 'super-admin',
        'administrator_roles' => ['super-admin', 'admin'],
        'data_scopes' => ['all', 'department', 'owned', 'read-only'],
        'permission_groups' => $permissionGroups,
        'roles' => [
            'super-admin' => [
                'label' => 'Super Admin',
                'description' => 'Toàn quyền hệ thống thông qua Gate bypass.',
                'data_scope' => 'all',
                'permissions' => [],
            ],
            'admin' => [
                'label' => 'Admin',
                'description' => 'Quản trị người dùng, cấu hình và toàn bộ dữ liệu CRM.',
                'data_scope' => 'all',
                'permissions' => $allPermissions,
            ],
            'sales-manager' => [
                'label' => 'Sales Manager',
                'description' => 'Điều hành dữ liệu bán hàng trong phạm vi phòng ban.',
                'data_scope' => 'department',
                'permissions' => [
                    'users.view',
                    'leads.view', 'leads.view-all', 'leads.create', 'leads.update', 'leads.update-all',
                    'leads.delete', 'leads.assign', 'leads.convert', 'leads.import', 'leads.export',
                    'companies.view', 'companies.create', 'companies.update', 'companies.delete',
                    'contacts.view', 'contacts.create', 'contacts.update', 'contacts.delete',
                    'opportunities.view', 'opportunities.view-all', 'opportunities.create',
                    'opportunities.update', 'opportunities.delete', 'opportunities.change-stage', 'opportunities.close',
                    'pipelines.view',
                    'activities.view', 'activities.create', 'activities.update', 'activities.delete',
                    'tasks.view', 'tasks.view-all', 'tasks.create', 'tasks.update', 'tasks.delete', 'tasks.assign',
                    'reports.view', 'reports.export',
                ],
            ],
            'sales' => [
                'label' => 'Sales',
                'description' => 'Thao tác dữ liệu bán hàng do chính người dùng sở hữu.',
                'data_scope' => 'owned',
                'permissions' => [
                    'leads.view', 'leads.create', 'leads.update', 'leads.delete', 'leads.convert', 'leads.export',
                    'companies.view', 'companies.create', 'companies.update', 'companies.delete',
                    'contacts.view', 'contacts.create', 'contacts.update', 'contacts.delete',
                    'opportunities.view', 'opportunities.create', 'opportunities.update',
                    'opportunities.delete', 'opportunities.change-stage', 'opportunities.close',
                    'pipelines.view',
                    'activities.view', 'activities.create', 'activities.update', 'activities.delete',
                    'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
                    'reports.view',
                ],
            ],
            'viewer' => [
                'label' => 'Viewer',
                'description' => 'Chỉ đọc dữ liệu CRM được cấp quyền truy cập.',
                'data_scope' => 'read-only',
                'permissions' => [
                    'leads.view',
                    'companies.view',
                    'contacts.view',
                    'opportunities.view',
                    'pipelines.view',
                    'activities.view',
                    'tasks.view',
                    'reports.view',
                ],
            ],
        ],
    ],
];
