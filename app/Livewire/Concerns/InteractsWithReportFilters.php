<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Data\ReportFilterData;
use App\Models\Department;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\Analytics\ReportFilterOptionsService;
use App\Services\Analytics\SalesMetricsQueryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

trait InteractsWithReportFilters
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

    public function updatedDepartmentId(): void
    {
        $this->userId = null;
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

    public function refreshMetrics(): void
    {
        app(SalesMetricsQueryService::class)->clearMetricsCache($this->reportActor());
    }

    /** @deprecated Kept for compatibility with links/tests created before P7-FIX. */
    public function clearCacheAndReload(): void
    {
        $this->refreshMetrics();
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function departments(): Collection
    {
        return app(ReportFilterOptionsService::class)->departments($this->reportActor());
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return app(ReportFilterOptionsService::class)->users($this->reportActor(), $this->departmentId);
    }

    /** @return Collection<int, Pipeline> */
    #[Computed]
    public function pipelines(): Collection
    {
        return app(ReportFilterOptionsService::class)->pipelines($this->reportActor());
    }

    protected function authorizeReportAccess(): void
    {
        abort_unless($this->reportActor()->can('reports.view'), 403, 'Bạn không có quyền xem báo cáo.');
    }

    protected function reportFilters(): ReportFilterData
    {
        return new ReportFilterData(
            datePreset: $this->datePreset,
            startDate: $this->startDate,
            endDate: $this->endDate,
            departmentId: $this->departmentId,
            userId: $this->userId,
            pipelineId: $this->pipelineId,
        );
    }

    protected function reportActor(): User
    {
        /** @var User $actor */
        $actor = Auth::user();

        return $actor;
    }
}
