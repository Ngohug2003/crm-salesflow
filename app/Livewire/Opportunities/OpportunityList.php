<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Data\OpportunityFilterData;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\OpportunityManagementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class OpportunityList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'pipeline', history: true)]
    public string $pipelineId = '';

    #[Url(as: 'stage', history: true)]
    public string $stageId = '';

    #[Url(as: 'status', history: true)]
    public string $status = '';

    #[Url(as: 'sort', history: true)]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir', history: true)]
    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public ?int $confirmingDeleteOpportunityId = null;

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Opportunity::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPipelineId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'pipelineId', 'stageId', 'status');
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function confirmDeleteOpportunity(int $opportunityId): void
    {
        $this->confirmingDeleteOpportunityId = $opportunityId;
    }

    public function deleteConfirmedOpportunity(): void
    {
        if ($this->confirmingDeleteOpportunityId !== null) {
            $this->deleteOpportunity($this->confirmingDeleteOpportunityId);
            $this->confirmingDeleteOpportunityId = null;
        }
    }

    public function deleteOpportunity(int $opportunityId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityManagementService $service */
        $service = app(OpportunityManagementService::class);

        try {
            $service->delete($actor, $opportunityId);
            session()->flash('message', 'Đã xóa Cơ hội bán hàng thành công.');
        } catch (\Throwable $e) {
            $this->addError('opportunity_error', $e->getMessage());
        }
    }

    /** @return LengthAwarePaginator<int, Opportunity> */
    #[Computed]
    public function opportunities(): LengthAwarePaginator
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityManagementService $service */
        $service = app(OpportunityManagementService::class);

        return $service->list($actor, $this->buildFilterData(), $this->perPage);
    }

    /** @return array{total_count: int, total_amount: float, total_weighted_value: float} */
    #[Computed]
    public function summary(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityManagementService $service */
        $service = app(OpportunityManagementService::class);

        return $service->summarize($actor, $this->buildFilterData());
    }

    /** @return Collection<int, Pipeline> */
    #[Computed]
    public function pipelines(): Collection
    {
        return Pipeline::query()->where('is_active', true)->with('stages')->get();
    }

    private function buildFilterData(): OpportunityFilterData
    {
        return new OpportunityFilterData(
            search: $this->search !== '' ? $this->search : null,
            pipelineId: $this->pipelineId !== '' ? (int) $this->pipelineId : null,
            stageId: $this->stageId !== '' ? (int) $this->stageId : null,
            status: $this->status !== '' ? $this->status : null,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    public function render(): View
    {
        return view('livewire.opportunities.opportunity-list');
    }
}
