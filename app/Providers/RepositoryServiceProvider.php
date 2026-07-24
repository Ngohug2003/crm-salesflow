<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AuditLogRepository;
use App\Repositories\Contracts\CompanyRepository;
use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadSourceRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Repositories\Contracts\SessionRepository;
use App\Repositories\Contracts\TagRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\EloquentAuditLogRepository;
use App\Repositories\EloquentCompanyRepository;
use App\Repositories\EloquentDepartmentRepository;
use App\Repositories\EloquentLeadRepository;
use App\Repositories\EloquentLeadSourceRepository;
use App\Repositories\EloquentLeadWorkflowRepository;
use App\Repositories\EloquentSessionRepository;
use App\Repositories\EloquentTagRepository;
use App\Repositories\EloquentUserRepository;
use App\Services\Contracts\LeadConversionContract;
use App\Services\LeadConversionService;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        AuditLogRepository::class => EloquentAuditLogRepository::class,
        CompanyRepository::class => EloquentCompanyRepository::class,
        DepartmentRepository::class => EloquentDepartmentRepository::class,
        LeadConversionContract::class => LeadConversionService::class,
        LeadRepository::class => EloquentLeadRepository::class,
        LeadSourceRepository::class => EloquentLeadSourceRepository::class,
        LeadWorkflowRepository::class => EloquentLeadWorkflowRepository::class,
        SessionRepository::class => EloquentSessionRepository::class,
        TagRepository::class => EloquentTagRepository::class,
        UserRepository::class => EloquentUserRepository::class,
    ];
}
