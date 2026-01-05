<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceActivity;
use Illuminate\Support\Facades\DB;

class RealTimeAutomationService
{
    public function __construct(
        private InvoiceActivityService $activityService
    ) {}

    /**
     * Run automations when user visits relevant pages
     * Call this from controllers/middleware
     */
    public function runOnPageLoad(int $userId): array
    {
        $results = [
            'overdue_checked' => $this->checkOverdueInvoices($userId),
            'reminders_checked' => $this->checkDueReminders($userId),
            'recurring_checked' => $this->checkRecurringInvoices($userId),
        ];

        return $results;
    }

    /**
     * Check and mark overdue invoices for this user only
     */
    private function checkOverdueInvoices(int $userId): array
    {
        $count = 0;
        
        $overdueInvoices = Invoice::where('user_id', $userId)
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->limit(10) // Limit to prevent long page loads
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                DB::transaction(function () use ($invoice, &$count) {
                    $freshInvoice = Invoice::lockForUpdate()->find($invoice->id);
                    
                    if ($freshInvoice->status === 'sent' && $freshInvoice->due_date->isPast()) {
                        $freshInvoice->status = 'overdue';
                        $freshInvoice->save();

                        $this->activityService->log(
                            $freshInvoice,
                            'marked_overdue',
                            [
                                'automated' => true,
                                'days_overdue' => $freshInvoice->due_date->diffInDays(now())
                            ]
                        );

                        $count++;
                    }
                });
            } catch (\Exception $e) {
                // Log error but don't break page load
                \Log::error('Automation error: ' . $e->getMessage());
            }
        }

        return ['processed' => $count];
    }

    /**
     * Check for due reminders for this user only
     */
    private function checkDueReminders(int $userId): array
    {
        $count = 0;
        
        // Due soon reminders (7 days before due)
        $dueSoonInvoices = Invoice::where('user_id', $userId)
            ->where('status', 'sent')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(7))
            ->whereDoesntHave('activities', function ($query) {
                $query->where('action', 'due_soon_reminder')
                    ->where('created_at', '>=', now()->subDays(7));
            })
            ->limit(5)
            ->get();

        foreach ($dueSoonInvoices as $invoice) {
            try {
                $this->activityService->log(
                    $invoice,
                    'due_soon_reminder',
                    [
                        'automated' => true,
                        'days_until_due' => now()->diffInDays($invoice->due_date)
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                \Log::error('Reminder error: ' . $e->getMessage());
            }
        }

        return ['reminders_sent' => $count];
    }

    /**
     * Check recurring invoices for this user only
     */
    private function checkRecurringInvoices(int $userId): array
    {
        $count = 0;
        
        $dueRecurring = \App\Models\RecurringInvoice::where('user_id', $userId)
            ->where('status', 'active')
            ->where('next_run_date', '<=', now())
            ->limit(3) // Limit to prevent long page loads
            ->get();

        foreach ($dueRecurring as $recurringInvoice) {
            try {
                DB::transaction(function () use ($recurringInvoice, &$count) {
                    $freshRecurring = \App\Models\RecurringInvoice::lockForUpdate()->find($recurringInvoice->id);
                    
                    if ($freshRecurring->status === 'active' && 
                        $freshRecurring->next_run_date->isPast() &&
                        !$this->alreadyGeneratedForPeriod($freshRecurring)) {
                        
                        // Generate invoice
                        $invoice = $this->generateInvoiceFromRecurring($freshRecurring);
                        
                        // Update next run date
                        $freshRecurring->last_run_date = now();
                        $freshRecurring->next_run_date = $this->calculateNextRunDate($freshRecurring);
                        $freshRecurring->save();

                        $this->activityService->log(
                            $invoice,
                            'generated_from_recurring',
                            [
                                'automated' => true,
                                'recurring_invoice_id' => $freshRecurring->id
                            ]
                        );

                        $count++;
                    }
                });
            } catch (\Exception $e) {
                \Log::error('Recurring error: ' . $e->getMessage());
            }
        }

        return ['invoices_generated' => $count];
    }

    private function alreadyGeneratedForPeriod($recurringInvoice): bool
    {
        $periodStart = now()->subMonth(); // Simplified for monthly
        
        return Invoice::where('recurring_invoice_id', $recurringInvoice->id)
            ->where('created_at', '>=', $periodStart)
            ->exists();
    }

    private function generateInvoiceFromRecurring($recurringInvoice): Invoice
    {
        return Invoice::create([
            'user_id' => $recurringInvoice->user_id,
            'client_id' => $recurringInvoice->client_id,
            'recurring_invoice_id' => $recurringInvoice->id,
            'status' => 'sent',
            'subtotal' => $recurringInvoice->amount,
            'tax' => 0,
            'total' => $recurringInvoice->amount,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'notes' => "Generated from: {$recurringInvoice->title}",
            'currency' => 'USD'
        ]);
    }

    private function calculateNextRunDate($recurringInvoice): \Carbon\Carbon
    {
        return $recurringInvoice->next_run_date->copy()->addMonth();
    }
}
