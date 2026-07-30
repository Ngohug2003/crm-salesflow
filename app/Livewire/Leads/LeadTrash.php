<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Exceptions\DuplicateLeadException;
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

    /** @var list<array{id: int, full_name: string, email: ?string, phone: ?string, status: string, owner: string, department: string, trashed: bool, matched_fields: list<string>}> */
    public array $duplicateCandidates = [];

    public ?string $pendingDuplicateSignature = null;

    public ?string $confirmedDuplicateSignature = null;

    public string $duplicateOverrideReason = '';

    public bool $showDuplicateConflict = false;

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
                $this->confirmedDuplicateSignature,
                $this->duplicateOverrideReason,
            );
        } catch (LeadWorkflowException $exception) {
            $this->addError($exception->field, $exception->getMessage());

            return;
        } catch (DuplicateLeadException $e) {
            $this->duplicateCandidates = $e->candidates;
            $this->pendingDuplicateSignature = $e->signature;
            $this->showDuplicateConflict = true;

            if ($this->confirmedDuplicateSignature !== $e->signature) {
                $this->confirmedDuplicateSignature = null;
            }

            $this->dispatch('modal-close', name: 'restore-lead');
            $this->dispatch('modal-show', name: 'duplicate-conflict');

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

    public function confirmConflictRestore(): void
    {
        $this->duplicateOverrideReason = trim($this->duplicateOverrideReason);
        if (empty($this->duplicateOverrideReason)) {
            $this->addError('duplicateOverrideReason', 'Vui lòng nhập lý do khôi phục trùng lặp.');

            return;
        }

        if (mb_strlen($this->duplicateOverrideReason) < 10) {
            $this->addError('duplicateOverrideReason', 'Lý do phải có ít nhất 10 ký tự.');

            return;
        }

        $this->confirmedDuplicateSignature = $this->pendingDuplicateSignature;
        $this->showDuplicateConflict = false;
        $this->dispatch('modal-close', name: 'duplicate-conflict');

        $this->confirmRestore();
    }

    public function dismissConflict(): void
    {
        $this->duplicateCandidates = [];
        $this->pendingDuplicateSignature = null;
        $this->confirmedDuplicateSignature = null;
        $this->duplicateOverrideReason = '';
        $this->showDuplicateConflict = false;
        $this->resetErrorBag('duplicateOverrideReason');
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
