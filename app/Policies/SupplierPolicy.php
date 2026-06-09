<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
