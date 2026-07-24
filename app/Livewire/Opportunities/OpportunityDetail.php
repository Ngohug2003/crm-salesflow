<?php

declare(strict_types=1);

namespace App\Livewire\Opportunities;

use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityManagementService;
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

    public function mount(int $opportunityId): void
    {
        $this->opportunityId = $opportunityId;

        /** @var User $actor */
        $actor = Auth::user();
        /** @var OpportunityManagementService $service */
        $service = app(OpportunityManagementService::class);

        $this->opportunity = $service->get($actor, $opportunityId);
        Gate::forUser($actor)->authorize('view', $this->opportunity);
    }

    public function render(): View
    {
        return view('livewire.opportunities.opportunity-detail');
    }
}
