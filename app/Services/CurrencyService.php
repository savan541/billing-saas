<?php

namespace App\Services;

use App\Models\CurrencyExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CurrencyService
{
    protected string $baseCurrency;
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'INR'];

    public function __construct(string $baseCurrency = 'USD')
    {
        $this->baseCurrency = strtoupper($baseCurrency);
    }

    /**
     * Convert amount from one currency to another
     */
    /**
     * Convert amount from one currency to another
     * 
     * @param float $amount Amount to convert
     * @param string|\App\Enums\Currency $fromCurrency Source currency code or enum
     * @param string|\App\Enums\Currency $toCurrency Target currency code or enum
     * @param string|null $date Optional date for historical rates
     * @return float Converted amount
     */
    public function convert(float $amount, $fromCurrency, $toCurrency, ?string $date = null): float
    {
        // Convert enums to strings if needed
        $fromCurrency = is_object($fromCurrency) ? $fromCurrency->value : strtoupper($fromCurrency);
        $toCurrency = is_object($toCurrency) ? $toCurrency->value : strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency, $date);
        
        return round($amount * $rate, 2);
    }

    /**
     * Get exchange rate between two currencies
     */
    /**
     * Get exchange rate between two currencies
     * 
     * @param string|\App\Enums\Currency $fromCurrency Source currency code or enum
     * @param string|\App\Enums\Currency $toCurrency Target currency code or enum
     * @param string|null $date Optional date for historical rates
     * @return float Exchange rate
     */
    public function getExchangeRate($fromCurrency, $toCurrency, ?string $date = null): float
    {
        // Convert enums to strings if needed
        $fromCurrency = is_object($fromCurrency) ? $fromCurrency->value : strtoupper($fromCurrency);
        $toCurrency = is_object($toCurrency) ? $toCurrency->value : strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}_" . ($date ?? 'latest');
        
        return Cache::remember($cacheKey, now()->addDay(), function () use ($fromCurrency, $toCurrency, $date) {
            // First try to get rate from database
            $rate = CurrencyExchangeRate::getRate($fromCurrency, $toCurrency, $date);

            // If not found in database, try to fetch from API
            if (!$rate) {
                $rate = $this->fetchExchangeRate($fromCurrency, $toCurrency);
                
                // Save to database for future use
                if ($rate) {
                    CurrencyExchangeRate::updateOrCreateRate(
                        $fromCurrency,
                        $toCurrency,
                        $rate,
                        now()->toDateString()
                    );
                }
            }

            return $rate ?? 1.0;
        });
    }

    /**
     * Fetch exchange rate from external API
     */
    protected function fetchExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        try {
            // In a real app, you would use a proper exchange rate API
            // This is a simplified example
            $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$fromCurrency}");
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['rates'][$toCurrency] ?? null;
            }
            
            return null;
        } catch (\Exception $e) {
            // Log error or handle it appropriately
            return null;
        }
    }

    /**
     * Format amount with currency symbol
     */
    public function formatAmount(float $amount, string $currency, bool $withSymbol = true): string
    {
        $currency = strtoupper($currency);
        $symbol = $this->getCurrencySymbol($currency);
        $formatted = number_format($amount, 2);
        
        return $withSymbol ? "{$symbol}{$formatted}" : $formatted;
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'INR' => '₹',
        ];

        return $symbols[strtoupper($currency)] ?? strtoupper($currency);
    }

    /**
     * Get all supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    /**
     * Check if currency is supported
     */
    public function isSupportedCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->supportedCurrencies);
    }
}
