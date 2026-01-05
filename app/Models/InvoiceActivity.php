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
            'marked_overdue' => 'Invoice automatically marked as overdue',
            'generated_from_recurring' => 'Invoice generated from recurring template',
            'due_soon_reminder' => 'Payment reminder sent (due soon)',
            'overdue_reminder' => 'Overdue payment reminder sent',
            'follow_up_reminder' => 'Follow-up reminder sent',
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
            'marked_overdue' => 'exclamation-triangle',
            'generated_from_recurring' => 'refresh',
            'due_soon_reminder' => 'bell',
            'overdue_reminder' => 'exclamation-circle',
            'follow_up_reminder' => 'flag',
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
            'marked_overdue' => 'red',
            'generated_from_recurring' => 'blue',
            'due_soon_reminder' => 'yellow',
            'overdue_reminder' => 'orange',
            'follow_up_reminder' => 'red',
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
