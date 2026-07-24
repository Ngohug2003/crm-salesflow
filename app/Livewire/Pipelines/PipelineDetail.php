<?php

declare(strict_types=1);

namespace App\Livewire\Pipelines;

use App\Models\Pipeline;
use App\Models\User;
use App\Services\PipelineManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class PipelineDetail extends Component
{
    public int $pipelineId;

    public Pipeline $pipeline;

    public function mount(int $pipelineId): void
    {
        $this->pipelineId = $pipelineId;

        /** @var User $actor */
        $actor = Auth::user();
        /** @var PipelineManagementService $service */
        $service = app(PipelineManagementService::class);

        $this->pipeline = $service->get($actor, $pipelineId);
        Gate::forUser($actor)->authorize('view', $this->pipeline);
    }

    public function render(): View
    {
        return view('livewire.pipelines.pipeline-detail');
    }
}
