<?php

declare(strict_types=1);

namespace App\Livewire\Roles;

use App\Models\User;
use App\Services\Rbac\PermissionMatrixService;
use App\Services\Rbac\RolePermissionManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class PermissionMatrixView extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $module = 'all';

    #[Url(as: 'role', except: '')]
    public string $selectedRole = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    /** @var list<string> */
    public array $initialPermissions = [];

    public ?string $pendingRole = null;

    public bool $showDiscardConfirmation = false;

    public bool $showSaveConfirmation = false;

    public bool $showResetConfirmation = false;

    public function mount(
        PermissionMatrixService $matrixService,
        RolePermissionManagementService $managementService,
    ): void {
        $this->authorizeAccess();

        $roleNames = array_column($matrixService->getRolesDefinition(), 'name');
        if (! in_array($this->selectedRole, $roleNames, true)) {
            $this->selectedRole = $managementService->editableRoleNames($this->actor())[0]
                ?? $roleNames[0]
                ?? '';
        }

        $this->loadSelectedRole($managementService);
    }

    public function clearFilters(): void
    {
        $this->authorizeAccess();
        $this->reset('search', 'module');
    }

    public function selectRole(string $roleName, RolePermissionManagementService $service): void
    {
        $this->authorizeAccess();
        $this->assertKnownRole($roleName);

        if ($roleName === $this->selectedRole) {
            return;
        }

        if ($this->hasUnsavedChanges()) {
            $this->pendingRole = $roleName;
            $this->showDiscardConfirmation = true;

            return;
        }

        $this->selectedRole = $roleName;
        $this->loadSelectedRole($service);
    }

    public function discardAndSelectRole(RolePermissionManagementService $service): void
    {
        $this->authorizeAccess();

        if ($this->pendingRole === null) {
            $this->showDiscardConfirmation = false;

            return;
        }

        $this->selectedRole = $this->pendingRole;
        $this->pendingRole = null;
        $this->showDiscardConfirmation = false;
        $this->loadSelectedRole($service);
    }

    public function cancelRoleSelection(): void
    {
        $this->authorizeAccess();
        $this->pendingRole = null;
        $this->showDiscardConfirmation = false;
    }

    public function togglePermission(string $permission, RolePermissionManagementService $service): void
    {
        $this->authorizeAccess();

        abort_unless(
            $service->canTogglePermission($this->actor(), $this->selectedRole, $permission),
            403,
            'Bạn không được phép thay đổi Permission này.',
        );

        if (in_array($permission, $this->selectedPermissions, true)) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, [$permission]));
        } else {
            $this->selectedPermissions[] = $permission;
            sort($this->selectedPermissions);
        }
    }

    public function openSaveConfirmation(RolePermissionManagementService $service): void
    {
        $this->authorizeAccess();
        abort_unless($service->canEditRole($this->actor(), $this->selectedRole), 403);

        if ($this->hasUnsavedChanges()) {
            $this->showSaveConfirmation = true;
        }
    }

    public function savePermissions(RolePermissionManagementService $service): void
    {
        $this->authorizeAccess();

        $service->updateRolePermissions(
            $this->actor(),
            $this->selectedRole,
            $this->selectedPermissions,
        );

        $this->showSaveConfirmation = false;
        $this->loadSelectedRole($service);
        session()->flash('success', 'Đã cập nhật quyền cho vai trò thành công.');
    }

    public function openResetConfirmation(RolePermissionManagementService $service): void
    {
        $this->authorizeAccess();
        abort_unless($service->canEditRole($this->actor(), $this->selectedRole), 403);
        $this->showResetConfirmation = true;
    }

    public function resetToDefaults(RolePermissionManagementService $service): void
    {
        $this->authorizeAccess();
        $service->resetRolePermissions($this->actor(), $this->selectedRole);

        $this->showResetConfirmation = false;
        $this->loadSelectedRole($service);
        session()->flash('success', 'Đã khôi phục bộ quyền mặc định của vai trò.');
    }

    public function render(
        PermissionMatrixService $matrixService,
        RolePermissionManagementService $managementService,
    ): View {
        $this->authorizeAccess();

        $matrix = $matrixService->getMatrix();
        $modules = $this->filterModules($matrix['modules']);
        $actor = $this->actor();
        $editablePermissionNames = [];

        foreach ($matrixService->getModulesDefinition() as $module) {
            foreach ($module['permissions'] as $permission) {
                if ($managementService->canTogglePermission($actor, $this->selectedRole, $permission['name'])) {
                    $editablePermissionNames[] = $permission['name'];
                }
            }
        }

        $roles = array_map(
            fn (array $role): array => [
                ...$role,
                'editable' => $managementService->canEditRole($actor, $role['name']),
                'selected' => $role['name'] === $this->selectedRole,
            ],
            $matrix['roles'],
        );

        return view('livewire.roles.permission-matrix-view', [
            'roles' => $roles,
            'modules' => $modules,
            'allModuleKeys' => array_column($matrix['modules'], 'key', 'label'),
            'editablePermissionNames' => $editablePermissionNames,
            'hasUnsavedChanges' => $this->hasUnsavedChanges(),
            'selectedRoleDefinition' => collect($roles)->firstWhere('name', $this->selectedRole),
        ]);
    }

    private function loadSelectedRole(RolePermissionManagementService $service): void
    {
        if ($this->selectedRole === '') {
            $this->selectedPermissions = [];
            $this->initialPermissions = [];

            return;
        }

        $permissions = $service->permissionsForRole($this->selectedRole);
        sort($permissions);
        $this->selectedPermissions = $permissions;
        $this->initialPermissions = $permissions;
    }

    private function hasUnsavedChanges(): bool
    {
        $current = $this->selectedPermissions;
        $initial = $this->initialPermissions;
        sort($current);
        sort($initial);

        return $current !== $initial;
    }

    /**
     * @param  list<array{
     *     key: string,
     *     label: string,
     *     permissions: list<array{name: string, label: string, roles: array<string, bool>}>
     * }>  $modules
     * @return list<array{
     *     key: string,
     *     label: string,
     *     permissions: list<array{name: string, label: string, roles: array<string, bool>}>
     * }>
     */
    private function filterModules(array $modules): array
    {
        if ($this->module !== 'all') {
            $modules = array_values(array_filter(
                $modules,
                fn (array $item): bool => $item['key'] === $this->module,
            ));
        }

        if (trim($this->search) === '') {
            return $modules;
        }

        $query = mb_strtolower(trim($this->search));
        foreach ($modules as $index => $item) {
            $modules[$index]['permissions'] = array_values(array_filter(
                $item['permissions'],
                fn (array $permission): bool => str_contains(mb_strtolower($permission['name']), $query)
                    || str_contains(mb_strtolower($permission['label']), $query),
            ));
        }

        return array_values(array_filter(
            $modules,
            fn (array $item): bool => $item['permissions'] !== [],
        ));
    }

    private function assertKnownRole(string $roleName): void
    {
        abort_unless(array_key_exists($roleName, config('crm.rbac.roles', [])), 404);
    }

    private function authorizeAccess(): void
    {
        Gate::authorize('roles.manage');
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
