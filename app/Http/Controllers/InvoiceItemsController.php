<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class InvoiceItemsController extends Controller
{
    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $item = $invoice->items()->create($validated);

        $invoice->refresh();
        $this->updateInvoiceTotals($invoice);

        return response()->json([
            'item' => $item,
            'invoice' => $invoice,
        ]);
    }

    public function update(Request $request, Invoice $invoice, InvoiceItem $item): JsonResponse
    {
        $this->authorize('update', $invoice);

        if ($item->invoice_id !== $invoice->id) {
            abort(404);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $item->update($validated);

        $invoice->refresh();
        $this->updateInvoiceTotals($invoice);

        return response()->json([
            'item' => $item,
            'invoice' => $invoice,
        ]);
    }

    public function destroy(Invoice $invoice, InvoiceItem $item): JsonResponse
    {
        $this->authorize('update', $invoice);

        if ($item->invoice_id !== $invoice->id) {
            abort(404);
        }

        $item->delete();

        $invoice->refresh();
        $this->updateInvoiceTotals($invoice);

        return response()->json([
            'invoice' => $invoice,
        ]);
    }

    private function updateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->items()->sum('total');
        $tax = $subtotal * 0.1; // 10% tax rate
        $total = $subtotal + $tax;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }
}
