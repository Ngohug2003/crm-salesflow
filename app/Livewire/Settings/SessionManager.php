<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\SessionManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class SessionManager extends Component
{
    /** @var list<array{token: string, fingerprint: string, ipAddress: string, device: string, browser: string, platform: string, lastActiveAt: string, isCurrent: bool}> */
    public array $sessions = [];

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public ?string $confirmingRevokeToken = null;

    public bool $confirmingRevokeOthers = false;

    public function mount(): void
    {
        $this->refreshSessions();
    }

    public function refreshSessions(): void
    {
        if (! request()->hasSession()) {
            $this->sessions = [];

            return;
        }

        $this->sessions = array_map(
            static fn ($session): array => [
                'token' => $session->token,
                'fingerprint' => $session->fingerprint,
                'ipAddress' => $session->ipAddress,
                'device' => $session->device,
                'browser' => $session->browser,
                'platform' => $session->platform,
                'lastActiveAt' => $session->lastActiveAt,
                'isCurrent' => $session->isCurrent,
            ],
            $this->service()->listFor($this->user(), request()->session()->getId()),
        );
    }

    public function confirmRevoke(string $token): void
    {
        $this->confirmingRevokeToken = $token;
    }

    public function revokeConfirmed(): mixed
    {
        if ($this->confirmingRevokeToken !== null) {
            $token = $this->confirmingRevokeToken;
            $this->confirmingRevokeToken = null;

            return $this->revoke($token);
        }

        return null;
    }

    public function confirmRevokeOthers(): void
    {
        $this->confirmingRevokeOthers = true;
    }

    public function revokeOthersConfirmed(): mixed
    {
        $this->confirmingRevokeOthers = false;

        return $this->revokeOthers();
    }

    public function revoke(string $token): mixed
    {
        $this->reset('statusMessage', 'errorMessage');

        if (! $this->passwordRecentlyConfirmed()) {
            return $this->redirectRoute('password.confirm', navigate: true);
        }

        try {
            $revokedCurrent = $this->service()->revoke($this->user(), $token, request()->session()->getId());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['session' => $exception->getMessage()]);
        }

        if ($revokedCurrent) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return $this->redirectRoute('login', navigate: true);
        }

        $this->statusMessage = 'Đã thu hồi phiên đăng nhập đã chọn.';
        $this->refreshSessions();

        return null;
    }

    public function revokeOthers(): mixed
    {
        if (! $this->passwordRecentlyConfirmed()) {
            return $this->redirectRoute('password.confirm', navigate: true);
        }

        $count = $this->service()->revokeOthers($this->user(), request()->session()->getId());
        $this->statusMessage = $count > 0
            ? "Đã thu hồi {$count} phiên đăng nhập khác."
            : 'Không có phiên đăng nhập khác để thu hồi.';
        $this->refreshSessions();

        return null;
    }

    public function render(): View
    {
        return view('livewire.settings.session-manager');
    }

    private function service(): SessionManagementService
    {
        return app(SessionManagementService::class);
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function passwordRecentlyConfirmed(): bool
    {
        if (! request()->hasSession()) {
            return false;
        }

        $confirmedAt = (int) request()->session()->get('auth.password_confirmed_at', 0);

        return $confirmedAt !== 0
            && (time() - $confirmedAt) < (int) config('auth.password_timeout', 10800);
    }
}
