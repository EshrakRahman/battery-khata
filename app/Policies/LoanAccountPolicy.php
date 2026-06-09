<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LoanAccount;
use App\Models\User;

class LoanAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function view(User $user, LoanAccount $account): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function update(User $user, LoanAccount $account): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, LoanAccount $account): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
