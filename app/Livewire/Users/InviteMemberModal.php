<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Exceptions\UserOperationException;
use App\Models\Department;
use App\Models\User;
use App\Services\Auth\UserInvitationService;
use App\Services\UserDirectoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class InviteMemberModal extends Component
{
    public bool $showModal = false;

    public string $name = '';

    public string $email = '';

    public ?int $departmentId = null;

    public string $role = 'sales';

    public ?string $generatedLink = null;

    public ?string $successMessage = null;

    #[On('open-invite-modal')]
    public function openModal(): void
    {
        Gate::authorize('create', User::class);

        $this->reset(['name', 'email', 'departmentId', 'generatedLink', 'successMessage']);
        $this->role = 'sales';
        $this->resetValidation();
        $this->showModal = true;
        $this->dispatch('modal-show', name: 'invite-member-modal');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('modal-close', name: 'invite-member-modal');
    }

    public function resetForAnother(): void
    {
        $this->reset(['name', 'email', 'departmentId', 'generatedLink', 'successMessage']);
        $this->role = 'sales';
        $this->resetValidation();
    }

    /** @return Collection<int, Department> */
    #[Computed]
    public function departmentOptions(): Collection
    {
        return Department::query()->orderBy('name')->get();
    }

    /** @return array<string, string> */
    #[Computed]
    public function roleOptions(): array
    {
        return app(UserDirectoryService::class)->roleOptions();
    }

    public function sendInvitation(): void
    {
        Gate::authorize('create', User::class);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'departmentId' => ['nullable', 'integer', 'exists:departments,id'],
            'role' => ['required', 'string'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên người được mời.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'role.required' => 'Vui lòng chọn vai trò.',
        ]);

        /** @var User $actor */
        $actor = Auth::user();

        try {
            $invitation = app(UserInvitationService::class)->createInvitation($actor, [
                'name' => $this->name,
                'email' => $this->email,
                'department_id' => $this->departmentId,
                'role' => $this->role,
            ]);

            $this->generatedLink = route('invitations.accept', ['token' => $invitation->token]);
            $this->successMessage = "Đã tạo lời mời thành công cho {$this->email}!";

            $this->dispatch('invitation-created', message: "Đã tạo lời mời thành công cho {$this->email}!");
        } catch (UserOperationException $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.users.invite-member-modal');
    }
}
