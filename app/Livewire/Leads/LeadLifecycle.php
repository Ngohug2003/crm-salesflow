<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Exceptions\LeadWorkflowException;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Services\LeadLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class LeadLifecycle extends Component
{
    public int $leadId;

    public string $leadName = '';

    public string $deleteReason = '';

    public function mount(int $leadId): void
    {
        $this->leadId = $leadId;
        $lead = $this->repository()->findVisibleOrFail($this->currentUser(), $leadId);
        Gate::authorize('view', $lead);
        $this->leadName = $lead->full_name;
    }

    public function openDelete(): void
    {
        $lead = $this->repository()->findVisibleOrFail($this->currentUser(), $this->leadId);
        Gate::authorize('delete', $lead);
        $this->resetValidation();
        $this->deleteReason = '';
        $this->dispatch('modal-show', name: 'delete-lead');
    }

    public function confirmDelete(): mixed
    {
        $this->validate(['deleteReason' => ['nullable', 'string', 'max:500']]);

        try {
            $this->service()->delete($this->currentUser(), $this->leadId, $this->deleteReason);
        } catch (LeadWorkflowException $exception) {
            $this->addError($exception->field, $exception->getMessage());

            return null;
        }

        session()->flash('status', "Đã đưa Lead {$this->leadName} vào thùng rác.");
        $this->dispatch('modal-close', name: 'delete-lead');

        return $this->redirectRoute('leads.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.leads.lead-lifecycle', [
            'canDelete' => Gate::forUser($this->currentUser())->allows(
                'delete',
                $this->repository()->findVisibleOrFail($this->currentUser(), $this->leadId),
            ),
        ]);
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
