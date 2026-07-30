<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Data\CompanyFilterData;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Services\CompanyManagementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class CompanyList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $industry = 'all';

    #[Url(except: 'all')]
    public string $size = 'all';

    #[Url(except: 'all')]
    public string $owner = 'all';

    #[Url(except: 'all')]
    public string $department = 'all';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    #[Url(except: 15)]
    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedIndustry(): void
    {
        $this->resetPage();
    }

    public function updatedSize(): void
    {
        $this->resetPage();
    }

    public function updatedOwner(): void
    {
        $this->resetPage();
    }

    public function updatedDepartment(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'industry', 'size', 'owner', 'department', 'sort', 'direction']);
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'desc';
        }

        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, Company> */
    #[Computed]
    public function companies(): LengthAwarePaginator
    {
        /** @var User $actor */
        $actor = Auth::user();

        $filters = new CompanyFilterData(
            search: $this->search,
            industry: $this->industry !== 'all' ? $this->industry : null,
            companySize: $this->size !== 'all' ? $this->size : null,
            ownerId: $this->owner !== 'all' && ctype_digit($this->owner) ? (int) $this->owner : null,
            departmentId: $this->department !== 'all' && ctype_digit($this->department) ? (int) $this->department : null,
            sortBy: $this->sort,
            sortDirection: $this->direction,
        );

        /** @var CompanyManagementService $service */
        $service = app(CompanyManagementService::class);

        return $service->list($actor, $filters, $this->perPage);
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function departments(): Collection
    {
        return Department::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return list<string> */
    #[Computed]
    public function industries(): array
    {
        return ['Công nghệ thông tin', 'Tài chính - Ngân hàng', 'Bất động sản', 'Sản xuất', 'Bán lẻ', 'Y tế - Dược phẩm', 'Giáo dục'];
    }

    /** @return list<string> */
    #[Computed]
    public function sizes(): array
    {
        return ['1-10 nhân sự', '11-50 nhân sự', '51-200 nhân sự', '201-500 nhân sự', '500+ nhân sự'];
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Company::class);

        return view('livewire.companies.company-list');
    }
}
