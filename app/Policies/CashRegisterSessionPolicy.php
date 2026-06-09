<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CashRegisterSession;
use App\Models\User;

class CashRegisterSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CashRegisterSession $session): bool
    {
        if (in_array($user->role, [UserRole::Admin, UserRole::Manager])) {
            return true;
        }

        return $session->opened_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CashRegisterSession $session): bool
    {
        if (in_array($user->role, [UserRole::Admin, UserRole::Manager])) {
            return true;
        }

        return $session->opened_by === $user->id;
    }

    public function delete(User $user, CashRegisterSession $session): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
