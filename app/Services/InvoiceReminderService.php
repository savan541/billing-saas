<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceActivity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceReminderService
{
    public function __construct(
        private InvoiceActivityService $activityService
    ) {}

    /**
     * Process all invoice reminders based on configurable schedules.
     * Idempotent - prevents duplicate reminders.
     */
    public function processReminders(): array
    {
        $results = [
            'due_soon_reminders' => $this->processDueSoonReminders(),
            'overdue_reminders' => $this->processOverdueReminders(),
            'follow_up_reminders' => $this->processFollowUpReminders(),
        ];

        return $results;
    }

    /**
     * Send reminders for invoices due soon (default: 7 days before due date)
     */
    public function processDueSoonReminders(int $daysBefore = 7): array
    {
        $results = [
            'sent' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        $dueSoonInvoices = Invoice::where('status', 'sent')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($daysBefore))
            ->whereDoesntHave('activities', function ($query) use ($daysBefore) {
                $query->where('action', 'due_soon_reminder')
                    ->where('created_at', '>=', now()->subDays($daysBefore));
            })
            ->with(['client'])
            ->get();

        foreach ($dueSoonInvoices as $invoice) {
            try {
                DB::transaction(function () use ($invoice, &$results) {
                    // Double-check invoice status
                    $freshInvoice = Invoice::lockForUpdate()->find($invoice->id);
                    
                    if ($freshInvoice->status !== 'sent') {
                        $results['skipped']++;
                        $results['details'][] = [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'reason' => 'Status changed to ' . $freshInvoice->status
                        ];
                        return;
                    }

                    // Log the reminder (in real implementation, this would send email)
                    $this->activityService->log(
                        $freshInvoice,
                        'due_soon_reminder',
                        [
                            'automated' => true,
                            'days_until_due' => now()->diffInDays($freshInvoice->due_date),
                            'client_email' => $freshInvoice->client?->email,
                            'reminder_type' => 'due_soon',
                            'processed_at' => now()->toISOString()
                        ]
                    );

                    $results['sent']++;
                    $results['details'][] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_name' => $invoice->client?->name,
                        'days_until_due' => now()->diffInDays($freshInvoice->due_date),
                        'amount' => $freshInvoice->total
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
     * Send reminders for overdue invoices
     */
    public function processOverdueReminders(): array
    {
        $results = [
            'sent' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        $overdueInvoices = Invoice::where('status', 'overdue')
            ->whereDoesntHave('activities', function ($query) {
                $query->where('action', 'overdue_reminder')
                    ->where('created_at', '>=', now()->subDays(7)); // Weekly overdue reminders
            })
            ->with(['client'])
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                DB::transaction(function () use ($invoice, &$results) {
                    $freshInvoice = Invoice::lockForUpdate()->find($invoice->id);
                    
                    if ($freshInvoice->status !== 'overdue') {
                        $results['skipped']++;
                        $results['details'][] = [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'reason' => 'Status changed to ' . $freshInvoice->status
                        ];
                        return;
                    }

                    $daysOverdue = $freshInvoice->due_date->diffInDays(now());

                    // Log the overdue reminder
                    $this->activityService->log(
                        $freshInvoice,
                        'overdue_reminder',
                        [
                            'automated' => true,
                            'days_overdue' => $daysOverdue,
                            'client_email' => $freshInvoice->client?->email,
                            'reminder_type' => 'overdue',
                            'processed_at' => now()->toISOString()
                        ]
                    );

                    $results['sent']++;
                    $results['details'][] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_name' => $invoice->client?->name,
                        'days_overdue' => $daysOverdue,
                        'amount' => $freshInvoice->total
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
     * Send follow-up reminders for long overdue invoices
     */
    public function processFollowUpReminders(): array
    {
        $results = [
            'sent' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        // Follow up on invoices overdue for more than 30 days
        $longOverdueInvoices = Invoice::where('status', 'overdue')
            ->where('due_date', '<', now()->subDays(30))
            ->whereDoesntHave('activities', function ($query) {
                $query->where('action', 'follow_up_reminder')
                    ->where('created_at', '>=', now()->subDays(14)); // Bi-weekly follow-ups
            })
            ->with(['client'])
            ->get();

        foreach ($longOverdueInvoices as $invoice) {
            try {
                DB::transaction(function () use ($invoice, &$results) {
                    $freshInvoice = Invoice::lockForUpdate()->find($invoice->id);
                    
                    if ($freshInvoice->status !== 'overdue') {
                        $results['skipped']++;
                        $results['details'][] = [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'reason' => 'Status changed to ' . $freshInvoice->status
                        ];
                        return;
                    }

                    $daysOverdue = $freshInvoice->due_date->diffInDays(now());

                    // Log the follow-up reminder
                    $this->activityService->log(
                        $freshInvoice,
                        'follow_up_reminder',
                        [
                            'automated' => true,
                            'days_overdue' => $daysOverdue,
                            'client_email' => $freshInvoice->client?->email,
                            'reminder_type' => 'follow_up',
                            'urgency_level' => $this->getUrgencyLevel($daysOverdue),
                            'processed_at' => now()->toISOString()
                        ]
                    );

                    $results['sent']++;
                    $results['details'][] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_name' => $invoice->client?->name,
                        'days_overdue' => $daysOverdue,
                        'amount' => $freshInvoice->total,
                        'urgency_level' => $this->getUrgencyLevel($daysOverdue)
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
     * Get urgency level based on days overdue
     */
    private function getUrgencyLevel(int $daysOverdue): string
    {
        return match(true) {
            $daysOverdue > 90 => 'critical',
            $daysOverdue > 60 => 'high',
            $daysOverdue > 30 => 'medium',
            default => 'low'
        };
    }

    /**
     * Get reminder summary statistics
     */
    public function getReminderSummary(): array
    {
        $now = now();
        
        return [
            'due_soon_count' => Invoice::where('status', 'sent')
                ->where('due_date', '>=', $now)
                ->where('due_date', '<=', $now->addDays(7))
                ->count(),
            'overdue_count' => Invoice::where('status', 'overdue')->count(),
            'long_overdue_count' => Invoice::where('status', 'overdue')
                ->where('due_date', '<', $now->subDays(30))
                ->count(),
            'recent_reminders' => InvoiceActivity::whereIn('action', [
                'due_soon_reminder', 
                'overdue_reminder', 
                'follow_up_reminder'
            ])
                ->where('created_at', '>=', $now->subDays(7))
                ->count(),
            'upcoming_reminders' => $this->getUpcomingReminders()
        ];
    }

    /**
     * Get upcoming reminders for the next 30 days
     */
    private function getUpcomingReminders(): array
    {
        $upcoming = Invoice::where('status', 'sent')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addDays(30))
            ->with(['client'])
            ->get()
            ->map(fn($inv) => [
                'invoice_id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'client_name' => $inv->client?->name,
                'amount' => $inv->total,
                'due_date' => $inv->due_date->toDateString(),
                'days_until_due' => now()->diffInDays($inv->due_date),
                'reminder_date' => $inv->due_date->copy()->subDays(7)->toDateString()
            ]);

        return $upcoming->toArray();
    }

    /**
     * Check if reminder should be sent (prevents duplicates)
     */
    private function shouldSendReminder(Invoice $invoice, string $reminderType, int $cooldownDays = 7): bool
    {
        return !$invoice->activities()
            ->where('action', $reminderType)
            ->where('created_at', '>=', now()->subDays($cooldownDays))
            ->exists();
    }
}
