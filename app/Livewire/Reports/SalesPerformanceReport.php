<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Data\ReportFilterData;
use App\Models\Department;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\Analytics\SalesMetricsQueryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class SalesPerformanceReport extends Component
{
    #[Url(as: 'preset')]
    public string $datePreset = 'this_month';

    #[Url(as: 'start')]
    public ?string $startDate = null;

    #[Url(as: 'end')]
    public ?string $endDate = null;

    #[Url(as: 'dept')]
    public ?int $departmentId = null;

    #[Url(as: 'user')]
    public ?int $userId = null;

    #[Url(as: 'pipeline')]
    public ?int $pipelineId = null;

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        if (! $actor->can('reports.view')) {
            abort(403, 'Bạn không có quyền xem báo cáo.');
        }
    }

    public function updatedDatePreset(string $value): void
    {
        if ($value !== 'custom') {
            $this->startDate = null;
            $this->endDate = null;
        }
    }

    public function resetFilters(): void
    {
        $this->datePreset = 'this_month';
        $this->startDate = null;
        $this->endDate = null;
        $this->departmentId = null;
        $this->userId = null;
        $this->pipelineId = null;
    }

    public function clearCacheAndReload(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var SalesMetricsQueryService $service */
        $service = app(SalesMetricsQueryService::class);
        $service->clearMetricsCache($actor);

        $this->resetFilters();
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function departments(): Collection
    {
        return Department::query()->orderBy('name')->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        $query = User::query()->orderBy('name');
        if ($this->departmentId !== null) {
            $query->where('department_id', $this->departmentId);
        }

        return $query->get();
    }

    /** @return Collection<int, Pipeline> */
    #[Computed]
    public function pipelines(): Collection
    {
        return Pipeline::query()->orderBy('name')->get();
    }

    /**
     * @return array<int, array{
     *     user_id: int,
     *     user_name: string,
     *     department_name: string,
     *     total_leads: int,
     *     total_opportunities: int,
     *     won_opportunities: int,
     *     won_amount: float,
     *     win_rate: float,
     *     activity_count: int,
     *     completed_tasks_count: int
     * }>
     */
    #[Computed]
    public function performanceData(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var SalesMetricsQueryService $service */
        $service = app(SalesMetricsQueryService::class);

        $filters = new ReportFilterData(
            datePreset: $this->datePreset,
            startDate: $this->startDate,
            endDate: $this->endDate,
            departmentId: $this->departmentId,
            userId: $this->userId,
            pipelineId: $this->pipelineId,
        );

        return $service->getSalesPerformanceMetrics($actor, $filters);
    }

    public function render(): View
    {
        return view('livewire.reports.sales-performance-report');
    }
}
