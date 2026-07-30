<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

final readonly class QuotePolicy
{
    public function __construct(private DataScopeService $dataScope) {}

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'quotes.view');
    }

    public function view(User $user, Quote $quote): bool
    {
        if (! $this->hasPermission($user, 'quotes.view')) {
            return false;
        }

        $opportunity = $quote->opportunity;

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function create(User $user): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $this->hasPermission($user, 'quotes.create');
    }

    public function update(User $user, Quote $quote): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $this->hasPermission($user, 'quotes.update') || ! $quote->status->isEditable()) {
            return false;
        }

        $opportunity = $quote->opportunity;

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function delete(User $user, Quote $quote): bool
    {
        if (! $this->dataScope->canWrite($user)) {
            return false;
        }

        if (! $this->hasPermission($user, 'quotes.void') || ! $quote->status->isEditable()) {
            return false;
        }

        $opportunity = $quote->opportunity;

        return $this->dataScope->allows($user, $opportunity->owner_id, $opportunity->department_id);
    }

    public function submit(User $user, Quote $quote): bool
    {
        return $quote->status->isEditable()
            && $this->hasPermission($user, 'quotes.submit')
            && $this->dataScope->canWrite($user)
            && $this->dataScope->allows($user, $quote->opportunity->owner_id, $quote->opportunity->department_id);
    }

    public function approve(User $user, Quote $quote): bool
    {
        if (! $this->hasPermission($user, 'quotes.approve') || ! $this->dataScope->canWrite($user)) {
            return false;
        }

        return $this->dataScope->allows($user, $quote->opportunity->owner_id, $quote->opportunity->department_id);
    }

    public function issue(User $user, Quote $quote): bool
    {
        return in_array($quote->status, [QuoteStatus::Approved, QuoteStatus::Issued, QuoteStatus::Sent, QuoteStatus::Accepted], true)
            && $this->hasPermission($user, 'quotes.issue')
            && $this->dataScope->allows($user, $quote->opportunity->owner_id, $quote->opportunity->department_id);
    }

    public function download(User $user, Quote $quote): bool
    {
        return $quote->status->canDownloadDocument()
            && $this->hasPermission($user, 'quotes.download')
            && $this->dataScope->allows($user, $quote->opportunity->owner_id, $quote->opportunity->department_id);
    }

    public function send(User $user, Quote $quote): bool
    {
        return $quote->status === QuoteStatus::Issued
            && $this->hasPermission($user, 'quotes.issue')
            && $this->dataScope->allows($user, $quote->opportunity->owner_id, $quote->opportunity->department_id);
    }

    public function manageSettings(User $user): bool
    {
        return $this->hasPermission($user, 'quotes.manage-settings');
    }

    private function hasPermission(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
