<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice #{$this->invoice->invoice_number} Paid",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-paid',
            with: [
                'invoice' => $this->invoice,
                'client' => $this->invoice->client,
                'companyName' => $this->invoice->user->name,
                'totalPaid' => $this->invoice->getTotalPaid(),
                'paidAt' => $this->invoice->paid_at,
            ]
        );
    }
}
