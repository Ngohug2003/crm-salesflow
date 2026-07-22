<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Enums\DataScope;
use App\Models\User;
use LogicException;

final class DataScopeResolver
{
    public function resolve(User $user): DataScope
    {
        /** @var array<string, array{data_scope: string}> $roles */
        $roles = config('crm.rbac.roles', []);
        $resolvedScope = DataScope::ReadOnly;

        foreach ($user->getRoleNames() as $roleName) {
            $definition = $roles[(string) $roleName] ?? null;

            if ($definition === null) {
                continue;
            }

            $candidate = DataScope::tryFrom($definition['data_scope']);

            if ($candidate === null) {
                throw new LogicException("Role [{$roleName}] has an invalid CRM data scope.");
            }

            if ($candidate->priority() > $resolvedScope->priority()) {
                $resolvedScope = $candidate;
            }
        }

        return $resolvedScope;
    }
}
