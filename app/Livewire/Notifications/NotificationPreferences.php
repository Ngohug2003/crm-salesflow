<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Models\User;
use App\Services\NotificationPreferenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class NotificationPreferences extends Component
{
    /** @var array<string, array{database: bool, email: bool, broadcast: bool}> */
    public array $preferences = [];

    public string $feedbackMessage = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationPreferenceService $service */
        $service = app(NotificationPreferenceService::class);
        $this->preferences = $service->getPreferencesForUser($user);
    }

    public function savePreferences(): void
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationPreferenceService $service */
        $service = app(NotificationPreferenceService::class);
        $service->updatePreferencesForUser($user, $this->preferences);

        $this->feedbackMessage = 'Đã cập nhật cài đặt nhận thông báo thành công!';
    }

    public function resetDefaults(): void
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationPreferenceService $service */
        $service = app(NotificationPreferenceService::class);
        $service->resetToDefault($user);
        $this->preferences = $service->getPreferencesForUser($user);

        $this->feedbackMessage = 'Đã khôi phục toàn bộ cài đặt nhận thông báo về mặc định!';
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-preferences');
    }
}
