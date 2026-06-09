<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
