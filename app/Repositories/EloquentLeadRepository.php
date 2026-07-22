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

final readonly class EloquentLeadRepository implements LeadRepository
{
    /** @var array<string, string> */
    private const array SORT_COLUMNS = [
        'created_at' => 'leads.created_at',
        'full_name' => 'leads.full_name',
        'status' => 'leads.status',
        'priority' => 'leads.priority',
        'estimated_value' => 'leads.estimated_value',
    ];

    public function __construct(private DataScopeService $dataScope) {}

    public function visibleTo(User $actor): Builder
    {
        return $this->dataScope->apply(
            Lead::query(),
            $actor,
            'owner_id',
            'department_id',
        );
    }

    public function filteredVisibleTo(User $actor, LeadFilterData $filters): Builder
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

    /** @return list<string> */
    private function relations(): array
    {
        return [
            'source:id,name,code,color',
            'owner:id,name,email',
            'department:id,name,code',
            'tags:id,name,slug,color',
        ];
    }
}
