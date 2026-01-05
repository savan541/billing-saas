<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class CurrencyExchangeRate extends Model
{
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'date'
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'date' => 'date',
    ];

    /**
     * Get the latest exchange rate for a currency pair
     */
    public static function getRate(string $fromCurrency, string $toCurrency, ?string $date = null): ?float
    {
        $query = static::where('base_currency', $fromCurrency)
            ->where('target_currency', $toCurrency);

        if ($date) {
            $query->where('date', '<=', $date);
        }

        $rate = $query->latest('date')
            ->value('rate');

        // If direct rate not found, try reverse rate
        if (!$rate) {
            $reverseRate = static::where('base_currency', $toCurrency)
                ->where('target_currency', $fromCurrency);

            if ($date) {
                $reverseRate->where('date', '<=', $date);
            }

            $reverseRate = $reverseRate->latest('date')
                ->value('rate');

            if ($reverseRate) {
                $rate = 1 / (float) $reverseRate;
            }
        }

        return $rate ? (float) $rate : null;
    }
    
    /**
     * Update or create an exchange rate for a currency pair
     */
    public static function updateOrCreateRate(string $baseCurrency, string $targetCurrency, float $rate, ?string $date = null): self
    {
        $date = $date ?: now()->toDateString();
        
        return static::updateOrCreate(
            [
                'base_currency' => strtoupper($baseCurrency),
                'target_currency' => strtoupper($targetCurrency),
                'date' => $date,
            ],
            ['rate' => $rate]
        );
    }
}
