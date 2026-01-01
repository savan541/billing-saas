<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceActivityService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    public function __construct(
    private InvoiceService $invoiceService,
    private InvoicePdfService $pdfService,
    private InvoiceActivityService $activityService
    ) {
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index(Request $request)
    {
        $invoices = Auth::user()
            ->invoices()
            ->with('client')
            ->when($request->search, function ($query, $search) {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only('search', 'status'),
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
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $invoice = $this->invoiceService->createInvoice($data);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'payments', 'activities']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'activities' => $this->activityService->getTimeline($invoice),
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
        $data = $request->validated();

        $invoice = $this->invoiceService->updateInvoice($invoice, $data);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->invoiceService->deleteInvoice($invoice);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    public function download(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        
        try {
            $pdfPath = $this->pdfService->generatePdf($invoice);
            $this->activityService->logPdfGenerated($invoice);
            
            return Storage::disk('local')->download(
                $pdfPath,
                "invoice-{$invoice->invoice_number}.pdf"
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Cannot download PDF for draft invoices.');
        }
    }

    public function send(Invoice $invoice)
    {
        $this->authorize('send', $invoice);

        $invoice->markAsSent();
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice sent successfully.');
    }

    public function cancel(Invoice $invoice)
    {
        $this->authorize('cancel', $invoice);

        $invoice->cancel();
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice cancelled successfully.');
    }

    public function markAsPaid(Invoice $invoice)
    {
        $this->authorize('markAsPaid', $invoice);

        $invoice->markAsPaid();
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice marked as paid successfully.');
    }
}
