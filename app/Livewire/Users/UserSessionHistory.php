<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\Auth\UserSessionHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class UserSessionHistory extends Component
{
    public ?int $userId = null;

    public ?string $feedbackMessage = null;

    public function mount(?int $userId = null): void
    {
        $this->userId = $userId ?? Auth::id();
    }

    public function revokeSession(string $sessionId): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var User $targetUser */
        $targetUser = User::findOrFail($this->userId ?? Auth::id());

        $service = app(UserSessionHistoryService::class);
        $success = $service->revokeSession($actor, $targetUser, $sessionId);

        if ($success) {
            $this->feedbackMessage = 'Đã thu hồi phiên đăng nhập thành công.';
        } else {
            $this->feedbackMessage = 'Không tìm thấy phiên đăng nhập hoặc phiên đã hết hạn.';
        }
    }

    public function revokeOtherSessions(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        $currentSessionId = Session::getId();

        $service = app(UserSessionHistoryService::class);
        $deletedCount = $service->revokeOtherSessions($actor, $currentSessionId);

        $this->feedbackMessage = "Đã đăng xuất thành công {$deletedCount} phiên làm việc khác.";
    }

    public function render(UserSessionHistoryService $service): View
    {
        /** @var User $actor */
        $actor = Auth::user();
        /** @var User $targetUser */
        $targetUser = User::findOrFail($this->userId ?? Auth::id());

        $currentSessionId = Session::getId();
        $sessions = $service->getSessionsForUser($targetUser, $currentSessionId);

        return view('livewire.users.user-session-history', [
            'targetUser' => $targetUser,
            'isSelf' => $actor->getKey() === $targetUser->getKey(),
            'sessions' => $sessions,
        ]);
    }
}
