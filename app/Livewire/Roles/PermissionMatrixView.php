<?php

declare(strict_types=1);

namespace App\Livewire\Roles;

use App\Models\User;
use App\Services\Rbac\PermissionMatrixService;
use Illuminate\Contracts\View\View;
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

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function clearFilters(): void
    {
        $this->authorizeAccess();
        $this->reset('search', 'module');
    }

    public function render(PermissionMatrixService $matrixService): View
    {
        $this->authorizeAccess();

        $matrix = $matrixService->getMatrix();
        $roles = $matrix['roles'];
        $modules = $matrix['modules'];

        // Filter by module if selected
        if ($this->module !== 'all') {
            $modules = array_values(array_filter(
                $modules,
                fn (array $m): bool => $m['key'] === $this->module
            ));
        }

        // Filter by search query if typed
        if (trim($this->search) !== '') {
            $query = mb_strtolower(trim($this->search));
            foreach ($modules as $mIndex => $m) {
                $filteredPerms = array_values(array_filter(
                    $m['permissions'],
                    fn (array $p): bool => str_contains(mb_strtolower($p['name']), $query)
                        || str_contains(mb_strtolower($p['label']), $query)
                ));
                $modules[$mIndex]['permissions'] = $filteredPerms;
            }
            // Filter out empty modules
            $modules = array_values(array_filter(
                $modules,
                fn (array $m): bool => count($m['permissions']) > 0
            ));
        }

        return view('livewire.roles.permission-matrix-view', [
            'roles' => $roles,
            'modules' => $modules,
            'allModuleKeys' => array_column($matrix['modules'], 'key', 'label'),
        ]);
    }

    private function authorizeAccess(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user !== null && $user->can('settings.manage')) {
            return;
        }

        abort(403, 'Bạn không có quyền truy cập Ma trận phân quyền.');
    }
}
