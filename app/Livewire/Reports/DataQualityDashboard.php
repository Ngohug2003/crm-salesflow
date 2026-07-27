<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Livewire\Concerns\InteractsWithReportFilters;
use App\Models\User;
use App\Services\Analytics\DataQualityAuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class DataQualityDashboard extends Component
{
    use InteractsWithReportFilters;

    public string $activeTab = 'incomplete';

    public function mount(): void
    {
        $this->authorizeReportAccess();
    }

    /**
     * @return array{
     *     health_score: int,
     *     total_records: int,
     *     incomplete_count: int,
     *     duplicate_count: int,
     *     stale_count: int,
     *     orphan_count: int
     * }
     */
    #[Computed]
    public function overviewMetrics(): array
    {
        /** @var User $actor */
        $actor = Auth::user();

        return app(DataQualityAuditService::class)->getOverviewMetrics($actor, $this->reportFilters());
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     type_label: string,
     *     owner_name: string,
     *     missing_field: string,
     *     url: string
     * }>
     */
    #[Computed]
    public function incompleteRecords(): array
    {
        /** @var User $actor */
        $actor = Auth::user();

        return app(DataQualityAuditService::class)->getIncompleteRecords($actor, $this->reportFilters());
    }

    /**
     * @return array<int, array{
     *     type_label: string,
     *     target_name: string,
     *     duplicate_name: string,
     *     matched_reason: string,
     *     url: string
     * }>
     */
    #[Computed]
    public function duplicateCandidates(): array
    {
        /** @var User $actor */
        $actor = Auth::user();

        return app(DataQualityAuditService::class)->getDuplicateCandidates($actor);
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     type_label: string,
     *     owner_name: string,
     *     stale_reason: string,
     *     url: string
     * }>
     */
    #[Computed]
    public function staleRecords(): array
    {
        /** @var User $actor */
        $actor = Auth::user();

        return app(DataQualityAuditService::class)->getStaleRecords($actor, $this->reportFilters());
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     type_label: string,
     *     owner_name: string,
     *     orphan_reason: string,
     *     url: string
     * }>
     */
    #[Computed]
    public function orphanRecords(): array
    {
        /** @var User $actor */
        $actor = Auth::user();

        return app(DataQualityAuditService::class)->getOrphanRecords($actor, $this->reportFilters());
    }

    public function render(): View
    {
        return view('livewire.reports.data-quality-dashboard');
    }
}
