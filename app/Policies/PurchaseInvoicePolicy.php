<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PurchaseInvoice;
use App\Models\User;

class PurchaseInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseInvoice $invoice): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function update(User $user, PurchaseInvoice $invoice): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    public function delete(User $user, PurchaseInvoice $invoice): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }
}
