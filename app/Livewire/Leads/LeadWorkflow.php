<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Data\LeadConversionData;
use App\Data\LeadTimelineEntry;
use App\Enums\LeadStatus;
use App\Exceptions\LeadWorkflowException;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Services\Lead\LeadTimelineService;
use App\Services\LeadAssignmentService;
use App\Services\LeadConversionService;
use App\Services\LeadDirectoryService;
use App\Services\LeadStatusTransitionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class LeadWorkflow extends Component
{
    public int $leadId;

    public string $ownerId = '';

    public string $assignmentReason = '';

    public string $targetStatus = '';

    public string $statusReason = '';

    public bool $showAssignModal = false;

    public bool $showStatusModal = false;

    public string $noteContent = '';

    public bool $noteIsPinned = false;

    public function addNote(): void
    {
        $validated = $this->validate([
            'noteContent' => ['required', 'string', 'max:2000'],
            'noteIsPinned' => ['boolean'],
        ]);

        /** @var LeadTimelineService $service */
        $service = app(LeadTimelineService::class);
        $service->createNote($this->currentUser(), $this->lead(), $validated['noteContent'], (bool) $validated['noteIsPinned']);

        $this->reset('noteContent', 'noteIsPinned');
        session()->flash('status', 'Đã lưu ghi chú mới vào dòng thời gian Lead.');
    }

    public function togglePinNote(int $noteId): void
    {
        /** @var LeadNote|null $note */
        $note = LeadNote::find($noteId);
        if ($note !== null) {
            /** @var LeadTimelineService $service */
            $service = app(LeadTimelineService::class);
            $service->togglePinNote($this->currentUser(), $note);
        }
    }

    public function deleteNote(int $noteId): void
    {
        /** @var LeadNote|null $note */
        $note = LeadNote::find($noteId);
        if ($note !== null) {
            /** @var LeadTimelineService $service */
            $service = app(LeadTimelineService::class);
            $service->deleteNote($this->currentUser(), $note);
            session()->flash('status', 'Đã xóa ghi chú khỏi dòng thời gian.');
        }
    }

    public function mount(int $leadId): void
    {
        $this->leadId = $leadId;
        $lead = $this->repository()->findVisibleOrFail($this->currentUser(), $leadId);
        Gate::authorize('view', $lead);
        $this->ownerId = $lead->owner_id === null ? '' : (string) $lead->owner_id;
    }

    #[Computed]
    public function lead(): Lead
    {
        return $this->repository()->findVisibleOrFail($this->currentUser(), $this->leadId);
    }

    /** @return EloquentCollection<int, User> */
    #[Computed]
    public function ownerOptions(): EloquentCollection
    {
        return $this->directory()->ownerOptions($this->currentUser());
    }

    /** @return array<string, string> */
    #[Computed]
    public function statusOptions(): array
    {
        $status = $this->lead()->getAttribute('status');
        $status = $status instanceof LeadStatus ? $status : LeadStatus::from((string) $status);

        return collect($this->statusService()->availableTransitions($status))
            ->mapWithKeys(static fn (LeadStatus $target): array => [$target->value => $target->label()])
            ->all();
    }

    /** @return Collection<int, LeadTimelineEntry> */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->workflowRepository()->timeline($this->lead());
    }

    #[Computed]
    public function canAssign(): bool
    {
        return Gate::forUser($this->currentUser())->allows('assign', $this->lead());
    }

    #[Computed]
    public function hasAssignmentChange(): bool
    {
        $currentOwnerId = $this->lead()->owner_id === null
            ? ''
            : (string) $this->lead()->owner_id;

        return $this->ownerId !== $currentOwnerId;
    }

    #[Computed]
    public function canChangeStatus(): bool
    {
        return Gate::forUser($this->currentUser())->allows('update', $this->lead())
            && $this->statusOptions() !== [];
    }

    public function openAssign(): void
    {
        $this->resetValidation();
        $lead = $this->lead();
        $this->ownerId = $lead->owner_id === null ? '' : (string) $lead->owner_id;
        $this->assignmentReason = '';
        $this->showAssignModal = true;
        $this->dispatch('modal-show', name: 'assign-owner-modal');
    }

    public function cancelAssign(): void
    {
        $this->resetValidation();
        $this->showAssignModal = false;
        $this->dispatch('modal-close', name: 'assign-owner-modal');
    }

    public function openChangeStatus(): void
    {
        $this->resetValidation();
        $this->targetStatus = '';
        $this->statusReason = '';
        $this->showStatusModal = true;
        $this->dispatch('modal-show', name: 'change-status-modal');
    }

    public function cancelChangeStatus(): void
    {
        $this->resetValidation();
        $this->showStatusModal = false;
        $this->dispatch('modal-close', name: 'change-status-modal');
    }

    public function assign(): mixed
    {
        if (! $this->hasAssignmentChange()) {
            $this->addError('ownerId', 'Hãy chọn người phụ trách khác trước khi lưu phân công.');

            return null;
        }

        $validated = $this->validate([
            'ownerId' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'assignmentReason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->assignmentService()->assign(
                $this->currentUser(),
                $this->leadId,
                $validated['ownerId'] === '' ? null : (int) $validated['ownerId'],
                $validated['assignmentReason'],
            );
        } catch (LeadWorkflowException $exception) {
            $this->addError($exception->field, $exception->getMessage());

            return null;
        }

        session()->flash('status', 'Đã cập nhật người phụ trách và lưu lịch sử phân công.');
        $this->dispatch('modal-close', name: 'assign-owner-modal');

        return $this->redirectRoute('leads.show', ['leadId' => $this->leadId], navigate: true);
    }

    public function changeStatus(): mixed
    {
        $validated = $this->validate([
            'targetStatus' => ['required', Rule::in(array_keys($this->statusOptions()))],
            'statusReason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->statusService()->transition(
                $this->currentUser(),
                $this->leadId,
                $validated['targetStatus'],
                $validated['statusReason'],
            );
        } catch (LeadWorkflowException $exception) {
            $this->addError($exception->field, $exception->getMessage());

            return null;
        }

        session()->flash('status', 'Đã chuyển trạng thái Lead và lưu lịch sử.');
        $this->dispatch('modal-close', name: 'change-status-modal');

        return $this->redirectRoute('leads.show', ['leadId' => $this->leadId], navigate: true);
    }

    public bool $showConvertModal = false;

    public bool $convertCreateCompany = true;

    public bool $convertCreateContact = true;

    public bool $convertCreateOpportunity = true;

    public string $convertOpportunityName = '';

    public ?float $convertEstimatedValue = null;

    #[Computed]
    public function canConvert(): bool
    {
        /** @var LeadConversionService $service */
        $service = app(LeadConversionService::class);
        $check = $service->checkEligibility($this->currentUser(), $this->lead());

        return $check['eligible'];
    }

    public function openConvert(): void
    {
        $this->resetValidation();
        $lead = $this->lead();
        $this->convertOpportunityName = "Cơ hội từ Lead {$lead->full_name}";
        $this->convertEstimatedValue = $lead->estimated_value !== null ? (float) $lead->estimated_value : null;
        $this->showConvertModal = true;
        $this->dispatch('modal-show', name: 'convert-lead-modal');
    }

    public function cancelConvert(): void
    {
        $this->resetValidation();
        $this->showConvertModal = false;
        $this->dispatch('modal-close', name: 'convert-lead-modal');
    }

    public function convertLead(): mixed
    {
        /** @var LeadConversionService $service */
        $service = app(LeadConversionService::class);

        try {
            $data = new LeadConversionData(
                createCompany: $this->convertCreateCompany,
                createContact: $this->convertCreateContact,
                createOpportunity: $this->convertCreateOpportunity,
                opportunityName: $this->convertOpportunityName,
                estimatedValue: $this->convertEstimatedValue,
            );

            $service->convert($this->currentUser(), $this->leadId, $data);
            session()->flash('status', 'Đã chuyển đổi Lead thành công!');
            $this->dispatch('modal-close', name: 'convert-lead-modal');

            return $this->redirectRoute('leads.show', ['leadId' => $this->leadId], navigate: true);
        } catch (\Throwable $e) {
            $this->addError('convert', $e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.leads.lead-workflow');
    }

    private function repository(): LeadRepository
    {
        return app(LeadRepository::class);
    }

    private function workflowRepository(): LeadWorkflowRepository
    {
        return app(LeadWorkflowRepository::class);
    }

    private function directory(): LeadDirectoryService
    {
        return app(LeadDirectoryService::class);
    }

    private function assignmentService(): LeadAssignmentService
    {
        return app(LeadAssignmentService::class);
    }

    private function statusService(): LeadStatusTransitionService
    {
        return app(LeadStatusTransitionService::class);
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
