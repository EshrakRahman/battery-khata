<?php

namespace App\Policies;

use App\Models\ScrapCollection;
use App\Models\User;

class ScrapCollectionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ScrapCollection $collection): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false; // Only created via sales checkout flow
    }

    public function update(User $user, ScrapCollection $collection): bool
    {
        return false;
    }

    public function delete(User $user, ScrapCollection $collection): bool
    {
        return false;
    }
}
