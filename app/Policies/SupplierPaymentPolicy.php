<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SupplierPayment;
use App\Models\User;

class SupplierPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupplierPayment $payment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function update(User $user, SupplierPayment $payment): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, SupplierPayment $payment): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
