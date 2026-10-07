<?php

namespace App\Policies;

use App\Models\Tenancy;
use App\Models\User;

class TenancyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tenancy $tenancy): bool
    {
        return $user->role === 'admin' || $tenancy->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function checkout(User $user, Tenancy $tenancy): bool
    {
        return $user->role === 'admin';
    }
}