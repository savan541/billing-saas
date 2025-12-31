<?php

namespace App\Models;

use App\Events\RecurringInvoiceGenerated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecurringInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'amount',
        'frequency',
        'start_date',
        'next_run_date',
        'last_run_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'next_run_date' => 'date',
        'last_run_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function pause(): void
    {
        $this->status = 'paused';
        $this->save();
    }

    public function resume(): void
    {
        $this->status = 'active';
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'active' => 'green',
            'paused' => 'yellow',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getFormattedAmount(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    public function getFrequencyLabel(): string
    {
        return match($this->frequency) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            default => ucfirst($this->frequency),
        };
    }

    public function calculateNextRunDate(): \Carbon\Carbon
    {
        $nextRun = $this->last_run_date ? $this->last_run_date : $this->start_date;
        
        return match($this->frequency) {
            'monthly' => $nextRun->addMonth(),
            'quarterly' => $nextRun->addMonths(3),
            'yearly' => $nextRun->addYear(),
            default => $nextRun->addMonth(),
        };
    }

    public function shouldGenerateInvoice(): bool
    {
        return $this->isActive() 
            && $this->next_run_date->isToday() 
            || $this->next_run_date->isPast();
    }

    public function generateInvoice(): Invoice
    {
        $invoiceData = [
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'status' => 'sent',
            'subtotal' => $this->amount,
            'tax' => 0,
            'discount' => 0,
            'total' => $this->amount,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'notes' => $this->notes ? "Generated from recurring invoice: {$this->title}\n\n{$this->notes}" : "Generated from recurring invoice: {$this->title}",
        ];

        $invoice = Invoice::create($invoiceData);

        $this->last_run_date = now();
        $this->next_run_date = $this->calculateNextRunDate();
        $this->save();

        RecurringInvoiceGenerated::dispatch($invoice, $this);

        return $invoice;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_date', '<=', now());
    }
}
