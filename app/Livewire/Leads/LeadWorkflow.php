<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Data\LeadTimelineEntry;
use App\Enums\LeadStatus;
use App\Exceptions\LeadWorkflowException;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Services\LeadAssignmentService;
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

        return $this->redirectRoute('leads.show', ['leadId' => $this->leadId], navigate: true);
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
