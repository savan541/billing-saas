<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_notifications_enabled',
        'email_notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_notifications_enabled' => 'boolean',
            'email_notification_preferences' => 'array',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    public function getEmailNotificationPreference(string $type): bool
    {
        if (!$this->email_notifications_enabled) {
            return false;
        }

        $preferences = $this->email_notification_preferences ?? [];
        
        return $preferences[$type] ?? true;
    }

    public function setEmailNotificationPreference(string $type, bool $enabled): void
    {
        $preferences = $this->email_notification_preferences ?? [];
        $preferences[$type] = $enabled;
        
        $this->email_notification_preferences = $preferences;
        $this->save();
    }

    public function getDefaultEmailNotificationPreferences(): array
    {
        return [
            'invoice_created' => true,
            'invoice_paid' => true,
            'payment_receipt' => true,
            'recurring_invoice_generated' => true,
        ];
    }
}
