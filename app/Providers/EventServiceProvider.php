<?php

namespace App\Providers;

use App\Events\InvoiceCreated;
use App\Events\InvoicePaid;
use App\Events\PaymentRecorded;
use App\Events\RecurringInvoiceGenerated;
use App\Listeners\SendInvoiceCreatedEmail;
use App\Listeners\SendInvoicePaidEmail;
use App\Listeners\SendPaymentReceiptEmail;
use App\Listeners\SendRecurringInvoiceGeneratedEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        InvoiceCreated::class => [
            SendInvoiceCreatedEmail::class,
        ],
        InvoicePaid::class => [
            SendInvoicePaidEmail::class,
        ],
        PaymentRecorded::class => [
            SendPaymentReceiptEmail::class,
        ],
        RecurringInvoiceGenerated::class => [
            SendRecurringInvoiceGeneratedEmail::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
