<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Mail\InvoicePaidMail;
use App\Services\InvoicePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendInvoicePaidEmail implements ShouldQueue
{
    public function __construct(private InvoicePdfService $pdfService)
    {
    }
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $user = $invoice->user;

        if (!$this->shouldSendEmail($user, 'invoice_paid')) {
            return;
        }

        Mail::to($invoice->client->email)
            ->queue(new InvoicePaidMail($invoice, $this->pdfService));
    }

    private function shouldSendEmail($user, string $type): bool
    {
        return $user->getEmailNotificationPreference($type);
    }
}
