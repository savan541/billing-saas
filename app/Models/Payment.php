<?php

namespace App\Models;

use App\Events\PaymentRecorded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getFormattedAmount(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    public function getPaymentMethodLabel(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'upi' => 'UPI',
            'card' => 'Card',
            default => ucfirst($this->payment_method),
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($payment) {
            PaymentRecorded::dispatch($payment);
            
            // Update invoice timestamp to trigger PDF regeneration
            if ($payment->invoice) {
                $payment->invoice->touch();
            }
        });
    }
}
