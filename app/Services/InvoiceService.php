<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function calculateInvoiceTotals(array $items): array
    {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $subtotal += $quantity * $unitPrice;
        }
        
        $tax = $subtotal * 0.10; // 10% tax rate
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
            
            $totals = $this->calculateInvoiceTotals($items);
            $data = array_merge($data, $totals);
            
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
            
            $totals = $this->calculateInvoiceTotals($items);
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
        $totals = $this->calculateInvoiceTotals($items);
        
        $invoice->update($totals);
        
        return $invoice->fresh();
    }
}
