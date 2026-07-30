<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\ContactFilterData;
use App\Models\Contact;
use App\Models\User;
use App\Repositories\Contracts\ContactRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentContactRepository implements ContactRepository
{
    /** @var array<string, string> */
    private const array SORT_COLUMNS = [
        'created_at' => 'contacts.created_at',
        'full_name' => 'contacts.full_name',
        'job_title' => 'contacts.job_title',
        'email' => 'contacts.email',
    ];

    public function __construct(private DataScopeService $dataScope) {}

    /** @return Builder<Contact> */
    private function visibleTo(User $actor): Builder
    {
        return $this->dataScope->apply(
            Contact::query(),
            $actor,
            'owner_id',
            'department_id',
        );
    }

    /** @return Builder<Contact> */
    private function filteredVisibleTo(User $actor, ContactFilterData $filters): Builder
    {
        $search = trim($filters->search);
        $sortColumn = self::SORT_COLUMNS[$filters->sortBy] ?? self::SORT_COLUMNS['created_at'];
        $sortDirection = strtolower($filters->sortDirection) === 'asc' ? 'asc' : 'desc';

        return $this->visibleTo($actor)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = "%{$search}%";

                $query->where(function (Builder $sub) use ($like): void {
                    $sub->whereLike('full_name', $like, caseSensitive: false)
                        ->orWhereLike('first_name', $like, caseSensitive: false)
                        ->orWhereLike('last_name', $like, caseSensitive: false)
                        ->orWhereLike('email', $like, caseSensitive: false)
                        ->orWhereLike('phone', $like, caseSensitive: false)
                        ->orWhereLike('job_title', $like, caseSensitive: false);
                });
            })
            ->when($filters->companyId !== null, fn (Builder $q) => $q->where('company_id', $filters->companyId))
            ->when($filters->isPrimary !== null, fn (Builder $q) => $q->where('is_primary', $filters->isPrimary))
            ->when($filters->ownerId !== null, fn (Builder $q) => $q->where('owner_id', $filters->ownerId))
            ->when($filters->departmentId !== null, fn (Builder $q) => $q->where('department_id', $filters->departmentId))
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('contacts.id', 'desc');
    }

    public function paginateVisible(User $actor, ContactFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return $this->filteredVisibleTo($actor, $filters)
            ->with(['company', 'owner', 'department'])
            ->paginate($perPage);
    }

    public function findVisibleOrFail(User $actor, int $id): Contact
    {
        /** @var Contact */
        return $this->visibleTo($actor)
            ->with(['company', 'owner', 'department', 'provinceUnit', 'ward', 'createdBy', 'updatedBy'])
            ->findOrFail($id);
    }

    public function findVisibleForUpdateOrFail(User $actor, int $id): Contact
    {
        /** @var Contact */
        return $this->visibleTo($actor)
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function create(array $data): Contact
    {
        return Contact::query()->create($data);
    }

    public function update(Contact $contact, array $data): Contact
    {
        $contact->update($data);

        return $contact->refresh();
    }

    public function softDelete(Contact $contact): Contact
    {
        $contact->delete();

        return $contact;
    }

    public function resetPrimaryContactsExcept(int $companyId, ?int $exceptContactId = null): void
    {
        Contact::query()
            ->where('company_id', $companyId)
            ->when($exceptContactId !== null, fn (Builder $q) => $q->where('id', '!=', $exceptContactId))
            ->update(['is_primary' => false]);
    }
}
