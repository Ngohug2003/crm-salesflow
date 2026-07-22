<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Data\UserListFilters;
use App\Models\Department;
use App\Models\User;
use App\Services\UserDirectoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class UserList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $department = 'all';

    #[Url(except: 'all')]
    public string $role = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', User::class);

        return $this->service()->paginate(
            $this->currentUser(),
            new UserListFilters($this->search, $this->department, $this->role, $this->status),
        );
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function departmentOptions(): Collection
    {
        return $this->service()->departmentOptions($this->currentUser());
    }

    /** @return array<string, string> */
    #[Computed]
    public function roleOptions(): array
    {
        return $this->service()->roleOptions();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->department = 'all';
        $this->role = 'all';
        $this->status = 'all';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.users.user-list');
    }

    private function service(): UserDirectoryService
    {
        return app(UserDirectoryService::class);
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
