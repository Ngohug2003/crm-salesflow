<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

interface SessionRepository
{
    /** @return list<object{id: string, user_id: int|null, ip_address: string|null, user_agent: string|null, last_activity: int}> */
    public function forUser(User $user): array;

    public function findForUser(User $user, string $sessionId): ?object;

    public function deleteForUser(User $user, string $sessionId): bool;

    public function deleteOtherSessions(User $user, string $currentSessionId): int;
}
