<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        if (!$webhookSecret) {
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                break;

            default:
                return response()->json(['received' => true]);
        }

        return response()->json(['received' => true]);
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        $invoiceId = $session->metadata->invoice_id ?? null;

        if (!$invoiceId) {
            return;
        }

        $invoice = Invoice::find($invoiceId);

        if (!$invoice || $invoice->isPaid()) {
            return;
        }

        // Verify the payment was successful
        if ($session->payment_status === 'paid') {
            $invoice->update([
                'stripe_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }
    }
}
