<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

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
final class DashboardOverview extends Component
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

    /**
     * Tải danh sách phòng ban cho dropdown bộ lọc
     *
     * @return Collection<int, Department>
     */
    #[Computed]
    public function departments(): Collection
    {
        return Department::query()->orderBy('name')->get();
    }

    /**
     * Tải danh sách nhân viên cho dropdown bộ lọc
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        $query = User::query()->orderBy('name');

        if ($this->departmentId !== null) {
            $query->where('department_id', $this->departmentId);
        }

        return $query->get();
    }

    /**
     * Tải danh sách Quy trình bán hàng cho dropdown bộ lọc
     *
     * @return Collection<int, Pipeline>
     */
    #[Computed]
    public function pipelines(): Collection
    {
        return Pipeline::query()->orderBy('name')->get();
    }

    /**
     * Tải số liệu thống kê tổng quan KPI từ SalesMetricsQueryService
     *
     * @return array{
     *     lead_metrics: array<string, mixed>,
     *     opportunity_metrics: array<string, mixed>,
     *     activity_task_metrics: array<string, mixed>
     * }
     */
    #[Computed]
    public function metrics(): array
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

        return $service->getOverviewSummary($actor, $filters);
    }

    public function render(): View
    {
        return view('livewire.dashboard.dashboard-overview');
    }
}
