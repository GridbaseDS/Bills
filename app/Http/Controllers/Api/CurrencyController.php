<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyConverter;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Get the live cached currency exchange rates for DOP, USD, and EUR
     */
    public function getRates()
    {
        try {
            $rates = CurrencyConverter::fetchLiveRates();
            
            // Format rates nicely for the frontend
            $dopToUsd = CurrencyConverter::getConversionRate('DOP', 'USD');
            $usdToDop = CurrencyConverter::getConversionRate('USD', 'DOP');
            $eurToDop = CurrencyConverter::getConversionRate('EUR', 'DOP');
            
            return response()->json([
                'success' => true,
                'rates' => [
                    'USD_TO_DOP' => round($usdToDop, 4),
                    'EUR_TO_DOP' => round($eurToDop, 4),
                    'DOP_TO_USD' => round($dopToUsd, 6),
                ],
                'live_api_rates' => $rates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
