<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Throwable;

final class UserSessionHistoryService
{
    /**
     * Get active sessions for a user.
     *
     * @return list<array{
     *     id: string,
     *     ip_address: string,
     *     user_agent: string,
     *     browser: string,
     *     platform: string,
     *     last_activity: int,
     *     last_activity_formatted: string,
     *     is_current: bool
     * }>
     */
    public function getSessionsForUser(User $targetUser, ?string $currentSessionId = null): array
    {
        try {
            /** @var list<object{id: string, ip_address: string|null, user_agent: string|null, last_activity: int}> $sessionRecords */
            $sessionRecords = DB::table('sessions')
                ->where('user_id', $targetUser->getKey())
                ->orderByDesc('last_activity')
                ->get()
                ->all();
        } catch (Throwable) {
            $sessionRecords = [];
        }

        $result = [];
        foreach ($sessionRecords as $record) {
            $userAgent = $record->user_agent ?? 'Unknown User Agent';
            $parsedAgent = $this->parseUserAgent($userAgent);

            $result[] = [
                'id' => $record->id,
                'ip_address' => $record->ip_address ?? '127.0.0.1',
                'user_agent' => $userAgent,
                'browser' => $parsedAgent['browser'],
                'platform' => $parsedAgent['platform'],
                'last_activity' => $record->last_activity,
                'last_activity_formatted' => now()->setTimestamp($record->last_activity)
                    ->timezone((string) config('crm.display_timezone', 'Asia/Ho_Chi_Minh'))
                    ->format('d/m/Y H:i:s'),
                'is_current' => $currentSessionId !== null && $record->id === $currentSessionId,
            ];
        }

        return $result;
    }

    /**
     * Revoke a specific session for a target user.
     */
    public function revokeSession(User $actor, User $targetUser, string $sessionId): bool
    {
        $this->authorizeRevoke($actor, $targetUser);

        $sessionRecord = DB::table('sessions')->where('id', $sessionId)->first();
        if ($sessionRecord === null) {
            return false;
        }

        DB::table('sessions')->where('id', $sessionId)->delete();

        activity()
            ->causedBy($actor)
            ->performedOn($targetUser)
            ->event('session.revoked')
            ->withProperties([
                'session_id' => $sessionId,
                'target_user_id' => $targetUser->getKey(),
                'target_user_email' => $targetUser->email,
                'ip_address' => $sessionRecord->ip_address ?? null,
                'user_agent' => $sessionRecord->user_agent ?? null,
            ])
            ->log("Thu hồi phiên đăng nhập {$sessionId} của người dùng {$targetUser->name}");

        return true;
    }

    /**
     * Revoke all other sessions for an actor except the current session.
     */
    public function revokeOtherSessions(User $actor, string $currentSessionId): int
    {
        $deletedCount = DB::table('sessions')
            ->where('user_id', $actor->getKey())
            ->where('id', '!=', $currentSessionId)
            ->delete();

        if ($deletedCount > 0) {
            activity()
                ->causedBy($actor)
                ->performedOn($actor)
                ->event('session.revoked_others')
                ->withProperties([
                    'current_session_id' => $currentSessionId,
                    'deleted_sessions_count' => $deletedCount,
                ])
                ->log("Thu hồi tất cả {$deletedCount} phiên đăng nhập khác của tài khoản {$actor->name}");
        }

        return $deletedCount;
    }

    /**
     * Check authorization before revoking session.
     */
    public function authorizeRevoke(User $actor, User $targetUser): void
    {
        // Users can always revoke their own sessions
        if ($actor->getKey() === $targetUser->getKey()) {
            return;
        }

        // Cannot revoke Super Admin sessions unless actor is Super Admin
        $superAdminRole = (string) config('crm.rbac.super_admin_role', 'super-admin');
        if ($targetUser->hasRole($superAdminRole) && ! $actor->hasRole($superAdminRole)) {
            abort(403, 'Bạn không thể thu hồi phiên đăng nhập của Quản trị viên tối cao.');
        }

        Gate::forUser($actor)->authorize('update', $targetUser);
    }

    /**
     * Parse User Agent string to extract Browser and Platform.
     *
     * @return array{browser: string, platform: string}
     */
    public function parseUserAgent(string $userAgent): array
    {
        $browser = 'Khôn xác định';
        $platform = 'Hệ điều hành khác';

        // Platform detection
        if (preg_match('/windows|win32/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        }

        // Browser detection
        if (preg_match('/edg\/([0-9.]+)/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome\/([0-9.]+)/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/firefox\/([0-9.]+)/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/safari\/([0-9.]+)/i', $userAgent) && ! preg_match('/chrome/i', $userAgent)) {
            $browser = 'Apple Safari';
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
        ];
    }
}
