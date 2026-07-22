<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class LeadPolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $actor): bool
    {
        return $actor->can('leads.view');
    }

    public function view(User $actor, Lead $lead): bool
    {
        return $actor->can('leads.view') && $this->withinScope($actor, $lead);
    }

    public function create(User $actor): bool
    {
        return $this->dataScope->canWrite($actor) && $actor->can('leads.create');
    }

    public function viewTrash(User $actor): bool
    {
        return $this->dataScope->canWrite($actor)
            && $actor->can('leads.view')
            && $actor->can('leads.delete');
    }

    public function update(User $actor, Lead $lead): bool
    {
        return $this->canMutate($actor, $lead, 'leads.update');
    }

    public function delete(User $actor, Lead $lead): bool
    {
        return $this->canMutate($actor, $lead, 'leads.delete');
    }

    public function restore(User $actor, Lead $lead): bool
    {
        return $this->canMutate($actor, $lead, 'leads.delete');
    }

    public function forceDelete(User $actor, Lead $lead): bool
    {
        return false;
    }

    public function assign(User $actor, Lead $lead): bool
    {
        return $this->canMutate($actor, $lead, 'leads.assign');
    }

    public function convert(User $actor, Lead $lead): bool
    {
        return $this->canMutate($actor, $lead, 'leads.convert');
    }

    private function canMutate(User $actor, Lead $lead, string $permission): bool
    {
        return $this->dataScope->canWrite($actor)
            && $actor->can($permission)
            && $this->withinScope($actor, $lead);
    }

    private function withinScope(User $actor, Lead $lead): bool
    {
        return $this->dataScope->allows(
            $actor,
            $lead->owner_id,
            $lead->department_id,
        );
    }
}
