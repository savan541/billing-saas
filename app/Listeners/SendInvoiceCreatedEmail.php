<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Mail\InvoiceCreatedMail;
use Illuminate\Support\Facades\Mail;

class SendInvoiceCreatedEmail
{
    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;
        $user = $invoice->user;

        if (!$this->shouldSendEmail($user, 'invoice_created')) {
            return;
        }

        Mail::to($invoice->client->email)
            ->queue(new InvoiceCreatedMail($invoice));
    }

    private function shouldSendEmail($user, string $type): bool
    {
        return $user->getEmailNotificationPreference($type);
    }
}
