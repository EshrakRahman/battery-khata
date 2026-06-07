<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\NotificationLog;
use App\Models\User;

class NotificationLogPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function view(User $user, NotificationLog $log): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function update(User $user, NotificationLog $log): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, NotificationLog $log): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
