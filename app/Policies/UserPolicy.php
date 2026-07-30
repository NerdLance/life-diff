<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(?User $viewer, User $user): bool
    {
        return true;
    }

    public function update(User $viewer, User $user): bool
    {
        return $viewer->is($user);
    }
}
