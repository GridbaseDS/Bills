<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class CurrencyConverter
{
    /**
     * Supported PayPal currencies
     */
    private const PAYPAL_SUPPORTED_CURRENCIES = [
        'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 
        'MXN', 'BRL', 'SGD', 'HKD', 'NZD', 'SEK',
        'DKK', 'PLN', 'NOK', 'HUF', 'CZK', 'ILS',
        'PHP', 'TWD', 'THB', 'MYR', 'CHF'
    ];
    
    /**
     * Default conversion rates (fallback if not configured)
     */
    private const DEFAULT_RATES = [
        'DOP_TO_USD' => 0.017, // 1 DOP ≈ 0.017 USD (aprox. 58.5 DOP = 1 USD)
    ];
    
    /**
     * Check if a currency is supported by PayPal
     */
    public static function isPayPalSupported(string $currency): bool
    {
        return in_array(strtoupper($currency), self::PAYPAL_SUPPORTED_CURRENCIES);
    }
    
    /**
     * Get the conversion rate from settings or default
     */
    public static function getConversionRate(string $fromCurrency, string $toCurrency): float
    {
        $key = strtoupper($fromCurrency) . '_TO_' . strtoupper($toCurrency);
        
        // Try to get from settings first
        $rate = Setting::get('currency_rate_' . strtolower($key));
        
        if ($rate && is_numeric($rate)) {
            return (float) $rate;
        }
        
        // Fall back to default rates
        return self::DEFAULT_RATES[$key] ?? 0.0;
    }
    
    /**
     * Convert amount from one currency to another
     */
    public static function convert(float $amount, string $fromCurrency, string $toCurrency): array
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);
        
        // No conversion needed
        if ($fromCurrency === $toCurrency) {
            return [
                'original_amount' => $amount,
                'original_currency' => $fromCurrency,
                'converted_amount' => $amount,
                'converted_currency' => $toCurrency,
                'exchange_rate' => 1.0,
                'conversion_applied' => false
            ];
        }
        
        $rate = self::getConversionRate($fromCurrency, $toCurrency);
        
        if ($rate <= 0) {
            throw new \Exception("No se encontró tasa de conversión para {$fromCurrency} a {$toCurrency}");
        }
        
        $convertedAmount = round($amount * $rate, 2);
        
        Log::info('Currency conversion', [
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'original_amount' => $amount,
            'rate' => $rate,
            'converted_amount' => $convertedAmount
        ]);
        
        return [
            'original_amount' => $amount,
            'original_currency' => $fromCurrency,
            'converted_amount' => $convertedAmount,
            'converted_currency' => $toCurrency,
            'exchange_rate' => $rate,
            'conversion_applied' => true
        ];
    }
    
    /**
     * Get PayPal-compatible currency for a given currency
     * If not supported, returns USD as default
     */
    public static function getPayPalCurrency(string $currency): array
    {
        $currency = strtoupper($currency);
        
        if (self::isPayPalSupported($currency)) {
            return [
                'currency' => $currency,
                'needs_conversion' => false,
                'target_currency' => $currency
            ];
        }
        
        // Default conversion target is USD
        return [
            'currency' => $currency,
            'needs_conversion' => true,
            'target_currency' => 'USD'
        ];
    }
    
    /**
     * Format currency for display
     */
    public static function formatAmount(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'DOP' => 'RD$',
            'MXN' => 'MX$',
            'CAD' => 'CA$',
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        
        return $symbol . number_format($amount, 2, '.', ',');
    }
}
