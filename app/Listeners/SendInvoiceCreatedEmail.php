<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Mail\InvoiceCreatedMail;
use App\Services\InvoicePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendInvoiceCreatedEmail implements ShouldQueue
{
    public function __construct(private InvoicePdfService $pdfService)
    {
    }
    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;
        $user = $invoice->user;

        if (!$this->shouldSendEmail($user, 'invoice_created')) {
            return;
        }

        Mail::to($invoice->client->email)
            ->queue(new InvoiceCreatedMail($invoice, $this->pdfService));
    }

    private function shouldSendEmail($user, string $type): bool
    {
        return $user->getEmailNotificationPreference($type);
    }
}
