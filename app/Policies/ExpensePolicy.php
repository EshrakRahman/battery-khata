<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Expense $expense): bool
    {
        if (in_array($user->role, [UserRole::Admin, UserRole::Manager])) {
            return true;
        }

        return $expense->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Expense $expense): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
