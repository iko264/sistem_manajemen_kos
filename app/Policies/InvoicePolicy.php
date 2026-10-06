<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /** Daftar boleh dibuka semua user login; isinya dibatasi di query (tenant: miliknya). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $this->owns($user, $invoice);
    }

    public function generate(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Hanya tenant pemilik invoice yang boleh membayar. */
    public function pay(User $user, Invoice $invoice): bool
    {
        return ! $user->isAdmin() && $this->owns($user, $invoice);
    }

    private function owns(User $user, Invoice $invoice): bool
    {
        return $invoice->tenancy?->user_id === $user->id;
    }
}
