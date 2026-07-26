<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class NotificationCenter extends Component
{
    use WithPagination;

    #[Url(except: 'all')]
    public string $filter = 'all';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openNotification(string $notificationId): void
    {
        $user = $this->currentUser();
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if ($notification !== null) {
            if ($notification->read_at === null) {
                $notification->markAsRead();
            }

            $url = $notification->data['action_url'] ?? route('dashboard');
            $this->redirect($url, navigate: true);
        }
    }

    public function markAsRead(string $notificationId): void
    {
        $user = $this->currentUser();
        /** @var DatabaseNotification|null $notification */
        $notification = $user->unreadNotifications()->where('id', $notificationId)->first();

        if ($notification !== null) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        $user = $this->currentUser();
        $user->unreadNotifications->markAsRead();
    }

    public function deleteNotification(string $notificationId): void
    {
        $user = $this->currentUser();
        $user->notifications()->where('id', $notificationId)->delete();
    }

    public function deleteAllRead(): void
    {
        $user = $this->currentUser();
        $user->readNotifications()->delete();
    }

    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    #[Computed]
    public function notificationsList(): LengthAwarePaginator
    {
        $user = $this->currentUser();
        $query = $user->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where('data', 'like', "%{$search}%");
        }

        return $query->latest()->paginate(15);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return $this->currentUser()->unreadNotifications()->count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->currentUser()->notifications()->count();
    }

    private function currentUser(): User
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-center')
            ->layout('layouts.app', ['title' => 'Hộp thư thông báo']);
    }
}
