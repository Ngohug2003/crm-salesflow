<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Department;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Activity::class, AuditLogPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(static function (User $user, string $ability): ?bool {
            return $user->hasRole((string) config('crm.rbac.super_admin_role')) ? true : null;
        });
    }
}
