<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ScrapDisposal;
use App\Models\User;

class ScrapDisposalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ScrapDisposal $disposal): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function update(User $user, ScrapDisposal $disposal): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, ScrapDisposal $disposal): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
