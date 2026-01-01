<?php

namespace App\Models;

use App\Enums\Currency;
use App\Scopes\UserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'address',
        'tax_id',
        'tax_country',
        'tax_state',
        'tax_rate',
        'tax_exempt',
        'tax_exemption_reason',
        'currency',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
        'tax_exempt' => 'boolean',
        'currency' => 'string',
    ];

    protected $appends = ['currency_symbol', 'formatted_currency'];

    protected static function booted()
    {
        static::addGlobalScope(new UserScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getEffectiveTaxRate(): float
    {
        if ($this->tax_exempt) {
            return 0.0;
        }
        
        return $this->tax_rate ?? 0.0;
    }

    public function getFormattedTaxRate(): string
    {
        $rate = $this->getEffectiveTaxRate();
        return ($rate * 100) . '%';
    }

    public function getTaxLabel(): string
    {
        if ($this->tax_exempt) {
            return 'Tax Exempt';
        }
        
        $parts = [];
        if ($this->tax_country) {
            $parts[] = strtoupper($this->tax_country);
        }
        if ($this->tax_state) {
            $parts[] = $this->tax_state;
        }
        
        $location = implode(', ', $parts);
        $rate = $this->getFormattedTaxRate();
        
        return $location ? "{$location} ({$rate})" : $rate;
    }

    public function getCurrencySymbolAttribute(): string
    {
        $currency = $this->currency;
        if (is_string($currency)) {
            $currencyEnum = \App\Enums\Currency::tryFrom($currency);
            return $currencyEnum?->getSymbol() ?? '$';
        }
        return $this->currency?->getSymbol() ?? '$';
    }

    public function getFormattedCurrencyAttribute(): string
    {
        $currency = $this->currency;
        if (is_string($currency)) {
            $currencyEnum = \App\Enums\Currency::tryFrom($currency);
            return $currencyEnum?->getFormatted() ?? 'USD ($)';
        }
        return $this->currency?->getFormatted() ?? 'USD ($)';
    }
}
