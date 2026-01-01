<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class InvoicePaymentController extends Controller
{
    public function createCheckoutSession(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        if ($invoice->isPaid()) {
            return back()->with('error', 'This invoice has already been paid.');
        }

        if (!$invoice->isSent()) {
            return back()->with('error', 'Only sent invoices can be paid.');
        }

        Stripe::setApiKey(config('stripe.secret_key'));

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $invoice->currency?->value ?? config('stripe.currency', 'usd'),
                        'product_data' => [
                            'name' => "Invoice #{$invoice->invoice_number}",
                            'description' => "Invoice for {$invoice->client->name}",
                        ],
                        'unit_amount' => (int) ($invoice->total * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('invoices.payment.success', $invoice) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('invoices.payment.cancel', $invoice),
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ],
                'customer_email' => Auth::user()->email,
            ]);

            // For Inertia.js, use Location response for external redirect
            return Inertia::location($session->url);
        } catch (ApiErrorException $e) {
            return back()->with('error', 'Unable to create payment session. Please try again.');
        }
    }

    public function success(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Payment session information is missing.');
        }

        Stripe::setApiKey(config('stripe.secret_key'));

        try {
            $session = Session::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('invoices.show', $invoice)
                    ->with('error', 'Payment was not successful.');
            }

            // Update invoice with Stripe information
            $invoice->update([
                'stripe_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Payment completed successfully!');
        } catch (ApiErrorException $e) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Unable to verify payment. Please contact support.');
        }
    }

    public function cancel(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('info', 'Payment was cancelled. You can try again later.');
    }
}
