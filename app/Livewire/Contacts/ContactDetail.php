<?php

declare(strict_types=1);

namespace App\Livewire\Contacts;

use App\Models\User;
use App\Services\ContactManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class ContactDetail extends Component
{
    public int $contactId;

    public bool $confirmingDeleteContact = false;

    public function mount(int $contactId): void
    {
        $this->contactId = $contactId;

        /** @var User $actor */
        $actor = Auth::user();
        /** @var ContactManagementService $service */
        $service = app(ContactManagementService::class);
        $contact = $service->get($actor, $contactId);

        Gate::forUser($actor)->authorize('view', $contact);
    }

    public function confirmDeleteContact(): void
    {
        $this->confirmingDeleteContact = true;
    }

    public function deleteContact(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var ContactManagementService $service */
        $service = app(ContactManagementService::class);

        $service->delete($actor, $this->contactId);

        session()->flash('message', 'Đã xóa Người liên hệ thành công.');
        $this->redirect(route('contacts.index'), navigate: true);
    }

    public function render(): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var ContactManagementService $service */
        $service = app(ContactManagementService::class);
        $contact = $service->get($actor, $this->contactId);

        return view('livewire.contacts.contact-detail', [
            'contact' => $contact,
        ]);
    }
}
