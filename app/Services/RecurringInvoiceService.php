<?php

namespace App\Services;

use App\Models\RecurringInvoice;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceService
{
    public function createRecurringInvoice(array $data): RecurringInvoice
    {
        return DB::transaction(function () use ($data) {
            $recurringInvoice = RecurringInvoice::create($data);
            
            return $recurringInvoice;
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

    public function generateDueInvoices(): int
    {
        $count = 0;
        
        $dueRecurringInvoices = RecurringInvoice::active()
            ->due()
            ->get();

        foreach ($dueRecurringInvoices as $recurringInvoice) {
            if ($recurringInvoice->shouldGenerateInvoice()) {
                $recurringInvoice->generateInvoice();
                $count++;
            }
        }

        return $count;
    }
}
