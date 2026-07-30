<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\InteractsWithReportFilters;
use App\Models\User;
use App\Services\Analytics\SalesMetricsQueryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class DashboardOverview extends Component
{
    use InteractsWithReportFilters;

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

        return $service->getOverviewSummary($actor, $this->reportFilters());
    }

    public function render(): View
    {
        return view('livewire.dashboard.dashboard-overview');
    }
}
