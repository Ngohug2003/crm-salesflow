<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityPlaybookService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class OpportunityPlaybookProgress extends Component
{
    public int $opportunityId;

    /** @var array<int, string> */
    public array $responses = [];

    public bool $showRestartConfirmation = false;

    public function completeStep(int $stepRunId): void
    {
        $this->service()->completeStep($this->actor(), $stepRunId, $this->responses[$stepRunId] ?? null);
        unset($this->responses[$stepRunId]);
        session()->flash('playbook_success', 'Đã hoàn tất bước playbook.');
        $this->dispatch('playbook-progress-updated');
    }

    public function start(): void
    {
        $opportunity = Opportunity::query()->findOrFail($this->opportunityId);
        $entryId = (int) ($opportunity->stageHistories()
            ->where('to_stage_id', $opportunity->stage_id)
            ->latest('id')
            ->value('id') ?? 0);
        $this->service()->activateForStage(
            $this->actor(),
            $opportunity,
            $opportunity->stage_id,
            $entryId,
            true,
        );
        session()->flash('playbook_success', 'Đã khởi chạy playbook thủ công.');
    }

    public function restart(): void
    {
        $opportunity = Opportunity::query()->findOrFail($this->opportunityId);
        $this->service()->restart($this->actor(), $opportunity);
        $this->showRestartConfirmation = false;
        $this->responses = [];
        session()->flash('playbook_success', 'Đã tạo lượt thực hiện Playbook mới. Lịch sử lượt trước vẫn được giữ lại.');
        $this->dispatch('playbook-progress-updated');
    }

    public function render(): View
    {
        $opportunity = Opportunity::query()->findOrFail($this->opportunityId);
        $run = $this->service()->activeRun($this->actor(), $opportunity);
        $total = $run?->steps->count() ?? 0;
        $completed = $run?->steps->where('status', 'completed')->count() ?? 0;
        $progress = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        $nextStep = $run?->steps->firstWhere('status', 'pending');
        $canStart = $run === null && $this->service()->canStart($this->actor(), $opportunity);
        $awaitingAutomaticActivation = $run === null
            && $this->service()->awaitingAutomaticActivation($this->actor(), $opportunity);

        return view('livewire.opportunities.opportunity-playbook-progress', compact(
            'opportunity',
            'run',
            'total',
            'completed',
            'progress',
            'nextStep',
            'canStart',
            'awaitingAutomaticActivation',
        ));
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    private function service(): OpportunityPlaybookService
    {
        return app(OpportunityPlaybookService::class);
    }
}
