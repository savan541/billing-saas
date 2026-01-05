<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Events\InvoiceCreated;
use App\Events\InvoicePaid;
use App\Services\InvoiceActivityService;
use App\Services\InvoicePdfService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'issue_date',
        'due_date',
        'notes',
        'paid_at',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'invoice_tax_rate',
        'tax_exempt_at_time',
        'currency',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'invoice_tax_rate' => 'decimal:4',
        'tax_exempt_at_time' => 'boolean',
        'currency' => Currency::class,
        'status' => InvoiceStatus::class,
    ];

    protected $appends = ['can_be_modified', 'total_paid', 'remaining_balance'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InvoiceActivity::class)->orderBy('created_at', 'desc');
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === InvoiceStatus::SENT;
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::PAID;
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::OVERDUE;
    }

    public function isCancelled(): bool
    {
        return $this->status === InvoiceStatus::CANCELLED;
    }

    public function canBeModified(): bool
    {
        return $this->status?->isEditable() ?? false;
    }

    public function getCanBeModifiedAttribute(): bool
    {
        return $this->canBeModified();
    }

    public function getStatusColor(): string
    {
        return $this->status?->getColor() ?? 'gray';
    }

    public function getFormattedTotal(): string
    {
        return '$' . number_format($this->total, 2);
    }

    public function getTotalPaid(): float
    {
        return $this->payments()->sum('amount');
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->getTotalPaid();
    }

    public function getRemainingBalance(): float
    {
        return max(0, $this->total - $this->getTotalPaid());
    }

    public function getRemainingBalanceAttribute(): float
    {
        return $this->getRemainingBalance();
    }

    public function getTaxRateAtTime(): float
    {
        return $this->invoice_tax_rate ?? 0.0;
    }

    public function getFormattedTaxRateAtTime(): string
    {
        $rate = $this->getTaxRateAtTime();
        return ($rate * 100) . '%';
    }

    public function wasTaxExemptAtTime(): bool
    {
        return $this->tax_exempt_at_time ?? false;
    }

    public function getTaxLabelAtTime(): string
    {
        if ($this->wasTaxExemptAtTime()) {
            return 'Tax Exempt';
        }
        
        $rate = $this->getFormattedTaxRateAtTime();
        return $rate === '0%' ? 'No Tax' : $rate;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currency?->getSymbol() ?? '$';
    }

    public function getFormattedCurrency(): string
    {
        return $this->currency?->getFormatted() ?? 'USD ($)';
    }

    public function formatAmount(float $amount): string
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($amount, 2);
    }

    public function getFormattedTotalPaid(): string
    {
        return $this->formatAmount($this->getTotalPaid());
    }

    public function getFormattedRemainingBalance(): string
    {
        return $this->formatAmount($this->getRemainingBalance());
    }

    public function isFullyPaid(): bool
    {
        return $this->getTotalPaid() >= $this->total;
    }

    public function updatePaymentStatus(): void
    {
        if ($this->isFullyPaid() && !$this->isPaid()) {
            $this->status = InvoiceStatus::PAID;
            $this->paid_at = now();
            $this->save();
        }
    }

    public function canAcceptPayment(float $amount): bool
    {
        return ($this->getTotalPaid() + $amount) <= $this->total;
    }

    public function canBeSent(): bool
    {
        return $this->status?->canBeSent() ?? false;
    }

    public function canBePaid(): bool
    {
        return $this->status?->canBePaid() ?? false;
    }

    public function canBeCancelled(): bool
    {
        return $this->status?->canBeCancelled() ?? false;
    }

    public function canBeMarkedAsPaid(): bool
    {
        return $this->status?->canBeMarkedAsPaid() ?? false;
    }

    public function markAsSent(): void
    {
        if ($this->canBeSent()) {
            $this->status = InvoiceStatus::SENT;
            $this->save();
        }
    }

    public function markAsPaid(): void
    {
        if ($this->canBeMarkedAsPaid() && $this->isFullyPaid()) {
            $this->status = InvoiceStatus::PAID;
            $this->paid_at = now();
            $this->save();
            
            // Fire payment event
            InvoicePaid::dispatch($this);
        }
    }

    public function cancel(): void
    {
        if ($this->canBeCancelled()) {
            $this->status = InvoiceStatus::CANCELLED;
            $this->save();
        }
    }

    public function checkOverdue(): void
    {
        if ($this->isSent() && $this->due_date && $this->due_date->isPast()) {
            $this->status = InvoiceStatus::OVERDUE;
            $this->save();
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $invoice->generateInvoiceNumber();
            }
        });

        static::created(function ($invoice) {
            app(InvoiceActivityService::class)->logCreated($invoice);
            
            if ($invoice->status !== InvoiceStatus::DRAFT) {
                InvoiceCreated::dispatch($invoice);
            }
        });

        static::updated(function ($invoice) {
            $activityService = app(InvoiceActivityService::class);
            
            if ($invoice->wasChanged('status')) {
                switch ($invoice->status) {
                    case InvoiceStatus::PAID:
                        $activityService->logPaid($invoice);
                        InvoicePaid::dispatch($invoice);
                        break;
                    case InvoiceStatus::SENT:
                        $activityService->logSent($invoice);
                        break;
                    case InvoiceStatus::CANCELLED:
                        $activityService->logCancelled($invoice);
                        break;
                }
            }
            
            if ($invoice->wasChanged(['subtotal', 'tax', 'discount', 'total', 'due_date'])) {
                $changes = $invoice->getChanges();
                $activityService->logUpdated($invoice, $changes);
            }
        });

        static::deleting(function ($invoice) {
            app(InvoiceActivityService::class)->logDeleted($invoice);
            
            $pdfService = app(InvoicePdfService::class);
            $pdfService->deletePdf($invoice);
        });
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $sequence = $this->where('user_id', $this->user_id)
            ->whereYear('created_at', $year)
            ->count() + 1;
        
        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }
}
