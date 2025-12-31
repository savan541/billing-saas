<?php

namespace App\Listeners;

use App\Events\RecurringInvoiceGenerated;
use App\Mail\RecurringInvoiceGeneratedMail;
use Illuminate\Support\Facades\Mail;

class SendRecurringInvoiceGeneratedEmail
{
    public function handle(RecurringInvoiceGenerated $event): void
    {
        $invoice = $event->invoice;
        $user = $invoice->user;

        if (!$this->shouldSendEmail($user, 'recurring_invoice_generated')) {
            return;
        }

        Mail::to($invoice->client->email)
            ->queue(new RecurringInvoiceGeneratedMail($invoice, $event->recurringInvoice));
    }

    private function shouldSendEmail($user, string $type): bool
    {
        return $user->getEmailNotificationPreference($type);
    }
}
