<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id && $invoice->canBeModified();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id && $invoice->canBeModified();
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
