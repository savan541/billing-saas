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
                'currency' => $client->currency ?? 'USD',
            ]);
            
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
            
            // Only update tax rate and currency if client changed
            $clientChanged = $clientId !== $invoice->client_id;
            if ($clientChanged) {
                $taxRate = $client->getEffectiveTaxRate();
                $data = array_merge($data, [
                    'invoice_tax_rate' => $taxRate,
                    'tax_exempt_at_time' => $client->tax_exempt ?? false,
                    'currency' => $client->currency ?? 'USD',
                ]);
            } else {
                // Use existing tax rate and currency for consistency
                $taxRate = $invoice->getTaxRateAtTime();
            }
            
            $totals = $this->calculateInvoiceTotals($items, $taxRate);
            $data = array_merge($data, $totals);
            
            $invoice->update($data);
            
            // Remove existing items and create new ones
            $invoice->items()->delete();
            
            foreach ($items as $itemData) {
                $itemData['total'] = (float) ($itemData['quantity'] ?? 0) * (float) ($itemData['unit_price'] ?? 0);
                $invoice->items()->create($itemData);
            }
            
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
