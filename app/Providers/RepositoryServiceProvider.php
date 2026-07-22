<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\EloquentDepartmentRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        DepartmentRepository::class => EloquentDepartmentRepository::class,
        UserRepository::class => EloquentUserRepository::class,
    ];
}
