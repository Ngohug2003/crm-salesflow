<?php

declare(strict_types=1);

namespace App\Livewire\Contacts;

use App\Exceptions\DuplicateContactException;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use App\Services\AdministrativeUnitService;
use App\Services\ContactManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class ContactEditor extends Component
{
    public ?int $contactId = null;

    public string $firstName = '';

    public string $lastName = '';

    public string $companyId = '';

    public string $email = '';

    public string $phone = '';

    public string $secondaryPhone = '';

    public string $jobTitle = '';

    public string $departmentName = '';

    public string $birthday = '';

    public bool $isPrimary = false;

    public string $address = '';

    public ?string $provinceId = null;

    public ?string $wardId = null;

    public string $city = '';

    public string $province = '';

    public string $country = 'Việt Nam';

    public string $notes = '';

    public string $ownerId = '';

    /** @var list<array{id: int, full_name: string, company: string, email: ?string, phone: ?string, owner: string, matched_fields: list<string>}> */
    public array $duplicateCandidates = [];

    public ?string $pendingDuplicateSignature = null;

    public ?string $confirmedDuplicateSignature = null;

    public bool $showDuplicateWarning = false;

    public string $duplicateOverrideReason = '';

    public bool $administrativeUnitSelectionChanged = false;

    public function mount(?int $contactId = null): void
    {
        $this->contactId = $contactId;

        /** @var User $actor */
        $actor = Auth::user();

        if ($contactId === null) {
            Gate::forUser($actor)->authorize('create', Contact::class);

            if (! $actor->hasAnyRole(['super-admin', 'admin'])) {
                $this->ownerId = (string) $actor->getKey();
            }
        } else {
            /** @var ContactManagementService $service */
            $service = app(ContactManagementService::class);
            $contact = $service->get($actor, $contactId);
            Gate::forUser($actor)->authorize('update', $contact);

            $this->firstName = $contact->first_name;
            $this->lastName = $contact->last_name;
            $this->companyId = $contact->company_id ? (string) $contact->company_id : '';
            $this->email = (string) $contact->email;
            $this->phone = (string) $contact->phone;
            $this->secondaryPhone = (string) $contact->secondary_phone;
            $this->jobTitle = (string) $contact->job_title;
            $this->departmentName = (string) $contact->department_name;
            $this->birthday = $contact->birthday !== null ? (string) $contact->birthday : '';
            $this->isPrimary = (bool) $contact->is_primary;
            $this->address = (string) $contact->address;
            $this->provinceId = $contact->province_id ? (string) $contact->province_id : null;
            $this->wardId = $contact->ward_id ? (string) $contact->ward_id : null;
            $this->city = (string) $contact->city;
            $this->province = (string) $contact->province;
            $this->country = $contact->country ?: 'Việt Nam';
            $this->notes = (string) $contact->notes;
            $this->ownerId = $contact->owner_id ? (string) $contact->owner_id : '';
        }
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'companyId' => ['nullable', 'integer', 'exists:companies,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondaryPhone' => ['nullable', 'string', 'max:50'],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'departmentName' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'isPrimary' => ['boolean'],
            'address' => ['nullable', 'string', 'max:255'],
            'provinceId' => [
                'nullable',
                'integer',
                Rule::exists('provinces', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true),
                ),
            ],
            'wardId' => [
                'nullable',
                'integer',
                Rule::exists('wards', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('is_active', true)
                        ->where('province_id', filled($this->provinceId) ? (int) $this->provinceId : 0),
                ),
            ],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'ownerId' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function save(): mixed
    {
        $this->validate();

        /** @var User $actor */
        $actor = Auth::user();

        /** @var ContactManagementService $service */
        $service = app(ContactManagementService::class);

        $data = [
            'first_name' => trim($this->firstName),
            'last_name' => trim($this->lastName),
            'company_id' => $this->companyId !== '' ? (int) $this->companyId : null,
            'email' => trim($this->email) !== '' ? trim($this->email) : null,
            'phone' => trim($this->phone) !== '' ? trim($this->phone) : null,
            'secondary_phone' => trim($this->secondaryPhone) !== '' ? trim($this->secondaryPhone) : null,
            'job_title' => trim($this->jobTitle) !== '' ? trim($this->jobTitle) : null,
            'department_name' => trim($this->departmentName) !== '' ? trim($this->departmentName) : null,
            'birthday' => trim($this->birthday) !== '' ? trim($this->birthday) : null,
            'is_primary' => $this->isPrimary,
            'address' => trim($this->address) !== '' ? trim($this->address) : null,
            'city' => trim($this->city) !== '' ? trim($this->city) : null,
            'province' => trim($this->province) !== '' ? trim($this->province) : null,
            'country' => trim($this->country) !== '' ? trim($this->country) : 'Việt Nam',
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
            'owner_id' => $this->ownerId !== '' ? (int) $this->ownerId : null,
        ];

        if ($this->contactId === null
            || $this->administrativeUnitSelectionChanged
            || filled($this->provinceId)
            || filled($this->wardId)) {
            $data['province_id'] = filled($this->provinceId) ? (int) $this->provinceId : null;
            $data['ward_id'] = filled($this->wardId) ? (int) $this->wardId : null;
        }

        try {
            if ($this->contactId === null) {
                $contact = $service->create(
                    $actor,
                    $data,
                    $this->confirmedDuplicateSignature,
                    $this->duplicateOverrideReason,
                );
                session()->flash('message', 'Tạo mới Người liên hệ thành công.');

                return $this->redirect(route('contacts.show', $contact), navigate: true);
            } else {
                $contact = $service->update(
                    $actor,
                    $this->contactId,
                    $data,
                    $this->confirmedDuplicateSignature,
                    $this->duplicateOverrideReason,
                );
                session()->flash('message', 'Cập nhật Người liên hệ thành công.');

                return $this->redirect(route('contacts.show', $contact), navigate: true);
            }
        } catch (DuplicateContactException $e) {
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

    public function updatedProvinceId(): void
    {
        $this->wardId = null;
        $this->administrativeUnitSelectionChanged = true;
        $this->resetValidation(['provinceId', 'wardId']);
    }

    public function updatedWardId(): void
    {
        $this->administrativeUnitSelectionChanged = true;
        $this->resetValidation('wardId');
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
        return $this->administrativeUnits()->wardOptions(
            filled($this->provinceId) ? (int) $this->provinceId : null,
        );
    }

    /** @return Collection<int, Company> */
    #[Computed]
    public function companies(): Collection
    {
        return Company::query()->orderBy('name')->get();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.contacts.contact-editor');
    }

    private function administrativeUnits(): AdministrativeUnitService
    {
        return app(AdministrativeUnitService::class);
    }
}
