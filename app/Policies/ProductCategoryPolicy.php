<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProductCategory $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function update(User $user, ProductCategory $category): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, ProductCategory $category): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
