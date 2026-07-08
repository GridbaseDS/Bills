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
     * Fetch live exchange rates with 12 hours caching
     */
    public static function fetchLiveRates(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('exchange_rates_usd', 43200, function () {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get('https://open.er-api.com/v6/latest/USD');
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['rates'])) {
                        return $data['rates'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error fetching live currency rates: ' . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Fetch live exchange rates from Banco Popular Dominicano (BPD) Sandbox API
     */
    public static function fetchBpdRates(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('exchange_rates_bpd', 43200, function () {
            try {
                $clientId = '35843da05b1107b15c85b641b5806748';
                $clientSecret = '9f3d924419ef4b8815982146bed4e5a2';
                
                // Get dynamic BPD credentials if defined in settings
                $settingClientId = Setting::get('bpd_client_id');
                $settingClientSecret = Setting::get('bpd_client_secret');
                if (!empty($settingClientId)) {
                    $clientId = $settingClientId;
                }
                if (!empty($settingClientSecret)) {
                    $clientSecret = $settingClientSecret;
                }

                // 1. Get OAuth2 Token with caching of 50 minutes (3000s) to avoid requesting too often
                $accessToken = \Illuminate\Support\Facades\Cache::remember('bpd_oauth_token_' . md5($clientId . $clientSecret), 3000, function () use ($clientId, $clientSecret) {
                    $authResponse = \Illuminate\Support\Facades\Http::asForm()
                        ->withHeaders([
                            'X-IBM-Client-Id' => $clientId
                        ])
                        ->post('https://api.us-east-a.apiconnect.ibmappdomain.cloud/apiportalpopular/bpdsandbox/bpd/Authentication/oauth2/token', [
                            'grant_type' => 'client_credentials',
                            'client_id' => $clientId,
                            'client_secret' => $clientSecret,
                            'scope' => 'scope_1'
                        ]);

                    if ($authResponse->successful()) {
                        $authData = $authResponse->json();
                        return $authData['access_token'] ?? null;
                    }

                    Log::error('BPD OAuth2 token retrieval failed: ' . $authResponse->body());
                    return null;
                });

                if (empty($accessToken)) {
                    throw new \Exception("Could not obtain BPD access token.");
                }

                // 2. Fetch rates
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'X-IBM-Client-Id' => $clientId,
                        'accept' => 'application/json'
                    ])
                    ->get('https://api.us-east-a.apiconnect.ibmappdomain.cloud/apiportalpopular/bpdsandbox/consultatasa/consultaTasa');

                if ($response->successful()) {
                    $data = $response->json();
                    $monedasList = $data['monedas']['moneda'] ?? [];
                    $rates = [];
                    foreach ($monedasList as $moneda) {
                        $currencyName = strtoupper($moneda['descripcion'] ?? '');
                        $sellRate = (float)($moneda['venta'] ?? 0);
                        if (!empty($currencyName) && $sellRate > 0) {
                            $rates[$currencyName] = $sellRate;
                        }
                    }
                    if (!empty($rates)) {
                        \Illuminate\Support\Facades\Cache::put('exchange_rates_bpd_time', now()->toDateTimeString(), 43200);
                        return $rates;
                    }
                } else {
                    Log::error('BPD exchange rates retrieval failed: ' . $response->body());
                }
            } catch (\Exception $e) {
                Log::error('Error fetching BPD exchange rates: ' . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Get the conversion rate from live API (cached), database settings, or hardcoded default
     */
    public static function getConversionRate(string $fromCurrency, string $toCurrency): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);
        
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        
        // 1. Attempt to fetch BPD rates first for USD, EUR and DOP conversions
        if (in_array($fromCurrency, ['USD', 'EUR', 'DOP']) && in_array($toCurrency, ['USD', 'EUR', 'DOP'])) {
            try {
                $bpdRates = self::fetchBpdRates();
                if (!empty($bpdRates)) {
                    $rates = [
                        'DOP' => 1.0,
                        'USD' => (float)($bpdRates['USD'] ?? 60.35),
                        'EUR' => (float)($bpdRates['EUR'] ?? 65.90),
                    ];
                    
                    if (isset($rates[$fromCurrency]) && isset($rates[$toCurrency])) {
                        return $rates[$fromCurrency] / $rates[$toCurrency];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error using BPD rates, falling back: ' . $e->getMessage());
            }
        }
        
        // Attempt to fetch live rates first
        $liveRates = self::fetchLiveRates();
        
        if (!empty($liveRates)) {
            // Case 1: Convert USD to anything
            if ($fromCurrency === 'USD' && isset($liveRates[$toCurrency])) {
                return (float) $liveRates[$toCurrency];
            }
            // Case 2: Convert anything to USD
            if ($toCurrency === 'USD' && isset($liveRates[$fromCurrency])) {
                return 1.0 / (float) $liveRates[$fromCurrency];
            }
            // Case 3: Convert anything to anything (e.g. DOP to EUR, or EUR to DOP)
            if (isset($liveRates[$fromCurrency]) && isset($liveRates[$toCurrency])) {
                return (float) $liveRates[$toCurrency] / (float) $liveRates[$fromCurrency];
            }
        }
        
        // Fall back to old static database settings / fallbacks
        $key = $fromCurrency . '_TO_' . $toCurrency;
        $rate = Setting::get('currency_rate_' . strtolower($key));
        
        if ($rate && is_numeric($rate)) {
            return (float) $rate;
        }
        
        // If converting DOP to USD (or vice-versa)
        if ($key === 'DOP_TO_USD') {
            return self::DEFAULT_RATES['DOP_TO_USD'] ?? 0.017;
        }
        if ($key === 'USD_TO_DOP') {
            $dopToUsd = Setting::get('currency_rate_dop_to_usd') ?: 0.017;
            return 1.0 / (float)$dopToUsd;
        }
        
        // If converting EUR to DOP (fallback rate ~ 63.50)
        if ($key === 'EUR_TO_DOP') {
            return 63.50;
        }
        if ($key === 'DOP_TO_EUR') {
            return 1.0 / 63.50;
        }
        
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
