<?php

namespace App\Events;

use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecurringInvoiceGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public RecurringInvoice $recurringInvoice
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->invoice->user_id),
        ];
    }
}
