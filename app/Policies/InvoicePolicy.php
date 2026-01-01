<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Invoice $invoice): Response
    {
        return $user->id === $invoice->user_id
            ? Response::allow()
            : Response::deny('You can only view your own invoices.');
    }

    public function create(User $user): Response
    {
        return $user->clients()->exists()
            ? Response::allow()
            : Response::deny('You need to have at least one client to create invoices.');
    }

    public function update(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('You can only edit your own invoices.');
        }

        if ($invoice->isPaid()) {
            return Response::deny('Paid invoices cannot be modified.');
        }

        return Response::allow();
    }

    public function delete(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('You can only delete your own invoices.');
        }

        if ($invoice->isPaid()) {
            return Response::deny('Paid invoices cannot be deleted.');
        }

        return Response::allow();
    }

    public function manageItems(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('You can only manage items in your own invoices.');
        }

        if ($invoice->isPaid()) {
            return Response::deny('Items cannot be modified in paid invoices.');
        }

        return Response::allow();
    }

    public function updateStatus(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('You can only change status of your own invoices.');
        }

        return Response::allow();
    }

    public function send(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('You can only send your own invoices.');
        }

        if (!$invoice->canBeSent()) {
            return Response::deny('This invoice cannot be sent.');
        }

        return Response::allow();
    }

    public function cancel(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('You can only cancel your own invoices.');
        }

        if (!$invoice->canBeCancelled()) {
            return Response::deny('This invoice cannot be cancelled.');
        }

        return Response::allow();
    }

    public function markAsPaid(User $user, Invoice $invoice): Response
    {
        if ($user->id !== $invoice->user_id) {
            return Response::deny('You can only mark your own invoices as paid.');
        }

        if (!$invoice->canBeMarkedAsPaid()) {
            return Response::deny('This invoice cannot be marked as paid.');
        }

        return Response::allow();
    }
}
