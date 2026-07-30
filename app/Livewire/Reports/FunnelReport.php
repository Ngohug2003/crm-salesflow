<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Livewire\Concerns\InteractsWithReportFilters;
use App\Models\User;
use App\Services\Analytics\SalesMetricsQueryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class FunnelReport extends Component
{
    use InteractsWithReportFilters;

    public function mount(): void
    {
        $this->authorizeReportAccess();
        if ($this->pipelineId === null) {
            $this->pipelineId = $this->pipelines()->first()?->id;
        }
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

        return $service->getFunnelMetrics($actor, $this->reportFilters());
    }

    public function render(): View
    {
        return view('livewire.reports.funnel-report');
    }
}
