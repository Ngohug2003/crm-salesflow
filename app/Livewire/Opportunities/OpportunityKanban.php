<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Data\OpportunityFilterData;
use App\Exceptions\StaleOpportunityException;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\OpportunityManagementService;
use App\Services\OpportunityStageTransitionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class OpportunityKanban extends Component
{
    #[Url(as: 'pipeline', history: true)]
    public string $pipelineId = '';

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'owner', history: true)]
    public string $ownerId = '';

    #[Url(as: 'status', history: true)]
    public string $status = ''; // default show all opportunities so won/lost cards remain visible

    public function mount(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('viewAny', Opportunity::class);

        if ($this->pipelineId === '') {
            $defaultPipeline = Pipeline::query()->where('is_default', true)->first()
                ?? Pipeline::query()->where('is_active', true)->first();

            if ($defaultPipeline !== null) {
                $this->pipelineId = (string) $defaultPipeline->id;
            }
        }
    }

    public function moveOpportunity(int $opportunityId, int $toStageId, ?int $fromStageId = null, ?string $notes = null): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityStageTransitionService $transitionService */
        $transitionService = app(OpportunityStageTransitionService::class);

        try {
            $updated = $transitionService->transitionStage(
                actor: $actor,
                opportunityId: $opportunityId,
                targetStageId: $toStageId,
                expectedCurrentStageId: $fromStageId,
                notes: $notes,
            );

            if ($updated->is_won || $updated->is_lost) {
                $this->status = '';
            }

            session()->flash('message', "Đã chuyển cơ hội '{$updated->title}' sang giai đoạn '{$updated->stage?->name}'.");
        } catch (StaleOpportunityException $e) {
            $this->addError('kanban_error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('kanban_error', $e->getMessage());
        }
    }

    /** @return Collection<int, Pipeline> */
    #[Computed]
    public function pipelines(): Collection
    {
        return Pipeline::query()->where('is_active', true)->with('stages')->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name', 'asc')->get();
    }

    public function getActivePipelineProperty(): ?Pipeline
    {
        $id = $this->pipelineId !== '' ? (int) $this->pipelineId : null;
        if ($id === null) {
            return null;
        }

        return Pipeline::query()->with('stages')->find($id);
    }

    /** @return Collection<int, mixed> */
    #[Computed]
    public function stageColumns(): Collection
    {
        $pipeline = $this->getActivePipelineProperty();
        if ($pipeline === null) {
            return collect();
        }

        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityManagementService $service */
        $service = app(OpportunityManagementService::class);

        $filterData = new OpportunityFilterData(
            search: $this->search !== '' ? $this->search : null,
            pipelineId: $pipeline->id,
            ownerId: $this->ownerId !== '' ? (int) $this->ownerId : null,
            status: $this->status !== '' ? $this->status : null,
            sortBy: 'created_at',
            sortDirection: 'desc',
        );

        $opportunities = collect($service->list($actor, $filterData, perPage: 500)->items());

        $stages = $pipeline->stages()->orderBy('position', 'asc')->get();
        $grouped = $opportunities->groupBy('stage_id');

        return $stages->map(static function (PipelineStage $stage) use ($grouped) {
            /** @var Collection<int, Opportunity> $opps */
            $opps = $grouped->get($stage->id, collect());
            $totalCount = $opps->count();
            $totalAmount = 0.0;
            $totalWeightedValue = 0.0;

            foreach ($opps as $opp) {
                $totalAmount += (float) $opp->amount;
                $totalWeightedValue += $opp->weighted_value;
            }

            return [
                'stage' => $stage,
                'opportunities' => $opps,
                'total_count' => $totalCount,
                'total_amount' => round($totalAmount, 2),
                'total_weighted_value' => round($totalWeightedValue, 2),
            ];
        });
    }

    public function render(): View
    {
        return view('livewire.opportunities.opportunity-kanban');
    }
}
