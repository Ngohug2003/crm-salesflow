<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class ContactPolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $actor): bool
    {
        return $actor->can('contacts.view');
    }

    public function view(User $actor, Contact $contact): bool
    {
        return $actor->can('contacts.view') && $this->withinScope($actor, $contact);
    }

    public function create(User $actor): bool
    {
        return $this->dataScope->canWrite($actor) && $actor->can('contacts.create');
    }

    public function update(User $actor, Contact $contact): bool
    {
        return $this->canMutate($actor, $contact, 'contacts.update');
    }

    public function delete(User $actor, Contact $contact): bool
    {
        return $this->canMutate($actor, $contact, 'contacts.delete');
    }

    public function restore(User $actor, Contact $contact): bool
    {
        return $this->canMutate($actor, $contact, 'contacts.delete');
    }

    public function forceDelete(User $actor, Contact $contact): bool
    {
        return false;
    }

    private function canMutate(User $actor, Contact $contact, string $permission): bool
    {
        return $this->dataScope->canWrite($actor)
            && $actor->can($permission)
            && $this->withinScope($actor, $contact);
    }

    private function withinScope(User $actor, Contact $contact): bool
    {
        return $this->dataScope->allows(
            $actor,
            $contact->owner_id,
            $contact->department_id,
        );
    }
}
