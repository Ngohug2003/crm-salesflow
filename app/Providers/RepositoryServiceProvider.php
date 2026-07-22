<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AuditLogRepository;
use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\EloquentAuditLogRepository;
use App\Repositories\EloquentDepartmentRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        AuditLogRepository::class => EloquentAuditLogRepository::class,
        DepartmentRepository::class => EloquentDepartmentRepository::class,
        UserRepository::class => EloquentUserRepository::class,
    ];
}
