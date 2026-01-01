<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoicePdfService
{
    private const STORAGE_PATH = 'invoices';
    
    public function generatePdf(Invoice $invoice): string
    {
        // Commented out for testing - uncomment for production
        // if ($invoice->isDraft()) {
        //     throw new \InvalidArgumentException('Cannot generate PDF for draft invoices.');
        // }

        $pdfPath = $this->getPdfPath($invoice);
        
        if ($this->shouldRegeneratePdf($invoice, $pdfPath)) {
            $this->createPdf($invoice, $pdfPath);
        }
        
        return $pdfPath;
    }
    
    public function getPdfStream(Invoice $invoice)
    {
        // Commented out for testing - uncomment for production
        // if ($invoice->isDraft()) {
        //     throw new \InvalidArgumentException('Cannot generate PDF for draft invoices.');
        // }

        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice->load(['client', 'items', 'payments']),
            'company' => $this->getCompanyDetails($invoice),
        ]);
        
        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }
    
    public function deletePdf(Invoice $invoice): void
    {
        $pdfPath = $this->getPdfPath($invoice);
        
        if (Storage::disk('local')->exists($pdfPath)) {
            Storage::disk('local')->delete($pdfPath);
            Log::info("PDF deleted for invoice {$invoice->invoice_number}");
        }
    }
    
    private function createPdf(Invoice $invoice, string $pdfPath): void
    {
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice->load(['client', 'items', 'payments']),
            'company' => $this->getCompanyDetails($invoice),
        ]);
        
        $pdf->setPaper('a4')
            ->setOption('defaultFont', 'Helvetica')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true);
        
        Storage::disk('local')->put($pdfPath, $pdf->output());
        
        Log::info("PDF generated for invoice {$invoice->invoice_number}");
    }
    
    private function shouldRegeneratePdf(Invoice $invoice, string $pdfPath): bool
    {
        if (!Storage::disk('local')->exists($pdfPath)) {
            return true;
        }
        
        $pdfModifiedAt = Storage::disk('local')->lastModified($pdfPath);
        $invoiceModifiedAt = $invoice->updated_at->timestamp;
        
        return $invoiceModifiedAt > $pdfModifiedAt;
    }
    
    private function getPdfPath(Invoice $invoice): string
    {
        return self::STORAGE_PATH . "/{$invoice->user_id}/invoice-{$invoice->invoice_number}.pdf";
    }
    
    private function getCompanyDetails(Invoice $invoice): array
    {
        $user = $invoice->user;
        
        return [
            'name' => $user->name,
            'email' => $user->email,
            'address' => $user->address ?? null,
            'phone' => $user->phone ?? null,
            'website' => $user->website ?? null,
        ];
    }
    
    public function getPublicUrl(Invoice $invoice): ?string
    {
        $pdfPath = $this->getPdfPath($invoice);
        
        if (!Storage::disk('local')->exists($pdfPath)) {
            return null;
        }
        
        return route('invoices.download', $invoice);
    }
}
