<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ActivityRepository;
use App\Repositories\Contracts\AdministrativeUnitRepository;
use App\Repositories\Contracts\AuditLogRepository;
use App\Repositories\Contracts\CompanyRepository;
use App\Repositories\Contracts\ContactRepository;
use App\Repositories\Contracts\DepartmentRepository;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\LeadSourceRepository;
use App\Repositories\Contracts\LeadWorkflowRepository;
use App\Repositories\Contracts\MetricsRepository;
use App\Repositories\Contracts\OpportunityRepository;
use App\Repositories\Contracts\PipelineRepository;
use App\Repositories\Contracts\SessionRepository;
use App\Repositories\Contracts\TagRepository;
use App\Repositories\Contracts\TaskRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\EloquentActivityRepository;
use App\Repositories\EloquentAdministrativeUnitRepository;
use App\Repositories\EloquentAuditLogRepository;
use App\Repositories\EloquentCompanyRepository;
use App\Repositories\EloquentContactRepository;
use App\Repositories\EloquentDepartmentRepository;
use App\Repositories\EloquentLeadRepository;
use App\Repositories\EloquentLeadSourceRepository;
use App\Repositories\EloquentLeadWorkflowRepository;
use App\Repositories\EloquentMetricsRepository;
use App\Repositories\EloquentOpportunityRepository;
use App\Repositories\EloquentPipelineRepository;
use App\Repositories\EloquentSessionRepository;
use App\Repositories\EloquentTagRepository;
use App\Repositories\EloquentTaskRepository;
use App\Repositories\EloquentUserRepository;
use App\Services\Contracts\LeadConversionContract;
use App\Services\LeadConversionService;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ActivityRepository::class => EloquentActivityRepository::class,
        AdministrativeUnitRepository::class => EloquentAdministrativeUnitRepository::class,
        AuditLogRepository::class => EloquentAuditLogRepository::class,
        CompanyRepository::class => EloquentCompanyRepository::class,
        ContactRepository::class => EloquentContactRepository::class,
        DepartmentRepository::class => EloquentDepartmentRepository::class,
        LeadConversionContract::class => LeadConversionService::class,
        LeadRepository::class => EloquentLeadRepository::class,
        LeadSourceRepository::class => EloquentLeadSourceRepository::class,
        LeadWorkflowRepository::class => EloquentLeadWorkflowRepository::class,
        MetricsRepository::class => EloquentMetricsRepository::class,
        OpportunityRepository::class => EloquentOpportunityRepository::class,
        PipelineRepository::class => EloquentPipelineRepository::class,
        SessionRepository::class => EloquentSessionRepository::class,
        TagRepository::class => EloquentTagRepository::class,
        TaskRepository::class => EloquentTaskRepository::class,
        UserRepository::class => EloquentUserRepository::class,
    ];
}
