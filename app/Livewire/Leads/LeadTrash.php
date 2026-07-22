<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Exceptions\LeadWorkflowException;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Services\LeadLifecycleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class LeadTrash extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $pendingRestoreId = null;

    public string $pendingRestoreName = '';

    public string $restoreReason = '';

    public ?string $notice = null;

    public function mount(): void
    {
        Gate::authorize('viewTrash', Lead::class);
    }

    /** @return LengthAwarePaginator<int, Lead> */
    #[Computed]
    public function leads(): LengthAwarePaginator
    {
        Gate::authorize('viewTrash', Lead::class);

        return $this->repository()->paginateTrashedVisibleTo($this->currentUser(), $this->search);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openRestore(int $leadId): void
    {
        $lead = $this->repository()->findTrashedVisibleOrFail($this->currentUser(), $leadId);
        Gate::authorize('restore', $lead);
        $this->pendingRestoreId = $lead->getKey();
        $this->pendingRestoreName = $lead->full_name;
        $this->restoreReason = '';
        $this->resetValidation();
        $this->dispatch('modal-show', name: 'restore-lead');
    }

    public function confirmRestore(): void
    {
        if ($this->pendingRestoreId === null) {
            return;
        }

        $this->validate(['restoreReason' => ['nullable', 'string', 'max:500']]);

        try {
            $restored = $this->service()->restore(
                $this->currentUser(),
                $this->pendingRestoreId,
                $this->restoreReason,
            );
        } catch (LeadWorkflowException $exception) {
            $this->addError($exception->field, $exception->getMessage());

            return;
        }

        $this->notice = "Đã khôi phục Lead {$restored->full_name}.";
        $this->resetRestore();
        $this->dispatch('modal-close', name: 'restore-lead');
        unset($this->leads);
    }

    public function dismissRestore(): void
    {
        $this->resetRestore();
    }

    public function render(): View
    {
        return view('livewire.leads.lead-trash');
    }

    private function resetRestore(): void
    {
        $this->pendingRestoreId = null;
        $this->pendingRestoreName = '';
        $this->restoreReason = '';
        $this->resetValidation();
    }

    private function repository(): LeadRepository
    {
        return app(LeadRepository::class);
    }

    private function service(): LeadLifecycleService
    {
        return app(LeadLifecycleService::class);
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
