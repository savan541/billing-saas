<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    public function canBeModified(): bool
    {
        return !$this->isPaid();
    }

    public function getCanBeModifiedAttribute(): bool
    {
        return $this->canBeModified();
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'paid' => 'green',
            'overdue' => 'red',
            default => 'gray',
        };
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

    public function getFormattedTotalPaid(): string
    {
        return '$' . number_format($this->getTotalPaid(), 2);
    }

    public function getFormattedRemainingBalance(): string
    {
        return '$' . number_format($this->getRemainingBalance(), 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->getTotalPaid() >= $this->total;
    }

    public function updatePaymentStatus(): void
    {
        if ($this->isFullyPaid() && !$this->isPaid()) {
            $this->status = 'paid';
            $this->paid_at = now();
            $this->save();
        }
    }

    public function canAcceptPayment(float $amount): bool
    {
        return ($this->getTotalPaid() + $amount) <= $this->total;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $invoice->generateInvoiceNumber();
            }
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
