<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PostDatedCheque;
use App\Models\User;

class PostDatedChequePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PostDatedCheque $cheque): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PostDatedCheque $cheque): bool
    {
        return true;
    }

    public function delete(User $user, PostDatedCheque $cheque): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
