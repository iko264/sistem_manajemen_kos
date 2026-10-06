<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isAdmin() || $payment->invoice?->tenancy?->user_id === $user->id;
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}
