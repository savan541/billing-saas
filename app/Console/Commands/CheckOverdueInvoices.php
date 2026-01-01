<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class CheckOverdueInvoices extends Command
{
    protected $signature = 'invoices:check-overdue';
    protected $description = 'Check for overdue invoices and update their status';

    public function handle()
    {
        $this->info('Checking for overdue invoices...');

        $overdueCount = Invoice::where('status', 'sent')
            ->where('due_date', '<', now())
            ->count();

        if ($overdueCount > 0) {
            $this->info("Found {$overdueCount} overdue invoices. Updating status...");

            Invoice::where('status', 'sent')
                ->where('due_date', '<', now())
                ->chunk(100, function ($invoices) {
                    foreach ($invoices as $invoice) {
                        $invoice->checkOverdue();
                        $this->line("Updated invoice {$invoice->invoice_number} to overdue");
                    }
                });

            $this->info('Overdue invoices updated successfully.');
        } else {
            $this->info('No overdue invoices found.');
        }

        return 0;
    }
}
