<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Data\ReportFilterData;
use App\Livewire\Dashboard\DashboardOverview;
use App\Livewire\Reports\FunnelReport;
use App\Livewire\Reports\RevenueReport;
use App\Livewire\Reports\SalesPerformanceReport;
use App\Models\Department;
use App\Models\Pipeline;
use App\Models\SavedReportFilter;
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

    public bool $showSavePresetModal = false;

    public string $presetName = '';

    public bool $presetIsDefault = false;

    public ?int $selectedPresetId = null;

    public function mountInteractsWithReportFilters(): void
    {
        if (! request()->has('preset') && ! request()->has('start') && ! request()->has('end') && ! request()->has('dept') && ! request()->has('user') && ! request()->has('pipeline')) {
            /** @var SavedReportFilter|null $defaultPreset */
            $defaultPreset = SavedReportFilter::query()
                ->where('user_id', $this->reportActor()->id)
                ->where('report_type', $this->currentReportType())
                ->where('is_default', true)
                ->first();

            if ($defaultPreset !== null) {
                $this->applyFilterPreset($defaultPreset->id);
            }
        }
    }

    protected function currentReportType(): string
    {
        if ($this instanceof DashboardOverview) {
            return 'overview';
        }

        if ($this instanceof RevenueReport) {
            return 'revenue';
        }

        if ($this instanceof FunnelReport) {
            return 'funnel';
        }

        if ($this instanceof SalesPerformanceReport) {
            return 'performance';
        }

        return 'all';
    }

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
        $this->selectedPresetId = null;

        if ($this instanceof FunnelReport) {
            $this->pipelineId = $this->pipelines()->first()?->id;
        } else {
            $this->pipelineId = null;
        }

        $this->refreshMetrics();
    }

    public function openSavePresetModal(): void
    {
        $this->presetName = '';
        $this->presetIsDefault = false;
        $this->showSavePresetModal = true;
    }

    public function saveFilterPreset(): void
    {
        if (trim($this->presetName) === '') {
            $this->addError('presetName', 'Vui lòng nhập tên bộ lọc đã lưu.');

            return;
        }

        $actor = $this->reportActor();

        if ($this->presetIsDefault) {
            SavedReportFilter::query()
                ->where('user_id', $actor->id)
                ->where('report_type', $this->currentReportType())
                ->update(['is_default' => false]);
        }

        /** @var SavedReportFilter $filter */
        $filter = SavedReportFilter::query()->create([
            'user_id' => $actor->id,
            'name' => trim($this->presetName),
            'report_type' => $this->currentReportType(),
            'filter_data' => [
                'datePreset' => $this->datePreset,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'departmentId' => $this->departmentId,
                'userId' => $this->userId,
                'pipelineId' => $this->pipelineId,
            ],
            'is_default' => $this->presetIsDefault,
        ]);

        $this->selectedPresetId = $filter->id;
        $this->showSavePresetModal = false;
        $this->presetName = '';
        $this->presetIsDefault = false;
    }

    public function applyFilterPreset(int $presetId): void
    {
        $actor = $this->reportActor();
        /** @var SavedReportFilter|null $preset */
        $preset = SavedReportFilter::query()
            ->where('user_id', $actor->id)
            ->find($presetId);

        if ($preset === null) {
            return;
        }

        $data = $preset->filter_data;

        $this->selectedPresetId = $preset->id;
        $this->datePreset = isset($data['datePreset']) && is_string($data['datePreset']) ? $data['datePreset'] : 'this_month';
        $this->startDate = isset($data['startDate']) && is_string($data['startDate']) ? $data['startDate'] : null;
        $this->endDate = isset($data['endDate']) && is_string($data['endDate']) ? $data['endDate'] : null;
        $this->departmentId = isset($data['departmentId']) && is_numeric($data['departmentId']) ? (int) $data['departmentId'] : null;
        $this->userId = isset($data['userId']) && is_numeric($data['userId']) ? (int) $data['userId'] : null;

        $presetPipelineId = isset($data['pipelineId']) && is_numeric($data['pipelineId']) ? (int) $data['pipelineId'] : null;
        if ($presetPipelineId !== null) {
            $this->pipelineId = $presetPipelineId;
        } elseif ($this instanceof FunnelReport) {
            if ($this->pipelineId === null) {
                $this->pipelineId = $this->pipelines()->first()?->id;
            }
        } else {
            $this->pipelineId = null;
        }

        $this->refreshMetrics();
    }

    public function deleteFilterPreset(int $presetId): void
    {
        $actor = $this->reportActor();
        SavedReportFilter::query()
            ->where('user_id', $actor->id)
            ->where('id', $presetId)
            ->delete();

        if ($this->selectedPresetId === $presetId) {
            $this->selectedPresetId = null;
        }
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

    /** @return Collection<int, SavedReportFilter> */
    #[Computed]
    public function savedPresets(): Collection
    {
        return SavedReportFilter::query()
            ->where('user_id', $this->reportActor()->id)
            ->whereIn('report_type', [$this->currentReportType(), 'all'])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
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
