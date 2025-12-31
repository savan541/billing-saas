<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Mail\InvoicePaidMail;
use Illuminate\Support\Facades\Mail;

class SendInvoicePaidEmail
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $user = $invoice->user;

        if (!$this->shouldSendEmail($user, 'invoice_paid')) {
            return;
        }

        Mail::to($invoice->client->email)
            ->queue(new InvoicePaidMail($invoice));
    }

    private function shouldSendEmail($user, string $type): bool
    {
        return $user->getEmailNotificationPreference($type);
    }
}
