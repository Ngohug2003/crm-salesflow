<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Exceptions\UserOperationException;
use App\Models\UserInvitation;
use App\Services\Auth\UserInvitationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest', ['title' => 'Kích hoạt tài khoản — SalesFlow CRM'])]
final class AcceptInvitation extends Component
{
    public string $token = '';

    public ?UserInvitation $invitation = null;

    public string $password = '';

    public string $passwordConfirmation = '';

    public ?string $errorMessage = null;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->invitation = UserInvitation::query()->where('token', $token)->first();

        if ($this->invitation === null) {
            $this->errorMessage = 'Liên kết lời mời không tồn tại hoặc đã bị hủy.';
        } elseif ($this->invitation->isAccepted()) {
            $this->errorMessage = 'Lời mời này đã được kích hoạt thành công trước đó.';
        } elseif ($this->invitation->isExpired()) {
            $this->errorMessage = 'Liên kết lời mời đã hết hạn. Vui lòng liên hệ Admin để nhận lời mời mới.';
        }
    }

    public function accept(): void
    {
        $this->resetErrorBag();

        if ($this->errorMessage !== null) {
            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải chứa ít nhất 8 ký tự.',
            'password.same' => 'Xác nhận mật khẩu không trùng khớp.',
        ]);

        try {
            app(UserInvitationService::class)->acceptInvitation($this->token, $this->password);
            $this->redirectRoute('dashboard', navigate: true);
        } catch (UserOperationException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.auth.accept-invitation');
    }
}
