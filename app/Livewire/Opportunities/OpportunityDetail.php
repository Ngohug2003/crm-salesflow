<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Exceptions\StaleOpportunityException;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\OpportunityCloseWorkflowService;
use App\Services\OpportunityManagementService;
use App\Services\OpportunityStageTransitionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class OpportunityDetail extends Component
{
    public int $opportunityId;

    public Opportunity $opportunity;

    public string $transitionNotes = '';

    public string $lostReason = '';

    public bool $showLostModal = false;

    public bool $showWonModal = false;

    public bool $showReopenModal = false;

    public ?int $selectedTargetStageId = null;

    public function mount(int $opportunityId): void
    {
        $this->opportunityId = $opportunityId;
        $this->reloadOpportunity();
    }

    public function changeStage(int $targetStageId): void
    {
        /** @var User $actor */
        $actor = Auth::user();

        // If target stage is Lost, open lost reason modal instead of immediate move
        $targetStage = PipelineStage::query()->find($targetStageId);
        if ($targetStage !== null && $targetStage->is_lost) {
            $this->showLostModal = true;

            return;
        }

        /** @var OpportunityStageTransitionService $transitionService */
        $transitionService = app(OpportunityStageTransitionService::class);

        try {
            $updated = $transitionService->transitionStage(
                actor: $actor,
                opportunityId: $this->opportunityId,
                targetStageId: $targetStageId,
                expectedCurrentStageId: $this->opportunity->stage_id,
                notes: $this->transitionNotes !== '' ? $this->transitionNotes : null,
            );

            $this->reloadOpportunity();
            $this->transitionNotes = '';
            $this->dispatch('attachment-updated');
            session()->flash('message', "Đã chuyển cơ hội sang '{$updated->stage?->name}'.");
        } catch (StaleOpportunityException $e) {
            $this->reloadOpportunity();
            $this->addError('stage_error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('stage_error', $e->getMessage());
        }
    }

    public function confirmCloseWon(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityCloseWorkflowService $workflow */
        $workflow = app(OpportunityCloseWorkflowService::class);

        try {
            $updated = $workflow->closeWon($actor, $this->opportunityId);
            $this->reloadOpportunity();
            $this->showWonModal = false;
            $this->dispatch('attachment-updated');
            session()->flash('message', "Đã CHỐT THÀNH CÔNG Cơ hội bán hàng '{$updated->title}'.");
        } catch (\Throwable $e) {
            $this->addError('stage_error', $e->getMessage());
        }
    }

    public function confirmCloseLost(): void
    {
        $this->validate([
            'lostReason' => ['required', 'string', 'min:3'],
        ], [
            'lostReason.required' => 'Vui lòng nhập lý do thất bại.',
            'lostReason.min' => 'Lý do thất bại phải có ít nhất 3 ký tự.',
        ]);

        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityCloseWorkflowService $workflow */
        $workflow = app(OpportunityCloseWorkflowService::class);

        try {
            $updated = $workflow->closeLost($actor, $this->opportunityId, $this->lostReason);
            $this->reloadOpportunity();
            $this->reset(['lostReason', 'showLostModal']);
            $this->dispatch('attachment-updated');
            session()->flash('message', "Đã chuyển Cơ hội bán hàng '{$updated->title}' sang trạng thái Thất bại (Lost).");
        } catch (\Throwable $e) {
            $this->addError('stage_error', $e->getMessage());
        }
    }

    public function confirmReopen(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityCloseWorkflowService $workflow */
        $workflow = app(OpportunityCloseWorkflowService::class);

        try {
            $updated = $workflow->reopen($actor, $this->opportunityId);
            $this->reloadOpportunity();
            $this->showReopenModal = false;
            $this->dispatch('attachment-updated');
            session()->flash('message', "Đã mở lại Cơ hội bán hàng '{$updated->title}'.");
        } catch (\Throwable $e) {
            $this->addError('stage_error', $e->getMessage());
        }
    }

    private function reloadOpportunity(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityManagementService $service */
        $service = app(OpportunityManagementService::class);
        $this->opportunity = $service->get($actor, $this->opportunityId);
        $this->opportunity->load(['tasks.assignee', 'tasks.assignees', 'tasks.creator']);

        Gate::forUser($actor)->authorize('view', $this->opportunity);
    }

    public function render(): View
    {
        return view('livewire.opportunities.opportunity-detail');
    }
}
