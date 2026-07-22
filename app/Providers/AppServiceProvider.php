<?php

namespace App\Providers;

use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\EloquentDepartmentRepository;
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
        //
    }
}
