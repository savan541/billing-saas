<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceActivity;

class InvoiceActivityService
{
    public function log(Invoice $invoice, string $action, array $metadata = []): InvoiceActivity
    {
        return $invoice->activities()->create([
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }

    public function logCreated(Invoice $invoice): InvoiceActivity
    {
        return $this->log($invoice, 'created', [
            'invoice_number' => $invoice->invoice_number,
            'total' => $invoice->total,
            'client_id' => $invoice->client_id,
        ]);
    }

    public function logSent(Invoice $invoice): InvoiceActivity
    {
        return $this->log($invoice, 'sent', [
            'sent_at' => now()->toISOString(),
            'client_email' => $invoice->client?->email,
        ]);
    }

    public function logPaid(Invoice $invoice, float $amount = null): InvoiceActivity
    {
        return $this->log($invoice, 'paid', [
            'paid_at' => $invoice->paid_at?->toISOString(),
            'amount' => $amount ?? $invoice->total,
        ]);
    }

    public function logPaymentReceived(Invoice $invoice, float $amount, string $method = null): InvoiceActivity
    {
        return $this->log($invoice, 'payment_received', [
            'amount' => $amount,
            'method' => $method,
            'received_at' => now()->toISOString(),
        ]);
    }

    public function logPdfGenerated(Invoice $invoice): InvoiceActivity
    {
        return $this->log($invoice, 'pdf_generated', [
            'generated_at' => now()->toISOString(),
        ]);
    }

    public function logCancelled(Invoice $invoice, string $reason = null): InvoiceActivity
    {
        return $this->log($invoice, 'cancelled', [
            'cancelled_at' => now()->toISOString(),
            'reason' => $reason,
        ]);
    }

    public function logUpdated(Invoice $invoice, array $changes): InvoiceActivity
    {
        return $this->log($invoice, 'updated', [
            'changes' => $changes,
            'updated_at' => now()->toISOString(),
        ]);
    }

    public function logDeleted(Invoice $invoice): InvoiceActivity
    {
        return $this->log($invoice, 'deleted', [
            'deleted_at' => now()->toISOString(),
            'invoice_number' => $invoice->invoice_number,
        ]);
    }

    public function getTimeline(Invoice $invoice): array
    {
        return $invoice->activities()
            ->with('invoice.client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'description' => $activity->getDescription(),
                    'icon' => $activity->getIcon(),
                    'color' => $activity->getColor(),
                    'metadata' => $activity->metadata,
                    'created_at' => $activity->created_at,
                    'formatted_date' => $activity->created_at->format('M j, Y g:i A'),
                ];
            })
            ->toArray();
    }
}
