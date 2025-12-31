<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecurringInvoiceGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public RecurringInvoice $recurringInvoice
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recurring Invoice Generated: #{$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recurring-invoice-generated',
            with: [
                'invoice' => $this->invoice,
                'recurringInvoice' => $this->recurringInvoice,
                'client' => $this->invoice->client,
                'companyName' => $this->invoice->user->name,
                'nextRunDate' => $this->recurringInvoice->next_run_date,
            ]
        );
    }
}
