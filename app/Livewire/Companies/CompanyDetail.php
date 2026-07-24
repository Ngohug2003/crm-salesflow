<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Models\User;
use App\Services\CompanyManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class CompanyDetail extends Component
{
    public int $companyId;

    public function mount(int $companyId): void
    {
        $this->companyId = $companyId;

        /** @var User $actor */
        $actor = Auth::user();
        /** @var CompanyManagementService $service */
        $service = app(CompanyManagementService::class);
        $company = $service->get($actor, $companyId);

        Gate::forUser($actor)->authorize('view', $company);
    }

    public function deleteCompany(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var CompanyManagementService $service */
        $service = app(CompanyManagementService::class);

        $service->delete($actor, $this->companyId);

        session()->flash('message', 'Đã xóa Doanh nghiệp thành công.');
        $this->redirect(route('companies.index'), navigate: true);
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var CompanyManagementService $service */
        $service = app(CompanyManagementService::class);
        $company = $service->get($actor, $this->companyId);

        return view('livewire.companies.company-detail', [
            'company' => $company,
        ]);
    }
}
