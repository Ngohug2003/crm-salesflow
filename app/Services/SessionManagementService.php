<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\SessionInfo;
use App\Models\User;
use App\Repositories\Contracts\SessionRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class SessionManagementService
{
    public function __construct(
        private SessionRepository $sessions,
        private SystemAuditService $audit,
    ) {}

    /** @return list<SessionInfo> */
    public function listFor(User $user, string $currentSessionId): array
    {
        return array_map(
            fn (object $session): SessionInfo => $this->toInfo($session, $currentSessionId),
            $this->sessions->forUser($user),
        );
    }

    public function revoke(User $actor, string $encryptedSessionId, string $currentSessionId): bool
    {
        $sessionId = $this->decryptSessionId($encryptedSessionId);
        $session = $this->sessions->findForUser($actor, $sessionId);

        if ($session === null) {
            throw new InvalidArgumentException('Phiên đăng nhập không tồn tại hoặc không thuộc tài khoản hiện tại.');
        }

        $deleted = $this->sessions->deleteForUser($actor, $sessionId);

        if ($deleted) {
            $this->audit->record(
                $actor,
                $actor,
                'session_revoked',
                $sessionId === $currentSessionId ? 'Thu hồi phiên đăng nhập hiện tại' : 'Thu hồi một phiên đăng nhập',
                null,
                [
                    'session' => $this->safeAuditPayload($session, $sessionId === $currentSessionId),
                ],
            );
        }

        return $sessionId === $currentSessionId;
    }

    public function revokeOthers(User $actor, string $currentSessionId): int
    {
        $count = $this->sessions->deleteOtherSessions($actor, $currentSessionId);

        if ($count > 0) {
            $this->audit->record(
                $actor,
                $actor,
                'sessions_revoked',
                'Thu hồi các phiên đăng nhập khác',
                null,
                ['revoked_sessions_count' => $count],
            );
        }

        return $count;
    }

    private function decryptSessionId(string $encryptedSessionId): string
    {
        try {
            $sessionId = Crypt::decryptString($encryptedSessionId);
        } catch (DecryptException) {
            throw new InvalidArgumentException('Mã phiên đăng nhập không hợp lệ.');
        }

        if ($sessionId === '') {
            throw new InvalidArgumentException('Mã phiên đăng nhập không hợp lệ.');
        }

        return $sessionId;
    }

    private function toInfo(object $session, string $currentSessionId): SessionInfo
    {
        [$browser, $platform, $device] = $this->parseUserAgent((string) ($session->user_agent ?? ''));

        return new SessionInfo(
            token: Crypt::encryptString((string) $session->id),
            fingerprint: $this->fingerprint((string) $session->id),
            ipAddress: $this->maskIp($session->ip_address),
            device: $device,
            browser: $browser,
            platform: $platform,
            lastActiveAt: Carbon::createFromTimestamp((int) $session->last_activity)
                ->timezone((string) config('crm.display_timezone'))
                ->format('d/m/Y H:i:s'),
            isCurrent: hash_equals((string) $session->id, $currentSessionId),
        );
    }

    /** @return array{fingerprint: string, ip_address: string, browser: string, platform: string, device: string, is_current: bool} */
    private function safeAuditPayload(object $session, bool $isCurrent): array
    {
        [$browser, $platform, $device] = $this->parseUserAgent((string) ($session->user_agent ?? ''));

        return [
            'fingerprint' => $this->fingerprint((string) $session->id),
            'ip_address' => $this->maskIp($session->ip_address),
            'browser' => $browser,
            'platform' => $platform,
            'device' => $device,
            'is_current' => $isCurrent,
        ];
    }

    private function fingerprint(string $sessionId): string
    {
        return Str::of(hash('sha256', $sessionId))->substr(0, 12)->upper()->toString();
    }

    private function maskIp(?string $ipAddress): string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return 'Không rõ';
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);
            $parts[3] = '0';

            return implode('.', $parts).'/24';
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return Str::of($ipAddress)->beforeLast(':')->append(':****')->toString();
        }

        return 'Đã che';
    }

    /** @return array{string, string, string} */
    private function parseUserAgent(string $userAgent): array
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Trình duyệt không rõ',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            default => 'Nền tảng không rõ',
        };

        $device = str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') || str_contains($userAgent, 'iPhone')
            ? 'Thiết bị di động'
            : 'Máy tính';

        return [$browser, $platform, $device];
    }
}
