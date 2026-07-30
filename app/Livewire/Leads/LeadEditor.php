<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Enums\DataScope;
use App\Exceptions\DuplicateLeadException;
use App\Livewire\Forms\LeadForm;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Province;
use App\Models\Tag;
use App\Models\User;
use App\Models\Ward;
use App\Repositories\Contracts\LeadRepository;
use App\Services\AdministrativeUnitService;
use App\Services\Authorization\DataScopeService;
use App\Services\LeadDirectoryService;
use App\Services\LeadManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * LeadEditor Component.
 *
 * QUYẾT ĐỊNH THIẾT KẾ:
 * Lead create/edit tiếp tục duy trì dưới dạng full-page (trang đầy đủ) thay vì sử dụng modal
 * do form nhập liệu của Lead rất dài, bao gồm nhiều trường dữ liệu thuộc các nhóm thông tin khác nhau
 * (Thông tin liên hệ, Địa chỉ và nhu cầu, Người phụ trách, Tag,...) và cần có liên kết URL riêng biệt
 * để dễ dàng chia sẻ, lưu bookmark và tránh làm hỏng trải nghiệm người dùng trên thiết bị di động (mobile).
 */
final class LeadEditor extends Component
{
    public LeadForm $form;

    public ?int $leadId = null;

    /** @var list<array{id: int, full_name: string, email: ?string, phone: ?string, status: string, owner: string, department: string, trashed: bool, matched_fields: list<string>}> */
    public array $duplicateCandidates = [];

    public ?string $pendingDuplicateSignature = null;

    public ?string $confirmedDuplicateSignature = null;

    public bool $showDuplicateWarning = false;

    public string $duplicateOverrideReason = '';

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

    /** @return Collection<int, Province> */
    #[Computed]
    public function provinceOptions(): Collection
    {
        return $this->administrativeUnits()->provinceOptions();
    }

    /** @return Collection<int, Ward> */
    #[Computed]
    public function wardOptions(): Collection
    {
        $provinceId = filled($this->form->provinceId) ? (int) $this->form->provinceId : null;

        return $this->administrativeUnits()->wardOptions($provinceId);
    }

    public function updatedFormProvinceId(): void
    {
        $this->form->wardId = null;
        $this->form->administrativeUnitSelectionChanged = true;
        $this->form->resetValidation(['provinceId', 'wardId']);
    }

    public function updatedFormWardId(): void
    {
        $this->form->administrativeUnitSelectionChanged = true;
        $this->form->resetValidation('wardId');
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

        try {
            $savedLead = $this->management()->save(
                $actor,
                $lead,
                $payload,
                $this->confirmedDuplicateSignature,
                $this->duplicateOverrideReason
            );

            session()->flash(
                'status',
                $lead === null
                    ? "Đã tạo Lead {$savedLead->full_name}."
                    : "Đã cập nhật Lead {$savedLead->full_name}.",
            );

            return $this->redirectRoute('leads.show', ['leadId' => $savedLead->getKey()], navigate: true);
        } catch (DuplicateLeadException $e) {
            $this->duplicateCandidates = $e->candidates;
            $this->pendingDuplicateSignature = $e->signature;
            $this->showDuplicateWarning = true;

            if ($this->confirmedDuplicateSignature !== $e->signature) {
                $this->confirmedDuplicateSignature = null;
            }

            return null;
        }
    }

    public function confirmDuplicateSave(): mixed
    {
        $this->duplicateOverrideReason = trim($this->duplicateOverrideReason);
        if (empty($this->duplicateOverrideReason)) {
            $this->addError('duplicateOverrideReason', 'Vui lòng nhập lý do lưu trùng lặp.');

            return null;
        }

        if (mb_strlen($this->duplicateOverrideReason) < 10) {
            $this->addError('duplicateOverrideReason', 'Lý do phải có ít nhất 10 ký tự.');

            return null;
        }

        $this->confirmedDuplicateSignature = $this->pendingDuplicateSignature;
        $this->showDuplicateWarning = false;

        return $this->save();
    }

    public function dismissDuplicateWarning(): void
    {
        $this->duplicateCandidates = [];
        $this->pendingDuplicateSignature = null;
        $this->confirmedDuplicateSignature = null;
        $this->duplicateOverrideReason = '';
        $this->showDuplicateWarning = false;
        $this->resetErrorBag('duplicateOverrideReason');
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

    private function administrativeUnits(): AdministrativeUnitService
    {
        return app(AdministrativeUnitService::class);
    }

    private function management(): LeadManagementService
    {
        return app(LeadManagementService::class);
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
