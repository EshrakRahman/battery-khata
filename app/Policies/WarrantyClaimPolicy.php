<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WarrantyClaim;

class WarrantyClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WarrantyClaim $claim): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WarrantyClaim $claim): bool
    {
        return true;
    }

    public function delete(User $user, WarrantyClaim $claim): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
