<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Exceptions\StaleOpportunityException;
use App\Models\Opportunity;
use App\Models\User;
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
            $this->reset(['transitionNotes', 'selectedTargetStageId']);
            $this->dispatch('attachment-updated');
            session()->flash('message', "Đã chuyển giai đoạn cơ hội sang '{$updated->stage?->name}'.");
        } catch (StaleOpportunityException $e) {
            $this->addError('stage_error', $e->getMessage());
            $this->reloadOpportunity();
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
        Gate::forUser($actor)->authorize('view', $this->opportunity);
    }

    public function render(): View
    {
        return view('livewire.opportunities.opportunity-detail');
    }
}
