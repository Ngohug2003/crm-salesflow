<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Tag;
use App\Models\User;
use App\Services\Export\ExportExecutionService;
use App\Services\Lead\LeadBulkActionService;
use App\Services\LeadDirectoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class LeadList extends Component
{
    use WithPagination;

    /** @var list<string> */
    private const array FILTER_PROPERTIES = [
        'search',
        'status',
        'priority',
        'scoreLevel',
        'source',
        'tag',
        'owner',
        'department',
        'dateFrom',
        'dateTo',
        'sort',
        'direction',
        'perPage',
    ];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(except: 'all')]
    public string $priority = 'all';

    #[Url(except: 'all')]
    public string $scoreLevel = 'all';

    #[Url(except: 'all')]
    public string $source = 'all';

    #[Url(except: 'all')]
    public string $tag = 'all';

    #[Url(except: 'all')]
    public string $owner = 'all';

    #[Url(except: 'all')]
    public string $department = 'all';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    #[Url(as: 'per_page', except: 15)]
    public int $perPage = 15;

    /** @var list<int|string> */
    public array $selectedLeadIds = [];

    public string $bulkOwnerId = '';

    public string $bulkAssignReason = '';

    public bool $showBulkAssignModal = false;

    public string $bulkStatus = '';

    public string $bulkStatusReason = '';

    public bool $showBulkStatusModal = false;

    public string $bulkTagId = '';

    public bool $showBulkTagModal = false;

    public bool $showBulkDeleteModal = false;

    public ?string $bulkFeedback = null;

    public function openBulkAssign(): void
    {
        $this->resetValidation();
        $this->bulkOwnerId = '';
        $this->bulkAssignReason = '';
        $this->showBulkAssignModal = true;
        $this->dispatch('modal-show', name: 'bulk-assign-modal');
    }

    public function cancelBulkAssign(): void
    {
        $this->showBulkAssignModal = false;
        $this->dispatch('modal-close', name: 'bulk-assign-modal');
    }

    public function executeBulkAssign(): void
    {
        $selected = $this->selectedIds();
        if ($selected === []) {
            return;
        }

        $validated = $this->validate([
            'bulkOwnerId' => ['nullable', 'integer'],
            'bulkAssignReason' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var LeadBulkActionService $bulkService */
        $bulkService = app(LeadBulkActionService::class);
        $ownerId = $validated['bulkOwnerId'] === '' ? null : (int) $validated['bulkOwnerId'];

        $res = $bulkService->bulkAssign($this->currentUser(), $selected, $ownerId, $validated['bulkAssignReason']);

        $this->bulkFeedback = "Phân công hàng loạt hoàn tất: {$res['success']} thành công, {$res['failed']} thất bại/bị chặn.";
        $this->clearSelection();
        $this->showBulkAssignModal = false;
        $this->dispatch('modal-close', name: 'bulk-assign-modal');
    }

    public function openBulkStatus(): void
    {
        $this->resetValidation();
        $this->bulkStatus = '';
        $this->bulkStatusReason = '';
        $this->showBulkStatusModal = true;
        $this->dispatch('modal-show', name: 'bulk-status-modal');
    }

    public function cancelBulkStatus(): void
    {
        $this->showBulkStatusModal = false;
        $this->dispatch('modal-close', name: 'bulk-status-modal');
    }

    public function executeBulkStatus(): void
    {
        $selected = $this->selectedIds();
        if ($selected === []) {
            return;
        }

        $validated = $this->validate([
            'bulkStatus' => ['required', 'string'],
            'bulkStatusReason' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var LeadBulkActionService $bulkService */
        $bulkService = app(LeadBulkActionService::class);
        $res = $bulkService->bulkUpdateStatus($this->currentUser(), $selected, $validated['bulkStatus'], $validated['bulkStatusReason']);

        $this->bulkFeedback = "Đổi trạng thái hàng loạt hoàn tất: {$res['success']} thành công, {$res['failed']} thất bại/bị chặn.";
        $this->clearSelection();
        $this->showBulkStatusModal = false;
        $this->dispatch('modal-close', name: 'bulk-status-modal');
    }

    public function openBulkTag(): void
    {
        $this->resetValidation();
        $this->bulkTagId = '';
        $this->showBulkTagModal = true;
        $this->dispatch('modal-show', name: 'bulk-tag-modal');
    }

    public function cancelBulkTag(): void
    {
        $this->showBulkTagModal = false;
        $this->dispatch('modal-close', name: 'bulk-tag-modal');
    }

    public function executeBulkTag(): void
    {
        $selected = $this->selectedIds();
        if ($selected === []) {
            return;
        }

        $validated = $this->validate([
            'bulkTagId' => ['required', 'integer'],
        ]);

        /** @var LeadBulkActionService $bulkService */
        $bulkService = app(LeadBulkActionService::class);
        $res = $bulkService->bulkAddTag($this->currentUser(), $selected, (int) $validated['bulkTagId']);

        $this->bulkFeedback = "Gán thẻ hàng loạt hoàn tất: {$res['success']} thành công, {$res['failed']} thất bại/bị chặn.";
        $this->clearSelection();
        $this->showBulkTagModal = false;
        $this->dispatch('modal-close', name: 'bulk-tag-modal');
    }

    public function openBulkDelete(): void
    {
        $this->showBulkDeleteModal = true;
        $this->dispatch('modal-show', name: 'bulk-delete-modal');
    }

    public function cancelBulkDelete(): void
    {
        $this->showBulkDeleteModal = false;
        $this->dispatch('modal-close', name: 'bulk-delete-modal');
    }

    public function executeBulkDelete(): void
    {
        $selected = $this->selectedIds();
        if ($selected === []) {
            return;
        }

        /** @var LeadBulkActionService $bulkService */
        $bulkService = app(LeadBulkActionService::class);
        $res = $bulkService->bulkDelete($this->currentUser(), $selected);

        $this->bulkFeedback = "Xóa hàng loạt hoàn tất: {$res['success']} thành công, {$res['failed']} thất bại/bị chặn.";
        $this->clearSelection();
        $this->showBulkDeleteModal = false;
        $this->dispatch('modal-close', name: 'bulk-delete-modal');
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', Lead::class);
        $this->normalizeUrlState();
    }

    /** @return LengthAwarePaginator<int, Lead> */
    #[Computed]
    public function leads(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Lead::class);

        return $this->service()->paginate(
            $this->currentUser(),
            $this->service()->makeFilters(
                $this->search,
                $this->status,
                $this->priority,
                $this->source,
                $this->tag,
                $this->owner,
                $this->department,
                $this->dateFrom,
                $this->dateTo,
                $this->sort,
                $this->direction,
                $this->scoreLevel,
            ),
            $this->perPage,
        );
    }

    #[Computed]
    public function visibleTotal(): int
    {
        return $this->service()->visibleTotal($this->currentUser());
    }

    /** @return Collection<int, LeadSource> */
    #[Computed]
    public function sourceOptions(): Collection
    {
        return $this->service()->sourceOptions();
    }

    /** @return Collection<int, Tag> */
    #[Computed]
    public function tagOptions(): Collection
    {
        return $this->service()->tagOptions();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function ownerOptions(): Collection
    {
        return $this->service()->ownerOptions($this->currentUser());
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function departmentOptions(): Collection
    {
        return $this->service()->departmentOptions($this->currentUser());
    }

    /** @return array<string, string> */
    #[Computed]
    public function statusOptions(): array
    {
        return $this->service()->statusOptions();
    }

    /** @return array<string, string> */
    #[Computed]
    public function priorityOptions(): array
    {
        return $this->service()->priorityOptions();
    }

    /** @return array<string, string> */
    #[Computed]
    public function sortOptions(): array
    {
        return $this->service()->sortOptions();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->status !== 'all'
            || $this->priority !== 'all'
            || $this->scoreLevel !== 'all'
            || $this->source !== 'all'
            || $this->tag !== 'all'
            || $this->owner !== 'all'
            || $this->department !== 'all'
            || $this->dateFrom !== ''
            || $this->dateTo !== '';
    }

    #[Computed]
    public function allPageSelected(): bool
    {
        $pageIds = $this->currentPageLeadIds();

        return $pageIds !== [] && array_diff($pageIds, $this->selectedIds()) === [];
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTER_PROPERTIES, true)) {
            $this->normalizeUrlState();
            $this->resetPage();
            $this->clearSelection();
        }
    }

    public function updatedSelectedLeadIds(): void
    {
        $this->selectedLeadIds = array_values(array_intersect(
            $this->selectedIds(),
            $this->currentPageLeadIds(),
        ));
    }

    public function updatingPaginators(): void
    {
        $this->clearSelection();
    }

    public function togglePageSelection(): void
    {
        $pageIds = $this->currentPageLeadIds();

        $this->selectedLeadIds = $this->allPageSelected()
            ? []
            : $pageIds;
    }

    public function clearSelection(): void
    {
        $this->selectedLeadIds = [];
        unset($this->allPageSelected);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->priority = 'all';
        $this->source = 'all';
        $this->tag = 'all';
        $this->owner = 'all';
        $this->department = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->sort = 'created_at';
        $this->direction = 'desc';
        $this->perPage = 15;
        $this->resetPage();
        $this->clearSelection();
    }

    public function render(): View
    {
        return view('livewire.leads.lead-list');
    }

    /** @return list<int> */
    private function currentPageLeadIds(): array
    {
        return array_map(
            static fn (Lead $lead): int => (int) $lead->getKey(),
            $this->leads()->items(),
        );
    }

    /** @return list<int> */
    private function selectedIds(): array
    {
        return collect($this->selectedLeadIds)
            ->filter(static fn (mixed $id): bool => is_int($id) || ctype_digit((string) $id))
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeUrlState(): void
    {
        $this->status = array_key_exists($this->status, $this->service()->statusOptions()) ? $this->status : 'all';
        $this->priority = array_key_exists($this->priority, $this->service()->priorityOptions()) ? $this->priority : 'all';
        $this->source = $this->normalizeIdFilter($this->source);
        $this->tag = $this->normalizeIdFilter($this->tag);
        $this->owner = $this->normalizeIdFilter($this->owner);
        $this->department = $this->normalizeIdFilter($this->department);
        $this->dateFrom = $this->normalizeDate($this->dateFrom);
        $this->dateTo = $this->normalizeDate($this->dateTo);
        $this->sort = array_key_exists($this->sort, $this->service()->sortOptions()) ? $this->sort : 'created_at';
        $this->direction = $this->direction === 'asc' ? 'asc' : 'desc';
        $this->perPage = in_array($this->perPage, [10, 15, 25, 50, 100], true) ? $this->perPage : 15;
    }

    private function normalizeIdFilter(string $value): string
    {
        return ctype_digit($value) && (int) $value > 0 ? $value : 'all';
    }

    private function normalizeDate(string $value): string
    {
        if (! preg_match('/^(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})$/', $value, $parts)) {
            return '';
        }

        return checkdate((int) $parts['month'], (int) $parts['day'], (int) $parts['year'])
            ? $value
            : '';
    }

    public ?int $exportBatchId = null;

    public ?string $exportDownloadUrl = null;

    public function exportCsv(ExportExecutionService $exportService): void
    {
        $actor = $this->currentUser();
        if (! Gate::allows('leads.view')) {
            throw new AuthorizationException('Bạn không có quyền xuất dữ liệu Lead.');
        }

        $filters = [
            'search' => $this->search,
            'status' => $this->status !== 'all' ? $this->status : null,
            'priority' => $this->priority !== 'all' ? $this->priority : null,
        ];

        $batch = $exportService->requestExport($actor, 'leads', array_filter($filters));
        $this->exportBatchId = $batch->id;
        $this->exportDownloadUrl = $exportService->getSignedDownloadUrl($batch);
    }

    private function service(): LeadDirectoryService
    {
        return app(LeadDirectoryService::class);
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
