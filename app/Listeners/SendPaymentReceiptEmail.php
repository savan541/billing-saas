<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Mail\PaymentReceiptMail;
use Illuminate\Support\Facades\Mail;

class SendPaymentReceiptEmail
{
    public function handle(PaymentRecorded $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        if (!$this->shouldSendEmail($user, 'payment_receipt')) {
            return;
        }

        Mail::to($payment->invoice->client->email)
            ->queue(new PaymentReceiptMail($payment));
    }

    private function shouldSendEmail($user, string $type): bool
    {
        return $user->getEmailNotificationPreference($type);
    }
}
