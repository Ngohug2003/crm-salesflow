<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\ContactFilterData;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ContactRepository
{
    /**
     * @return LengthAwarePaginator<int, Contact>
     */
    public function paginateVisible(User $actor, ContactFilterData $filters, int $perPage = 15): LengthAwarePaginator;

    public function findVisibleOrFail(User $actor, int $id): Contact;

    public function findVisibleForUpdateOrFail(User $actor, int $id): Contact;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Contact;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Contact $contact, array $data): Contact;

    public function softDelete(Contact $contact): Contact;

    public function resetPrimaryContactsExcept(int $companyId, ?int $exceptContactId = null): void;
}
