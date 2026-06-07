<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, StockTransfer $transfer): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, StockTransfer $transfer): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
