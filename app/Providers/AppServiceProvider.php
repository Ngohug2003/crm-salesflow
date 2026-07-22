<?php

namespace App\Providers;

use App\Models\User;
use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\EloquentDepartmentRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DepartmentRepository::class, EloquentDepartmentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(static function (User $user, string $ability): ?bool {
            return $user->hasRole((string) config('crm.rbac.super_admin_role')) ? true : null;
        });
    }
}
