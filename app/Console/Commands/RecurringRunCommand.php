<?php

namespace App\Console\Commands;

use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;

class RecurringRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices from due recurring invoices';

    /**
     * Execute the console command.
     */
    public function handle(RecurringInvoiceService $recurringInvoiceService)
    {
        $this->info('Starting recurring invoice generation...');

        try {
            $count = $recurringInvoiceService->generateDueInvoices();
            
            $this->info("Successfully generated {$count} invoice(s) from recurring templates.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error generating recurring invoices: ' . $e->getMessage());
            
            return 1;
        }
    }
}
