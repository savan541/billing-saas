<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getDescription(): string
    {
        return match($this->action) {
            'created' => 'Invoice created',
            'sent' => 'Invoice sent to client',
            'paid' => 'Invoice marked as paid',
            'payment_received' => 'Payment received',
            'pdf_generated' => 'PDF generated',
            'cancelled' => 'Invoice cancelled',
            'updated' => 'Invoice updated',
            'deleted' => 'Invoice deleted',
            default => ucfirst($this->action),
        };
    }

    public function getIcon(): string
    {
        return match($this->action) {
            'created' => 'plus-circle',
            'sent' => 'paper-airplane',
            'paid' => 'check-circle',
            'payment_received' => 'banknotes',
            'pdf_generated' => 'document-text',
            'cancelled' => 'x-circle',
            'updated' => 'pencil',
            'deleted' => 'trash',
            default => 'information-circle',
        };
    }

    public function getColor(): string
    {
        return match($this->action) {
            'created' => 'blue',
            'sent' => 'green',
            'paid' => 'green',
            'payment_received' => 'green',
            'pdf_generated' => 'gray',
            'cancelled' => 'red',
            'updated' => 'yellow',
            'deleted' => 'red',
            default => 'gray',
        };
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
