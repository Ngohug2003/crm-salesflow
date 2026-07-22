<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\SystemAuditService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

final readonly class AuditAuthenticationActivity
{
    public function __construct(private SystemAuditService $audit) {}

    public function handle(Login|Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $isLogin = $event instanceof Login;

        $this->audit->record(
            $event->user,
            $event->user,
            $isLogin ? 'logged-in' : 'logged-out',
            $isLogin ? 'Đăng nhập hệ thống thành công' : 'Đăng xuất khỏi hệ thống',
            null,
            null,
            [
                'ip_address' => $this->maskIp(request()->ip()),
                'user_agent' => str(request()->userAgent())->limit(500)->toString(),
                'remember' => $event instanceof Login ? $event->remember : null,
            ],
        );
    }

    private function maskIp(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);

            return implode(':', array_slice($parts, 0, 3)).':****';
        }

        $parts = explode('.', $ip);

        return count($parts) === 4
            ? "{$parts[0]}.{$parts[1]}.***.***"
            : '***';
    }
}
