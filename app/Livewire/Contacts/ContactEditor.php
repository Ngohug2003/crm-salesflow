<?php

declare(strict_types=1);

namespace App\Livewire\Contacts;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

    public string $city = '';

    public string $province = '';

    public string $country = 'Việt Nam';

    public string $notes = '';

    public string $ownerId = '';

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
            $this->city = (string) $contact->city;
            $this->province = (string) $contact->province;
            $this->country = $contact->country ?: 'Việt Nam';
            $this->notes = (string) $contact->notes;
            $this->ownerId = $contact->owner_id ? (string) $contact->owner_id : '';
        }
    }

    /** @return array<string, array<int, string>> */
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
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'ownerId' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function save(): void
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

        if ($this->contactId === null) {
            $contact = $service->create($actor, $data);
            session()->flash('message', 'Tạo mới Người liên hệ thành công.');
            $this->redirect(route('contacts.show', $contact), navigate: true);
        } else {
            $contact = $service->update($actor, $this->contactId, $data);
            session()->flash('message', 'Cập nhật Người liên hệ thành công.');
            $this->redirect(route('contacts.show', $contact), navigate: true);
        }
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
}
