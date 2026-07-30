<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Models\Department;
use App\Models\Opportunity;
use App\Services\Authorization\DataScopeService;
use App\Services\OpportunityRiskService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

final class OpportunityRiskDashboard extends Component
{
    use WithPagination;

    public string $selectedLevel = 'all'; // all, critical, high, medium, low

    public ?int $selectedDepartmentId = null;

    public string $search = '';

    // Modal state for Acknowledge
    public bool $showAckModal = false;

    public ?int $targetOpportunityId = null;

    public string $ackStatus = 'acknowledged'; // acknowledged, in_progress, resolved

    public string $ackNotes = '';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user === null || ! $user->can('opportunities.view')) {
            abort(403, 'Bạn không có quyền truy cập Risk Dashboard.');
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->selectedDepartmentId = null;
        $this->selectedLevel = 'all';
        $this->resetPage();
    }

    public function openAcknowledgeModal(int $opportunityId): void
    {
        $this->targetOpportunityId = $opportunityId;
        $this->ackStatus = 'acknowledged';
        $this->ackNotes = '';
        $this->showAckModal = true;
    }

    public function saveAcknowledgement(OpportunityRiskService $riskService): void
    {
        $this->validate([
            'ackStatus' => ['required', 'string', 'in:acknowledged,in_progress,resolved'],
            'ackNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        if ($user === null || $this->targetOpportunityId === null) {
            return;
        }

        $opportunity = Opportunity::query()->findOrFail($this->targetOpportunityId);

        $riskService->acknowledgeRisk($user, $opportunity, $this->ackStatus, $this->ackNotes);

        $this->showAckModal = false;
        $this->targetOpportunityId = null;
        session()->flash('success', "Đã ghi nhận trạng thái xử lý cho cơ hội [{$opportunity->title}].");
    }

    public function recalculateAll(OpportunityRiskService $riskService, DataScopeService $dataScope): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $count = $riskService->evaluateForUser($user, $dataScope);
        session()->flash('success', "Đã đánh giá rủi ro lại cho {$count} Cơ hội bán hàng thuộc phạm vi của bạn.");
    }

    public function render(DataScopeService $dataScope): View
    {
        $user = Auth::user();

        /** @var Builder<Opportunity> $query */
        $query = Opportunity::query()
            ->where('is_won', false)
            ->where('is_lost', false)
            ->with([
                'currentRiskSnapshot.factors',
                'latestRiskAcknowledgement.user',
                'owner',
                'department',
                'stage',
                'pipeline',
                'company',
            ]);

        // Apply Data Scope
        if ($user !== null) {
            $dataScope->apply($query, $user);
        }

        if ($this->selectedDepartmentId !== null) {
            $query->where('department_id', $this->selectedDepartmentId);
        }

        if ($this->search !== '') {
            $query->where(function (Builder $q): void {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        // Apply level filter
        if ($this->selectedLevel !== 'all') {
            $query->whereHas('currentRiskSnapshot', function (Builder $q): void {
                $q->where('level', $this->selectedLevel);
            });
        }

        // Order by highest risk score first
        $query->leftJoin('opportunity_risk_snapshots as ors', function ($join): void {
            $join->on('opportunities.id', '=', 'ors.opportunity_id')
                ->where('ors.is_current', '=', true);
        })
            ->select('opportunities.*')
            ->orderByRaw('COALESCE(ors.score, 0) DESC');

        $opportunities = $query->paginate(12);

        // Compute metrics
        $metricsQuery = Opportunity::query()->where('is_won', false)->where('is_lost', false);
        if ($user !== null) {
            $dataScope->apply($metricsQuery, $user);
        }
        if ($this->selectedDepartmentId !== null) {
            $metricsQuery->where('department_id', $this->selectedDepartmentId);
        }

        $criticalCount = (clone $metricsQuery)->whereHas('currentRiskSnapshot', fn ($q) => $q->where('level', 'critical'))->count();
        $highCount = (clone $metricsQuery)->whereHas('currentRiskSnapshot', fn ($q) => $q->where('level', 'high'))->count();
        $mediumCount = (clone $metricsQuery)->whereHas('currentRiskSnapshot', fn ($q) => $q->where('level', 'medium'))->count();
        $lowCount = (clone $metricsQuery)->whereHas('currentRiskSnapshot', fn ($q) => $q->where('level', 'low'))->count();

        $departments = Department::query()->orderBy('name')->get();

        return view('livewire.opportunities.opportunity-risk-dashboard', [
            'opportunities' => $opportunities,
            'criticalCount' => $criticalCount,
            'highCount' => $highCount,
            'mediumCount' => $mediumCount,
            'lowCount' => $lowCount,
            'totalCount' => $criticalCount + $highCount + $mediumCount + $lowCount,
            'departments' => $departments,
        ])->layout('layouts.app', ['title' => 'Cảnh báo Deal Rủi ro (At-Risk Deals)']);
    }
}
