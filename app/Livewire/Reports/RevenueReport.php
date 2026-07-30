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
final class RevenueReport extends Component
{
    use InteractsWithReportFilters;

    public function mount(): void
    {
        $this->authorizeReportAccess();
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

        return $service->getOpportunityMetrics($actor, $this->reportFilters());
    }

    public function render(): View
    {
        return view('livewire.reports.revenue-report');
    }
}
