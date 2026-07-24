<?php

declare(strict_types=1);

namespace App\Livewire\Contacts;

use App\Data\ContactFilterData;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactManagementService;
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
final class ContactList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $company = 'all';

    #[Url(except: 'all')]
    public string $primary = 'all';

    #[Url(except: 'all')]
    public string $owner = 'all';

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

    public function updatedCompany(): void
    {
        $this->resetPage();
    }

    public function updatedPrimary(): void
    {
        $this->resetPage();
    }

    public function updatedOwner(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'company', 'primary', 'owner', 'sort', 'direction']);
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

    /** @return LengthAwarePaginator<int, Contact> */
    #[Computed]
    public function contacts(): LengthAwarePaginator
    {
        /** @var User $actor */
        $actor = Auth::user();

        $filters = new ContactFilterData(
            search: $this->search,
            companyId: $this->company !== 'all' && ctype_digit($this->company) ? (int) $this->company : null,
            isPrimary: $this->primary === 'yes' ? true : ($this->primary === 'no' ? false : null),
            ownerId: $this->owner !== 'all' && ctype_digit($this->owner) ? (int) $this->owner : null,
            sortBy: $this->sort,
            sortDirection: $this->direction,
        );

        /** @var ContactManagementService $service */
        $service = app(ContactManagementService::class);

        return $service->list($actor, $filters, $this->perPage);
    }

    /** @return Collection<int, Company> */
    #[Computed]
    public function companies(): Collection
    {
        return Company::query()->orderBy('name')->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Contact::class);

        return view('livewire.contacts.contact-list');
    }
}
