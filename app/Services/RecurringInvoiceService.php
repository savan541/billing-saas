<?php

namespace App\Services;

use App\Models\RecurringInvoice;
use App\Models\Invoice;
use App\Models\InvoiceActivity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecurringInvoiceService
{
    public function __construct(
        private InvoiceActivityService $activityService
    ) {}

    /**
     * Generate invoices for due recurring profiles.
     * Idempotent - prevents duplicate invoice generation.
     */
    public function processDueInvoices(): array
    {
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        // Get active recurring invoices that are due
        $dueRecurringInvoices = RecurringInvoice::where('status', 'active')
            ->where('next_run_date', '<=', now())
            ->get();

        foreach ($dueRecurringInvoices as $recurringInvoice) {
            try {
                DB::transaction(function () use ($recurringInvoice, &$results) {
                    // Lock and refresh to prevent race conditions
                    $freshRecurring = RecurringInvoice::lockForUpdate()->find($recurringInvoice->id);
                    
                    if ($freshRecurring->status !== 'active') {
                        $results['skipped']++;
                        $results['details'][] = [
                            'recurring_id' => $recurringInvoice->id,
                            'title' => $recurringInvoice->title,
                            'reason' => 'Status changed to ' . $freshRecurring->status
                        ];
                        return;
                    }

                    // Check if we already generated an invoice for this period
                    if ($this->alreadyGeneratedForPeriod($freshRecurring)) {
                        $results['skipped']++;
                        $results['details'][] = [
                            'recurring_id' => $recurringInvoice->id,
                            'title' => $recurringInvoice->title,
                            'reason' => 'Invoice already generated for this period'
                        ];
                        return;
                    }

                    // Generate the invoice
                    $invoice = $this->generateInvoiceFromRecurring($freshRecurring);

                    // Update next run date
                    $freshRecurring->last_run_date = now();
                    $freshRecurring->next_run_date = $this->calculateNextRunDate($freshRecurring);
                    $freshRecurring->save();

                    // Log the automation
                    $this->activityService->log(
                        $invoice,
                        'generated_from_recurring',
                        [
                            'automated' => true,
                            'recurring_invoice_id' => $freshRecurring->id,
                            'recurring_title' => $freshRecurring->title,
                            'frequency' => $freshRecurring->frequency,
                            'next_run_date' => $freshRecurring->next_run_date->toDateString(),
                            'processed_at' => now()->toISOString()
                        ]
                    );

                    $results['processed']++;
                    $results['details'][] = [
                        'recurring_id' => $recurringInvoice->id,
                        'title' => $recurringInvoice->title,
                        'generated_invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->total,
                        'next_run_date' => $freshRecurring->next_run_date->toDateString()
                    ];
                });
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'recurring_id' => $recurringInvoice->id,
                    'title' => $recurringInvoice->title,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Check if invoice already generated for current period
     */
    private function alreadyGeneratedForPeriod(RecurringInvoice $recurringInvoice): bool
    {
        $periodStart = $this->getPeriodStart($recurringInvoice);
        
        return Invoice::where('recurring_invoice_id', $recurringInvoice->id)
            ->where('created_at', '>=', $periodStart)
            ->exists();
    }

    /**
     * Get period start date based on frequency
     */
    private function getPeriodStart(RecurringInvoice $recurringInvoice): Carbon
    {
        $frequency = $recurringInvoice->frequency;
        $lastRun = $recurringInvoice->last_run_date ?? $recurringInvoice->start_date;

        return match($frequency) {
            'monthly' => $lastRun->copy()->subMonth(),
            'quarterly' => $lastRun->copy()->subMonths(3),
            'yearly' => $lastRun->copy()->subYear(),
            default => $lastRun->copy()->subMonth(),
        };
    }

    /**
     * Generate invoice from recurring template
     */
    private function generateInvoiceFromRecurring(RecurringInvoice $recurringInvoice): Invoice
    {
        $invoiceData = [
            'user_id' => $recurringInvoice->user_id,
            'client_id' => $recurringInvoice->client_id,
            'recurring_invoice_id' => $recurringInvoice->id,
            'status' => 'sent',
            'subtotal' => $recurringInvoice->amount,
            'tax' => 0,
            'discount' => 0,
            'total' => $recurringInvoice->amount,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'notes' => $this->generateInvoiceNotes($recurringInvoice),
            'currency' => 'USD', // Default currency, can be enhanced
        ];

        return Invoice::create($invoiceData);
    }

    /**
     * Generate invoice notes from recurring template
     */
    private function generateInvoiceNotes(RecurringInvoice $recurringInvoice): string
    {
        $baseNotes = "Generated from recurring invoice: {$recurringInvoice->title}";
        
        if ($recurringInvoice->notes) {
            $baseNotes .= "\n\n{$recurringInvoice->notes}";
        }

        $baseNotes .= "\n\nFrequency: {$recurringInvoice->getFrequencyLabel()}";
        $baseNotes .= "\nGenerated: " . now()->format('M j, Y');

        return $baseNotes;
    }

    /**
     * Calculate next run date based on frequency
     */
    private function calculateNextRunDate(RecurringInvoice $recurringInvoice): Carbon
    {
        $nextRun = $recurringInvoice->next_run_date ?? $recurringInvoice->start_date;
        
        return match($recurringInvoice->frequency) {
            'monthly' => $nextRun->copy()->addMonth(),
            'quarterly' => $nextRun->copy()->addMonths(3),
            'yearly' => $nextRun->copy()->addYear(),
            default => $nextRun->copy()->addMonth(),
        };
    }

    /**
     * Get recurring invoices summary
     */
    public function getRecurringSummary(): array
    {
        $recurringInvoices = RecurringInvoice::with(['client'])->get();

        return [
            'total_active' => $recurringInvoices->where('status', 'active')->count(),
            'total_paused' => $recurringInvoices->where('status', 'paused')->count(),
            'total_cancelled' => $recurringInvoices->where('status', 'cancelled')->count(),
            'monthly_revenue' => $recurringInvoices
                ->where('status', 'active')
                ->where('frequency', 'monthly')
                ->sum('amount'),
            'quarterly_revenue' => $recurringInvoices
                ->where('status', 'active')
                ->where('frequency', 'quarterly')
                ->sum('amount'),
            'yearly_revenue' => $recurringInvoices
                ->where('status', 'active')
                ->where('frequency', 'yearly')
                ->sum('amount'),
            'upcoming_runs' => $recurringInvoices
                ->where('status', 'active')
                ->where('next_run_date', '<=', now()->addDays(30))
                ->map(fn($inv) => [
                    'id' => $inv->id,
                    'title' => $inv->title,
                    'client_name' => $inv->client?->name,
                    'amount' => $inv->amount,
                    'frequency' => $inv->frequency,
                    'next_run_date' => $inv->next_run_date->toDateString(),
                    'days_until_run' => now()->diffInDays($inv->next_run_date)
                ])
                ->values()
        ];
    }

    /**
     * Legacy methods for backward compatibility
     */
    public function createRecurringInvoice(array $data): RecurringInvoice
    {
        return DB::transaction(function () use ($data) {
            return RecurringInvoice::create($data);
        });
    }

    public function updateRecurringInvoice(RecurringInvoice $recurringInvoice, array $data): RecurringInvoice
    {
        return DB::transaction(function () use ($recurringInvoice, $data) {
            $recurringInvoice->update($data);
            return $recurringInvoice->fresh();
        });
    }

    public function deleteRecurringInvoice(RecurringInvoice $recurringInvoice): void
    {
        DB::transaction(function () use ($recurringInvoice) {
            $recurringInvoice->delete();
        });
    }
}
