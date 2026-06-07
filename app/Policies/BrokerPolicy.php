<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Broker;
use App\Models\User;

class BrokerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Broker $broker): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Broker $broker): bool
    {
        return true;
    }

    public function delete(User $user, Broker $broker): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
