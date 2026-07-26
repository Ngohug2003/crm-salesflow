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
final class SalesPerformanceReport extends Component
{
    use InteractsWithReportFilters;

    public function mount(): void
    {
        $this->authorizeReportAccess();
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

        return $service->getSalesPerformanceMetrics($actor, $this->reportFilters());
    }

    public function render(): View
    {
        return view('livewire.reports.sales-performance-report');
    }
}
