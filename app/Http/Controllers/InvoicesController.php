<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index()
    {
        $invoices = Auth::user()
            ->invoices()
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        $clients = Auth::user()->clients()->orderBy('name')->get();

        return Inertia::render('Invoices/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        $invoice = Auth::user()->invoices()->create([
            'client_id' => $validated['client_id'],
            'number' => $validated['number'],
            'status' => $validated['status'],
            'subtotal' => $validated['subtotal'],
            'tax' => $validated['tax'],
            'total' => $validated['total'],
            'issued_at' => $validated['issued_at'],
            'due_at' => $validated['due_at'],
        ]);

        foreach ($validated['items'] as $item) {
            $invoice->items()->create($item);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        $clients = Auth::user()->clients()->orderBy('name')->get();

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => $clients,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $validated = $request->validated();

        $invoice->update([
            'client_id' => $validated['client_id'],
            'number' => $validated['number'],
            'status' => $validated['status'],
            'subtotal' => $validated['subtotal'],
            'tax' => $validated['tax'],
            'total' => $validated['total'],
            'issued_at' => $validated['issued_at'],
            'due_at' => $validated['due_at'],
        ]);

        $invoice->items()->delete();

        foreach ($validated['items'] as $item) {
            $invoice->items()->create($item);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}
