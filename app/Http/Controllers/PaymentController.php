<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->getRemainingBalance(),
            'payment_method' => 'required|in:cash,bank_transfer,upi,card',
            'payment_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ], [
            'amount.max' => 'Payment amount cannot exceed the remaining balance of ' . $invoice->getFormattedRemainingBalance(),
        ]);

        try {
            DB::transaction(function () use ($validated, $invoice) {
                $payment = Payment::create([
                    'user_id' => Auth::id(),
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => $validated['payment_date'],
                    'notes' => $validated['notes'],
                ]);

                $invoice->updatePaymentStatus();
            });

            return back()
                ->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to record payment. Please try again.');
        }
    }
}
