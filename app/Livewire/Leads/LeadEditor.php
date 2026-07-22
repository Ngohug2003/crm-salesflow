<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Enums\DataScope;
use App\Livewire\Forms\LeadForm;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Tag;
use App\Models\User;
use App\Repositories\Contracts\LeadRepository;
use App\Services\Authorization\DataScopeService;
use App\Services\DuplicateLeadService;
use App\Services\LeadDirectoryService;
use App\Services\LeadManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class LeadEditor extends Component
{
    public LeadForm $form;

    public ?int $leadId = null;

    /** @var list<array{id: int, full_name: string, email: ?string, phone: ?string, status: string, owner: string, department: string, trashed: bool, matched_fields: list<string>}> */
    public array $duplicateCandidates = [];

    public ?string $pendingDuplicateSignature = null;

    public ?string $confirmedDuplicateSignature = null;

    public bool $showDuplicateWarning = false;

    public function mount(?int $leadId = null): void
    {
        $this->leadId = $leadId;

        if ($leadId === null) {
            Gate::authorize('create', Lead::class);

            if ($this->dataScope()->resolve($this->currentUser()) !== DataScope::All) {
                $this->form->ownerId = (string) $this->currentUser()->getKey();
            }

            return;
        }

        $lead = $this->repository()->findVisibleOrFail($this->currentUser(), $leadId);
        Gate::authorize('update', $lead);
        $this->form->fillFrom($lead);
    }

    /** @return Collection<int, LeadSource> */
    #[Computed]
    public function sourceOptions(): Collection
    {
        return $this->directory()->sourceOptions();
    }

    /** @return Collection<int, Tag> */
    #[Computed]
    public function tagOptions(): Collection
    {
        return $this->directory()->tagOptions();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function ownerOptions(): Collection
    {
        return $this->directory()->ownerOptions($this->currentUser());
    }

    /** @return array<string, string> */
    #[Computed]
    public function priorityOptions(): array
    {
        return $this->directory()->priorityOptions();
    }

    #[Computed]
    public function canAssign(): bool
    {
        return $this->leadId === null && $this->currentUser()->can('leads.assign');
    }

    public function save(): mixed
    {
        $actor = $this->currentUser();
        $lead = $this->leadId === null
            ? null
            : $this->repository()->findVisibleOrFail($actor, $this->leadId);
        $payload = $this->form->validatedPayload();
        $signature = $this->duplicates()->signature($payload);
        $candidates = $this->duplicates()->candidates($actor, $payload, $this->leadId);

        if ($candidates !== [] && $this->confirmedDuplicateSignature !== $signature) {
            $this->duplicateCandidates = $candidates;
            $this->pendingDuplicateSignature = $signature;
            $this->showDuplicateWarning = true;

            return null;
        }

        $savedLead = $this->management()->save($actor, $lead, $payload);

        session()->flash(
            'status',
            $lead === null
                ? "Đã tạo Lead {$savedLead->full_name}."
                : "Đã cập nhật Lead {$savedLead->full_name}.",
        );

        return $this->redirectRoute('leads.show', ['leadId' => $savedLead->getKey()], navigate: true);
    }

    public function confirmDuplicateSave(): mixed
    {
        $this->confirmedDuplicateSignature = $this->pendingDuplicateSignature;
        $this->showDuplicateWarning = false;

        return $this->save();
    }

    public function dismissDuplicateWarning(): void
    {
        $this->duplicateCandidates = [];
        $this->pendingDuplicateSignature = null;
        $this->confirmedDuplicateSignature = null;
        $this->showDuplicateWarning = false;
    }

    public function render(): View
    {
        return view('livewire.leads.lead-editor');
    }

    private function repository(): LeadRepository
    {
        return app(LeadRepository::class);
    }

    private function directory(): LeadDirectoryService
    {
        return app(LeadDirectoryService::class);
    }

    private function management(): LeadManagementService
    {
        return app(LeadManagementService::class);
    }

    private function duplicates(): DuplicateLeadService
    {
        return app(DuplicateLeadService::class);
    }

    private function dataScope(): DataScopeService
    {
        return app(DataScopeService::class);
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
