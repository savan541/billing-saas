<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceActivity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OverdueInvoiceService
{
    public function __construct(
        private InvoiceActivityService $activityService
    ) {}

    /**
     * Check and update overdue invoices.
     * Idempotent - safe to run multiple times.
     */
    public function processOverdueInvoices(): array
    {
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        // Find sent invoices that are past due date but not already overdue
        $overdueInvoices = Invoice::where('status', 'sent')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                DB::transaction(function () use ($invoice, &$results) {
                    // Double-check status to prevent race conditions
                    $freshInvoice = Invoice::lockForUpdate()->find($invoice->id);
                    
                    if ($freshInvoice->status !== 'sent') {
                        $results['skipped']++;
                        $results['details'][] = [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'reason' => 'Status already changed to ' . $freshInvoice->status
                        ];
                        return;
                    }

                    // Check if already overdue to avoid duplicate processing
                    if ($freshInvoice->due_date->isFuture()) {
                        $results['skipped']++;
                        $results['details'][] = [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'reason' => 'Due date is in future'
                        ];
                        return;
                    }

                    // Update to overdue status
                    $freshInvoice->status = 'overdue';
                    $freshInvoice->save();

                    // Log the automation activity
                    $this->activityService->log(
                        $freshInvoice,
                        'marked_overdue',
                        [
                            'automated' => true,
                            'overdue_since' => $freshInvoice->due_date->toDateString(),
                            'days_overdue' => $freshInvoice->due_date->diffInDays(now()),
                            'processed_at' => now()->toISOString()
                        ]
                    );

                    $results['processed']++;
                    $results['details'][] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'action' => 'marked_overdue',
                        'days_overdue' => $freshInvoice->due_date->diffInDays(now())
                    ];
                });
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get overdue invoices summary
     */
    public function getOverdueSummary(): array
    {
        $overdueInvoices = Invoice::where('status', 'overdue')
            ->with(['client'])
            ->get();

        $summary = [
            'total_count' => $overdueInvoices->count(),
            'total_amount' => $overdueInvoices->sum('total'),
            'by_age' => [
                '1-30_days' => $overdueInvoices->filter(fn($inv) => $inv->due_date->diffInDays(now()) <= 30)->sum('total'),
                '31-60_days' => $overdueInvoices->filter(fn($inv) => $inv->due_date->diffInDays(now()) > 30 && $inv->due_date->diffInDays(now()) <= 60)->sum('total'),
                '61-90_days' => $overdueInvoices->filter(fn($inv) => $inv->due_date->diffInDays(now()) > 60 && $inv->due_date->diffInDays(now()) <= 90)->sum('total'),
                '90+_days' => $overdueInvoices->filter(fn($inv) => $inv->due_date->diffInDays(now()) > 90)->sum('total'),
            ],
            'recently_overdue' => $overdueInvoices
                ->filter(fn($inv) => $inv->due_date->diffInDays(now()) <= 7)
                ->map(fn($inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'client_name' => $inv->client?->name,
                    'amount' => $inv->total,
                    'days_overdue' => $inv->due_date->diffInDays(now()),
                    'due_date' => $inv->due_date->toDateString()
                ])
                ->values()
        ];

        return $summary;
    }

    /**
     * Check if invoice should be marked as overdue
     */
    private function shouldMarkAsOverdue(Invoice $invoice): bool
    {
        return $invoice->status === 'sent' && $invoice->due_date->isPast();
    }

    /**
     * Get invoices that will become overdue soon
     */
    public function getUpcomingOverdue(int $days = 7): array
    {
        $upcomingInvoices = Invoice::where('status', 'sent')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addDays($days))
            ->with(['client'])
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'client_name' => $inv->client?->name,
                'amount' => $inv->total,
                'due_date' => $inv->due_date->toDateString(),
                'days_until_due' => now()->diffInDays($inv->due_date)
            ]);

        return $upcomingInvoices->toArray();
    }
}
