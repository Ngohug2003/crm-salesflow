<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Models\User;
use App\Services\Customer360Service;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Customer360 extends Component
{
    public int $companyId;

    public string $activeTab = 'overview';

    public function mount(int $companyId): void
    {
        $this->companyId = $companyId;
        $this->loadData();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['overview', 'relationship_map', 'opportunities', 'contacts', 'tasks', 'timeline'], true)) {
            $this->activeTab = $tab;
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function data(): array
    {
        return $this->loadData();
    }

    public function render(): View
    {
        return view('livewire.companies.customer-360', [
            'data' => $this->loadData(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var Customer360Service $service */
        $service = app(Customer360Service::class);

        return $service->getCompany360Data($actor, $this->companyId);
    }
}
