<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringInvoiceRequest;
use App\Http\Requests\UpdateRecurringInvoiceRequest;
use App\Models\RecurringInvoice;
use App\Services\RecurringInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RecurringInvoiceController extends Controller
{
    public function __construct(private RecurringInvoiceService $recurringInvoiceService)
    {
        $this->authorizeResource(RecurringInvoice::class, 'recurring_invoice');
    }

    public function index(Request $request)
    {
        $recurringInvoices = Auth::user()
            ->recurringInvoices()
            ->with('client')
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->frequency, function ($query, $frequency) {
                $query->where('frequency', $frequency);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('RecurringInvoices/Index', [
            'recurringInvoices' => $recurringInvoices,
            'filters' => $request->only('search', 'status', 'frequency'),
        ]);
    }

    public function create()
    {
        $clients = Auth::user()->clients()->orderBy('name')->get();

        return Inertia::render('RecurringInvoices/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(StoreRecurringInvoiceRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $recurringInvoice = $this->recurringInvoiceService->createRecurringInvoice($data);

        return redirect()->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice created successfully.');
    }

    public function show(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->load(['client', 'invoices' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return Inertia::render('RecurringInvoices/Show', [
            'recurringInvoice' => $recurringInvoice,
        ]);
    }

    public function edit(RecurringInvoice $recurringInvoice)
    {
        $clients = Auth::user()->clients()->orderBy('name')->get();

        return Inertia::render('RecurringInvoices/Edit', [
            'recurringInvoice' => $recurringInvoice,
            'clients' => $clients,
        ]);
    }

    public function update(UpdateRecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice)
    {
        $data = $request->validated();

        $recurringInvoice = $this->recurringInvoiceService->updateRecurringInvoice($recurringInvoice, $data);

        return redirect()->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice updated successfully.');
    }

    public function destroy(RecurringInvoice $recurringInvoice)
    {
        $this->recurringInvoiceService->deleteRecurringInvoice($recurringInvoice);

        return redirect()->route('recurring-invoices.index')
            ->with('success', 'Recurring invoice deleted successfully.');
    }

    public function pause(RecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);
        
        $recurringInvoice->pause();

        return redirect()->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice paused successfully.');
    }

    public function resume(RecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);
        
        $recurringInvoice->resume();

        return redirect()->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice resumed successfully.');
    }

    public function cancel(RecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);
        
        $recurringInvoice->cancel();

        return redirect()->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice cancelled successfully.');
    }
}
