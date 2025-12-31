<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class InvoiceItemsController extends Controller
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('manageItems', $invoice);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $item = $invoice->items()->create([
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'total' => $validated['quantity'] * $validated['unit_price'],
        ]);

        $this->invoiceService->recalculateInvoiceTotals($invoice);

        return redirect()->route('invoices.show', $invoice);
    }

    public function update(Request $request, Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        $this->authorize('manageItems', $invoice);

        if ($item->invoice_id !== $invoice->id) {
            abort(404, 'Item not found in this invoice.');
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $item->update([
            'description' => $validated['description'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'total' => $validated['quantity'] * $validated['unit_price'],
        ]);

        $this->invoiceService->recalculateInvoiceTotals($invoice);

        return redirect()->route('invoices.show', $invoice);
    }

    public function destroy(Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        $this->authorize('manageItems', $invoice);

        if ($item->invoice_id !== $invoice->id) {
            abort(404, 'Item not found in this invoice.');
        }

        if ($invoice->items()->count() <= 1) {
            return back()->withErrors(['items' => 'Invoice must have at least one item.']);
        }

        $item->delete();

        $this->invoiceService->recalculateInvoiceTotals($invoice);

        return redirect()->route('invoices.show', $invoice);
    }
}
