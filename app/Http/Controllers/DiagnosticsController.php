<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\Request;

class DiagnosticsController extends Controller
{
    /**
     * Show diagnostics page
     */
    public function index()
    {
        $paypalConfig = [
            'client_id' => Setting::get('paypal_client_id', config('services.paypal.client_id')),
            'client_secret' => Setting::get('paypal_client_secret', config('services.paypal.client_secret')),
            'mode' => Setting::get('paypal_mode', config('services.paypal.mode', 'sandbox')),
        ];
        
        $diagnostics = [
            'paypal_configured' => !empty($paypalConfig['client_id']) && !empty($paypalConfig['client_secret']),
            'client_id_set' => !empty($paypalConfig['client_id']),
            'client_secret_set' => !empty($paypalConfig['client_secret']),
            'mode' => $paypalConfig['mode'],
            'client_id_preview' => !empty($paypalConfig['client_id']) ? substr($paypalConfig['client_id'], 0, 10) . '...' : 'No configurado',
            'php_version' => PHP_VERSION,
            'curl_enabled' => function_exists('curl_version'),
            'curl_version' => function_exists('curl_version') ? curl_version()['version'] : 'N/A',
            'app_url' => config('app.url'),
            'app_name' => config('app.name'),
            'logs_path' => storage_path('logs'),
            'sample_invoice' => Invoice::with(['client', 'items'])->first(),
        ];
        
        return view('diagnostics.index', compact('diagnostics', 'paypalConfig'));
    }
    
    /**
     * Test PayPal order creation
     */
    public function testOrderCreation(Request $request)
    {
        try {
            $amount = $request->input('amount', 10.00);
            $currency = $request->input('currency', 'USD');
            
            $paypalConfig = [
                'client_id' => Setting::get('paypal_client_id', config('services.paypal.client_id')),
                'client_secret' => Setting::get('paypal_client_secret', config('services.paypal.client_secret')),
                'mode' => Setting::get('paypal_mode', config('services.paypal.mode', 'sandbox')),
            ];
            
            if (empty($paypalConfig['client_id']) || empty($paypalConfig['client_secret'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales de PayPal no configuradas'
                ], 400);
            }
            
            // Get access token
            $baseUrl = $paypalConfig['mode'] === 'live' 
                ? 'https://api-m.paypal.com' 
                : 'https://api-m.sandbox.paypal.com';
            
            $ch = curl_init("{$baseUrl}/v1/oauth2/token");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => "{$paypalConfig['client_id']}:{$paypalConfig['client_secret']}",
                CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Accept-Language: en_US',
                ],
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return response()->json([
                    'success' => false,
                    'step' => 'auth',
                    'message' => 'Error cURL: ' . $curlError
                ], 500);
            }
            
            if ($httpCode !== 200) {
                return response()->json([
                    'success' => false,
                    'step' => 'auth',
                    'message' => 'Error de autenticación (código ' . $httpCode . ')',
                    'response' => $response
                ], 400);
            }
            
            $authData = json_decode($response, true);
            $accessToken = $authData['access_token'];
            
            // Create test order
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => 'TEST-' . time(),
                    'description' => 'Orden de prueba',
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', '')
                    ]
                ]],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'locale' => 'es-DO',
                    'landing_page' => 'NO_PREFERENCE',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW'
                ]
            ];
            
            $ch = curl_init("{$baseUrl}/v2/checkout/orders");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($orderData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    "Authorization: Bearer {$accessToken}",
                ],
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return response()->json([
                    'success' => false,
                    'step' => 'create_order',
                    'message' => 'Error cURL: ' . $curlError
                ], 500);
            }
            
            $orderResponse = json_decode($response, true);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Orden de prueba creada exitosamente',
                    'order_id' => $orderResponse['id'],
                    'status' => $orderResponse['status'],
                    'amount' => $amount,
                    'currency' => $currency
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'step' => 'create_order',
                    'message' => 'Error al crear orden (código ' . $httpCode . ')',
                    'response' => $orderResponse,
                    'request_data' => $orderData
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Excepción: ' . $e->getMessage()
            ], 500);
        }
    }
}
