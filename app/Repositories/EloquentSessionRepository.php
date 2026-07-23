<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\SessionRepository;
use Illuminate\Support\Facades\DB;

final readonly class EloquentSessionRepository implements SessionRepository
{
    public function forUser(User $user): array
    {
        return DB::table((string) config('session.table', 'sessions'))
            ->select(['id', 'user_id', 'ip_address', 'user_agent', 'last_activity'])
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_activity')
            ->get()
            ->all();
    }

    public function findForUser(User $user, string $sessionId): ?object
    {
        return DB::table((string) config('session.table', 'sessions'))
            ->select(['id', 'user_id', 'ip_address', 'user_agent', 'last_activity'])
            ->where('user_id', $user->getKey())
            ->where('id', $sessionId)
            ->first();
    }

    public function deleteForUser(User $user, string $sessionId): bool
    {
        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->where('id', $sessionId)
            ->delete() === 1;
    }

    public function deleteOtherSessions(User $user, string $currentSessionId): int
    {
        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    public function deleteAllSessions(User $user): int
    {
        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
