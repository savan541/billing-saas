<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        private InvoicePdfService $pdfService
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice #{$this->invoice->invoice_number} Created",
        );
    }

    public function attachments(): array
    {
        try {
            $pdfPath = $this->pdfService->generatePdf($this->invoice);
            
            return [
                Attachment::fromStorageDisk('local', $pdfPath)
                    ->as("invoice-{$this->invoice->invoice_number}.pdf")
                    ->withMime('application/pdf'),
            ];
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-created',
            with: [
                'invoice' => $this->invoice,
                'client' => $this->invoice->client,
                'companyName' => $this->invoice->user->name,
            ]
        );
    }
}
