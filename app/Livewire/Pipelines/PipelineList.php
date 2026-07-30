<?php

declare(strict_types=1);

namespace App\Livewire\Pipelines;

use App\Data\PipelineFilterData;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\PipelineManagementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class PipelineList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'active', history: true)]
    public string $isActive = '';

    #[Url(as: 'sort', history: true)]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir', history: true)]
    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public ?int $confirmingDeletePipelineId = null;

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Pipeline::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingIsActive(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'isActive');
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function toggleDefault(int $pipelineId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var PipelineManagementService $service */
        $service = app(PipelineManagementService::class);

        $pipeline = $service->get($actor, $pipelineId);
        $service->update($actor, $pipeline->id, ['is_default' => true]);

        session()->flash('message', "Đã đặt quy trình '{$pipeline->name}' làm mặc định.");
    }

    public function toggleActive(int $pipelineId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var PipelineManagementService $service */
        $service = app(PipelineManagementService::class);

        $pipeline = $service->get($actor, $pipelineId);
        $newActiveState = ! $pipeline->is_active;
        $service->update($actor, $pipeline->id, ['is_active' => $newActiveState]);

        $statusText = $newActiveState ? 'kích hoạt' : 'tạm ngừng';
        session()->flash('message', "Đã {$statusText} quy trình '{$pipeline->name}'.");
    }

    public function confirmDeletePipeline(int $pipelineId): void
    {
        $this->confirmingDeletePipelineId = $pipelineId;
    }

    public function deleteConfirmedPipeline(): void
    {
        if ($this->confirmingDeletePipelineId !== null) {
            $this->deletePipeline($this->confirmingDeletePipelineId);
            $this->confirmingDeletePipelineId = null;
        }
    }

    public function deletePipeline(int $pipelineId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var PipelineManagementService $service */
        $service = app(PipelineManagementService::class);

        $deleted = $service->delete($actor, $pipelineId);
        session()->flash('message', "Đã xóa quy trình '{$deleted->name}'.");
    }

    /** @return LengthAwarePaginator<int, Pipeline> */
    #[Computed]
    public function pipelines(): LengthAwarePaginator
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var PipelineManagementService $service */
        $service = app(PipelineManagementService::class);

        $filters = new PipelineFilterData(
            search: $this->search !== '' ? $this->search : null,
            isActive: $this->isActive !== '' ? filter_var($this->isActive, FILTER_VALIDATE_BOOLEAN) : null,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );

        return $service->list($actor, $filters, $this->perPage);
    }

    public function render(): View
    {
        return view('livewire.pipelines.pipeline-list');
    }
}
