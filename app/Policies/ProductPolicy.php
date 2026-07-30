<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Services\Authorization\DataScopeService;

final readonly class ProductPolicy
{
    public function __construct(private DataScopeService $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('products.view') && $this->scope->allows($user, $product->owner_id, $product->department_id);
    }

    public function create(User $user): bool
    {
        return $this->scope->canWrite($user) && $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $this->scope->canWrite($user) && $user->can('products.update') && $this->scope->allows($user, $product->owner_id, $product->department_id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->scope->canWrite($user) && $user->can('products.delete') && $this->scope->allows($user, $product->owner_id, $product->department_id);
    }
}
