<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class NotificationMenu extends Component
{
    public bool $open = false;

    public function toggleMenu(): void
    {
        $this->open = ! $this->open;
    }

    public function openNotification(string $notificationId): void
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if ($notification) {
            if ($notification->read_at === null) {
                $notification->markAsRead();
            }

            $url = $notification->data['action_url'] ?? route('tasks.index');
            $this->redirect($url, navigate: true);
        }
    }

    public function markAsRead(string $notificationId): void
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var DatabaseNotification|null $notification */
        $notification = $user->unreadNotifications()->where('id', $notificationId)->first();

        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();
    }

    /** @return Collection<int, DatabaseNotification> */
    #[Computed]
    public function notifications(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->notifications()->take(10)->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->unreadNotifications()->count();
    }

    public function render(): View
    {
        return view('livewire.notification-menu');
    }
}
