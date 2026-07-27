<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Models\User;
use App\Services\CustomerRelationshipMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CustomerRelationshipMap extends Component
{
    public int $companyId;

    public function mount(int $companyId): void
    {
        $this->companyId = $companyId;
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapData(): array
    {
        /** @var User $actor */
        $actor = Auth::user();

        /** @var CustomerRelationshipMapService $service */
        $service = app(CustomerRelationshipMapService::class);

        return $service->buildCompanyMap($actor, $this->companyId);
    }

    public function render(): View
    {
        return view('livewire.companies.customer-relationship-map');
    }
}
