<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\LeadFilterData;
use App\Enums\DataScope;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Tag;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\Authorization\DataScopeService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

final readonly class LeadDirectoryService
{
    public function __construct(
        private LeadRepository $leads,
        private UserRepository $users,
        private DepartmentRepository $departments,
        private DataScopeService $dataScope,
    ) {}

    /** @return LengthAwarePaginator<int, Lead> */
    public function paginate(
        User $actor,
        LeadFilterData $filters,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->leads->paginateVisibleTo($actor, $filters, $perPage);
    }

    public function visibleTotal(User $actor): int
    {
        return $this->leads->visibleTo($actor)->count();
    }

    public function makeFilters(
        string $search,
        string $status,
        string $priority,
        string $source,
        string $tag,
        string $owner,
        string $department,
        string $dateFrom,
        string $dateTo,
        string $sortBy,
        string $sortDirection,
    ): LeadFilterData {
        return new LeadFilterData(
            search: $search,
            status: LeadStatus::tryFrom($status),
            priority: LeadPriority::tryFrom($priority),
            sourceId: $this->positiveId($source),
            tagId: $this->positiveId($tag),
            ownerId: $this->positiveId($owner),
            departmentId: $this->positiveId($department),
            createdFrom: $this->date($dateFrom),
            createdTo: $this->date($dateTo),
            sortBy: $sortBy,
            sortDirection: $sortDirection,
        );
    }

    /** @return Collection<int, LeadSource> */
    public function sourceOptions(): Collection
    {
        return LeadSource::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'code', 'color']);
    }

    /** @return Collection<int, Tag> */
    public function tagOptions(): Collection
    {
        return Tag::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'color']);
    }

    /** @return Collection<int, User> */
    public function ownerOptions(User $actor): Collection
    {
        return $this->users->visibleTo($actor)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'department_id', 'name', 'email']);
    }

    /** @return Collection<int, Department> */
    public function departmentOptions(User $actor): Collection
    {
        $onlyIds = match ($this->dataScope->resolve($actor)) {
            DataScope::Department, DataScope::Owned => $actor->department_id === null
                ? []
                : [$actor->department_id],
            DataScope::All, DataScope::ReadOnly => null,
        };

        return $this->departments->userFilterOptions($onlyIds);
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        return collect(LeadStatus::cases())
            ->mapWithKeys(static fn (LeadStatus $status): array => [$status->value => $status->label()])
            ->all();
    }

    /** @return array<string, string> */
    public function priorityOptions(): array
    {
        return collect(LeadPriority::cases())
            ->mapWithKeys(static fn (LeadPriority $priority): array => [$priority->value => $priority->label()])
            ->all();
    }

    /** @return array<string, string> */
    public function sortOptions(): array
    {
        return [
            'created_at' => 'Ngày tạo',
            'full_name' => 'Họ và tên',
            'status' => 'Trạng thái',
            'priority' => 'Ưu tiên',
            'estimated_value' => 'Giá trị dự kiến',
        ];
    }

    private function positiveId(string $value): ?int
    {
        return ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function date(string $value): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $date : null;
    }
}
