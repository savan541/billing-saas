<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function calculateInvoiceTotals(array $items, float $taxRate = 0.00): array
    {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $subtotal += $quantity * $unitPrice;
        }
        
        $tax = $subtotal * $taxRate;
        $discount = 0; // Can be made configurable later
        $total = $subtotal + $tax - $discount;
        
        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'discount' => round($discount, 2),
            'total' => round($total, 2),
        ];
    }
    
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);
            
            $clientId = $data['client_id'];
            $client = \App\Models\Client::find($clientId);
            $taxRate = $client->getEffectiveTaxRate();
            
            $totals = $this->calculateInvoiceTotals($items, $taxRate);
            $data = array_merge($data, $totals, [
                'invoice_tax_rate' => $taxRate,
                'tax_exempt_at_time' => $client->tax_exempt ?? false,
                'currency' => $client->currency instanceof \App\Enums\Currency 
                    ? $client->currency->value 
                    : ($client->currency ?? 'USD'),
            ]);
            
            // Set paid_at if status is paid
            if (isset($data['status']) && $data['status'] === 'paid') {
                $data['paid_at'] = now();
            }
            
            $invoice = Invoice::create($data);
            
            foreach ($items as $itemData) {
                $itemData['total'] = (float) ($itemData['quantity'] ?? 0) * (float) ($itemData['unit_price'] ?? 0);
                $invoice->items()->create($itemData);
            }
            
            return $invoice->load(['client', 'items']);
        });
    }
    
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);
            
            $clientId = $data['client_id'] ?? $invoice->client_id;
            $client = \App\Models\Client::find($clientId);
            
            // Always recalculate totals from items
            if ($clientId !== $invoice->client_id) {
                // Client changed - use new client's tax rate
                $taxRate = $client->getEffectiveTaxRate();
                $data = array_merge($data, [
                    'invoice_tax_rate' => $taxRate,
                    'tax_exempt_at_time' => $client->tax_exempt ?? false,
                    'currency' => $client->currency instanceof \App\Enums\Currency 
                        ? $client->currency->value 
                        : ($client->currency ?? 'USD'),
                ]);
            } else {
                // Same client - use existing tax rate for consistency
                $taxRate = $invoice->getTaxRateAtTime();
            }
            
            // Always recalculate totals from items
            $totals = $this->calculateInvoiceTotals($items, $taxRate);
            $data = array_merge($data, $totals);
            
            // Set paid_at if status is being changed to paid
            if (isset($data['status']) && $data['status'] === 'paid' && $invoice->status !== 'paid') {
                $data['paid_at'] = now();
            }
            
            // Update invoice with new totals
            $invoice->update($data);
            
            // Remove existing items and create new ones with correct totals
            $invoice->items()->delete();
            
            foreach ($items as $itemData) {
                // Ensure item total is calculated correctly
                $itemData['total'] = (float) ($itemData['quantity'] ?? 0) * (float) ($itemData['unit_price'] ?? 0);
                $invoice->items()->create($itemData);
            }
            
            // Refresh to get updated relationships
            return $invoice->fresh(['client', 'items']);
        });
    }
    
    public function deleteInvoice(Invoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            return $invoice->delete();
        });
    }
    
    /**
     * Recalculate and fix invoice totals based on items
     */
    public function fixInvoiceTotals(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $items = $invoice->items()->get()->toArray();
            $taxRate = $invoice->getTaxRateAtTime();
            
            // Recalculate totals from items
            $totals = $this->calculateInvoiceTotals($items, $taxRate);
            
            // Update invoice with corrected totals
            $invoice->update($totals);
            
            return $invoice->fresh();
        });
    }
    
    /**
     * Fix all invoice totals in the system
     */
    public function fixAllInvoiceTotals(): int
    {
        $fixed = 0;
        Invoice::with('items')->chunk(100, function ($invoices) use (&$fixed) {
            foreach ($invoices as $invoice) {
                $itemsSubtotal = $invoice->items->sum('total');
                $expectedTotal = $itemsSubtotal + ($itemsSubtotal * $invoice->getTaxRateAtTime());
                
                if (abs($invoice->total - $expectedTotal) > 0.01) {
                    $this->fixInvoiceTotals($invoice);
                    $fixed++;
                }
            }
        });
        
        return $fixed;
    }
    
    public function updateInvoiceStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->update(['status' => $status]);
        return $invoice->fresh();
    }
    
    public function recalculateInvoiceTotals(Invoice $invoice): Invoice
    {
        $items = $invoice->items()->get()->toArray();
        $client = $invoice->client;
        $taxRate = $client->getEffectiveTaxRate();
        $totals = $this->calculateInvoiceTotals($items, $taxRate);
        
        $invoice->update($totals);
        
        return $invoice->fresh();
    }
}
