<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PriceBook;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class PriceBookPolicy
{
    public function __construct(private DataScopeService $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('price-books.view');
    }

    public function view(User $user, PriceBook $priceBook): bool
    {
        return $user->can('price-books.view') && $this->scope->allows($user, $priceBook->owner_id, $priceBook->department_id);
    }

    public function create(User $user): bool
    {
        return $this->scope->canWrite($user) && $user->can('price-books.create');
    }

    public function update(User $user, PriceBook $priceBook): bool
    {
        return $this->scope->canWrite($user) && $user->can('price-books.update') && $this->scope->allows($user, $priceBook->owner_id, $priceBook->department_id);
    }

    public function delete(User $user, PriceBook $priceBook): bool
    {
        return $this->scope->canWrite($user) && $user->can('price-books.delete') && $this->scope->allows($user, $priceBook->owner_id, $priceBook->department_id);
    }
}
