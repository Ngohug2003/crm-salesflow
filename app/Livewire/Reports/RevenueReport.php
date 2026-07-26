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
final class RevenueReport extends Component
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

        if ($this->pipelineId === null) {
            /** @var Pipeline|null $defaultPipe */
            $defaultPipe = Pipeline::query()->orderBy('name')->first();
            if ($defaultPipe !== null) {
                $this->pipelineId = $defaultPipe->id;
            }
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
     * @return array{
     *     total_opportunities: int,
     *     open_opportunities: int,
     *     won_opportunities: int,
     *     lost_opportunities: int,
     *     total_amount: float,
     *     open_amount: float,
     *     won_amount: float,
     *     weighted_forecast: float,
     *     win_rate: float,
     *     avg_sales_cycle_days: float,
     *     loss_reasons: array<string, int>
     * }
     */
    #[Computed]
    public function opportunityMetrics(): array
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

        return $service->getOpportunityMetrics($actor, $filters);
    }

    /**
     * @return array<int, array{
     *     stage_id: int,
     *     stage_name: string,
     *     position: int,
     *     color: string,
     *     probability: int,
     *     opportunity_count: int,
     *     total_amount: float,
     *     conversion_from_previous: float,
     *     conversion_from_top: float
     * }>
     */
    #[Computed]
    public function funnelData(): array
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

        return $service->getFunnelMetrics($actor, $filters);
    }

    public function render(): View
    {
        return view('livewire.reports.revenue-report');
    }
}
