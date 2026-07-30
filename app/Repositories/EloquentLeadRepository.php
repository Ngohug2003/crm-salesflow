<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\LeadFilterData;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Services\Authorization\DataScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentLeadRepository implements LeadRepository
{
    /** @var array<string, string> */
    private const array SORT_COLUMNS = [
        'created_at' => 'leads.created_at',
        'full_name' => 'leads.full_name',
        'status' => 'leads.status',
        'priority' => 'leads.priority',
        'estimated_value' => 'leads.estimated_value',
        'score' => 'leads.score',
    ];

    public function __construct(private DataScopeService $dataScope) {}

    public function countVisibleTo(User $actor): int
    {
        return $this->visibleTo($actor)->count();
    }

    /** @return Builder<Lead> */
    private function visibleTo(User $actor): Builder
    {
        return $this->dataScope->apply(
            Lead::query(),
            $actor,
            'owner_id',
            'department_id',
        );
    }

    /** @return Builder<Lead> */
    private function filteredVisibleTo(User $actor, LeadFilterData $filters): Builder
    {
        $search = trim($filters->search);
        $sortColumn = self::SORT_COLUMNS[$filters->sortBy] ?? self::SORT_COLUMNS['created_at'];
        $sortDirection = strtolower($filters->sortDirection) === 'asc' ? 'asc' : 'desc';

        return $this->visibleTo($actor)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = "%{$search}%";

                $query->where(function (Builder $query) use ($like): void {
                    $query->whereLike('full_name', $like, caseSensitive: false)
                        ->orWhereLike('email', $like, caseSensitive: false)
                        ->orWhereLike('phone', $like, caseSensitive: false)
                        ->orWhereLike('secondary_phone', $like, caseSensitive: false)
                        ->orWhereLike('company_name', $like, caseSensitive: false);
                });
            })
            ->when(
                $filters->status !== null,
                fn (Builder $query): Builder => $query->where('status', $filters->status),
            )
            ->when(
                $filters->priority !== null,
                fn (Builder $query): Builder => $query->where('priority', $filters->priority),
            )
            ->when(
                $filters->scoreLevel !== null && $filters->scoreLevel !== '' && $filters->scoreLevel !== 'all',
                function (Builder $query) use ($filters): void {
                    match ($filters->scoreLevel) {
                        'hot' => $query->where('score', '>=', 70),
                        'warm' => $query->where('score', '>=', 40)->where('score', '<', 70),
                        'cold' => $query->where('score', '<', 40),
                        default => null,
                    };
                },
            )
            ->when(
                $filters->sourceId !== null && $filters->sourceId > 0,
                fn (Builder $query): Builder => $query->where('lead_source_id', $filters->sourceId),
            )
            ->when(
                $filters->tagId !== null && $filters->tagId > 0,
                fn (Builder $query): Builder => $query->whereHas(
                    'tags',
                    fn (Builder $tagQuery): Builder => $tagQuery->where('tags.id', $filters->tagId),
                ),
            )
            ->when(
                $filters->ownerId !== null && $filters->ownerId > 0,
                fn (Builder $query): Builder => $query->where('owner_id', $filters->ownerId),
            )
            ->when(
                $filters->departmentId !== null && $filters->departmentId > 0,
                fn (Builder $query): Builder => $query->where('department_id', $filters->departmentId),
            )
            ->when(
                $filters->createdFrom !== null,
                fn (Builder $query): Builder => $query->where(
                    'created_at',
                    '>=',
                    $filters->createdFrom?->startOfDay(),
                ),
            )
            ->when(
                $filters->createdTo !== null,
                fn (Builder $query): Builder => $query->where(
                    'created_at',
                    '<=',
                    $filters->createdTo?->endOfDay(),
                ),
            )
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('leads.id', $sortDirection);
    }

    public function paginateVisibleTo(
        User $actor,
        LeadFilterData $filters,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->filteredVisibleTo($actor, $filters)
            ->with($this->relations())
            ->paginate(max(1, min(100, $perPage)));
    }

    public function findVisibleOrFail(User $actor, int $leadId): Lead
    {
        return $this->visibleTo($actor)
            ->with($this->relations())
            ->findOrFail($leadId);
    }

    public function findVisibleForUpdateOrFail(User $actor, int $leadId): Lead
    {
        return $this->visibleTo($actor)
            ->lockForUpdate()
            ->findOrFail($leadId);
    }

    /** @return Builder<Lead> */
    private function trashedVisibleTo(User $actor): Builder
    {
        return $this->dataScope->apply(
            Lead::query()->onlyTrashed(),
            $actor,
            'owner_id',
            'department_id',
        );
    }

    public function paginateTrashedVisibleTo(User $actor, string $search, int $perPage = 15): LengthAwarePaginator
    {
        $search = trim($search);

        return $this->trashedVisibleTo($actor)
            ->with($this->relations())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = "%{$search}%";

                $query->where(function (Builder $query) use ($like): void {
                    $query->whereLike('full_name', $like, caseSensitive: false)
                        ->orWhereLike('email', $like, caseSensitive: false)
                        ->orWhereLike('phone', $like, caseSensitive: false)
                        ->orWhereLike('company_name', $like, caseSensitive: false);
                });
            })
            ->latest('deleted_at')
            ->latest('id')
            ->paginate(max(1, min(100, $perPage)));
    }

    public function findTrashedVisibleOrFail(User $actor, int $leadId): Lead
    {
        return $this->trashedVisibleTo($actor)
            ->with($this->relations())
            ->findOrFail($leadId);
    }

    public function findTrashedVisibleForUpdateOrFail(User $actor, int $leadId): Lead
    {
        return $this->trashedVisibleTo($actor)
            ->lockForUpdate()
            ->findOrFail($leadId);
    }

    public function duplicateCandidates(
        User $actor,
        ?string $email,
        array $phones,
        ?int $excludeLeadId = null,
    ): Collection {
        $phones = array_values(array_unique(array_filter($phones)));

        if ($email === null && $phones === []) {
            return new Collection;
        }

        return $this->dataScope->apply(
            Lead::query()->withTrashed(),
            $actor,
            'owner_id',
            'department_id',
        )
            ->when($excludeLeadId !== null, fn (Builder $query): Builder => $query->whereKeyNot($excludeLeadId))
            ->where(function (Builder $query) use ($email, $phones): void {
                $query
                    ->when($email !== null, fn (Builder $query): Builder => $query->where('email_normalized', $email))
                    ->when($phones !== [], function (Builder $query) use ($phones, $email): void {
                        $method = $email === null ? 'where' : 'orWhere';

                        $query->{$method}(function (Builder $query) use ($phones): void {
                            $query->whereIn('phone_normalized', $phones)
                                ->orWhereIn('secondary_phone_normalized', $phones);
                        });
                    });
            })
            ->with($this->relations())
            ->orderBy('id')
            ->limit(10)
            ->get();
    }

    public function create(array $attributes): Lead
    {
        return Lead::query()->create($attributes);
    }

    public function update(Lead $lead, array $attributes): Lead
    {
        $lead->fill($attributes)->save();

        return $lead->refresh();
    }

    public function syncTags(Lead $lead, array $tagIds): Lead
    {
        $lead->tags()->sync($tagIds);

        return $lead->load($this->relations());
    }

    public function softDelete(Lead $lead): Lead
    {
        $lead->delete();

        return $lead;
    }

    public function restore(Lead $lead): Lead
    {
        $lead->restore();

        return $lead->refresh()->load($this->relations());
    }

    /** @return list<string> */
    private function relations(): array
    {
        return [
            'source:id,name,code,color',
            'owner:id,name,email',
            'department:id,name,code',
            'provinceUnit:id,code,name,full_name',
            'ward:id,province_id,code,name,full_name',
            'tags:id,name,slug,color',
            'createdBy:id,name,email',
            'updatedBy:id,name,email',
        ];
    }
}
