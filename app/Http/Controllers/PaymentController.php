<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\CurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Show the invoice search page
     */
    public function searchPage()
    {
        return view('payment.search');
    }
    
    /**
     * Search for an invoice by number
     */
    public function searchInvoice(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string'
        ]);
        
        $invoiceNumber = strtoupper(trim($request->input('invoice_number')));
        
        $invoice = Invoice::with(['client', 'items'])->where('invoice_number', '=', $invoiceNumber)->first();
        
        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna factura con el número: ' . $invoiceNumber
            ], 404);
        }
        
        // Check if invoice can be paid
        if (in_array($invoice->status, ['cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Esta factura ha sido cancelada y no puede ser pagada'
            ], 400);
        }
        
        if ($invoice->status === 'paid' && $invoice->getRemainingBalance() <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Esta factura ya está completamente pagada'
            ], 400);
        }
        
        // Generate payment token if not exists or expired
        if (!$invoice->isPaymentTokenValid()) {
            $invoice->generatePaymentToken();
        }
        
        return response()->json([
            'success' => true,
            'invoice' => [
                'number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'client' => [
                    'name' => $invoice->client->name ?? 'N/A',
                    'email' => $invoice->client->email ?? null,
                ],
                'items' => $invoice->items->map(function($item) {
                    return [
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'total' => $item->amount
                    ];
                }),
                'subtotal' => $invoice->subtotal,
                'tax_rate' => $invoice->tax_rate,
                'tax_amount' => $invoice->tax_amount,
                'total' => $invoice->total,
                'amount_paid' => $invoice->amount_paid,
                'remaining' => $invoice->getRemainingBalance(),
                'currency' => $invoice->currency
            ],
            'payment_url' => $invoice->getPaymentUrl()
        ]);
    }
    
    /**
     * Show the payment page for a specific invoice
     */
    public function show($token)
    {
        $invoice = Invoice::where('payment_token', '=', $token)->firstOrFail();
        
        if (!$invoice->isPaymentTokenValid()) {
            return view('payment.expired', ['invoice' => $invoice]);
        }
        
        $invoice->load(['client', 'items']);
        
        $paypalConfig = $this->getPayPalConfig();
        
        // Check if currency conversion is needed
        $paypalCurrency = CurrencyConverter::getPayPalCurrency($invoice->currency);
        $currencyConversion = null;
        $finalCurrency = $invoice->currency; // Default to original currency
        
        if ($paypalCurrency['needs_conversion']) {
            $amount = $invoice->getRemainingBalance();
            $currencyConversion = CurrencyConverter::convert(
                $amount,
                $invoice->currency,
                $paypalCurrency['target_currency']
            );
            $finalCurrency = $currencyConversion['converted_currency']; // Use converted currency for SDK
        }
        
        return view('payment.show', [
            'invoice' => $invoice,
            'paypalClientId' => $paypalConfig['client_id'] ?? null,
            'paypalConfigured' => !empty($paypalConfig['client_id']) && !empty($paypalConfig['client_secret']),
            'currencyConversion' => $currencyConversion,
            'paypalCurrency' => $finalCurrency, // Currency for PayPal SDK
        ]);
    }
    
    /**
     * Create PayPal order
     */
    public function createOrder(Request $request, $token)
    {
        $invoice = Invoice::where('payment_token', '=', $token)->firstOrFail();
        
        if (!$invoice->isPaymentTokenValid()) {
            return response()->json(['error' => 'Token de pago inválido o expirado'], 400);
        }
        
        $amount = $invoice->getRemainingBalance();
        
        if ($amount <= 0) {
            return response()->json(['error' => 'Esta factura ya está pagada'], 400);
        }
        
        try {
            Log::info('Creating PayPal order', [
                'invoice' => $invoice->invoice_number,
                'amount' => $amount,
                'currency' => $invoice->currency
            ]);
            
            // Check if currency conversion is needed
            $paypalCurrency = CurrencyConverter::getPayPalCurrency($invoice->currency);
            $finalAmount = $amount;
            $finalCurrency = $invoice->currency;
            $conversionInfo = null;
            
            if ($paypalCurrency['needs_conversion']) {
                $conversion = CurrencyConverter::convert(
                    $amount, 
                    $invoice->currency, 
                    $paypalCurrency['target_currency']
                );
                
                $finalAmount = $conversion['converted_amount'];
                $finalCurrency = $conversion['converted_currency'];
                $conversionInfo = $conversion;
                
                Log::info('Currency conversion applied', [
                    'original' => $amount . ' ' . $invoice->currency,
                    'converted' => $finalAmount . ' ' . $finalCurrency,
                    'rate' => $conversion['exchange_rate']
                ]);
            }
            
            $accessToken = $this->getPayPalAccessToken();
            
            $description = "Factura #{$invoice->invoice_number}";
            if ($conversionInfo) {
                $description .= " ({$conversionInfo['original_amount']} {$conversionInfo['original_currency']} ≈ {$finalAmount} {$finalCurrency})";
            }
            
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $invoice->invoice_number,
                    'description' => $description,
                    'amount' => [
                        'currency_code' => $finalCurrency,
                        'value' => number_format($finalAmount, 2, '.', '')
                    ],
                    'custom_id' => json_encode([
                        'invoice_id' => $invoice->id,
                        'original_currency' => $invoice->currency,
                        'original_amount' => $amount,
                        'conversion_rate' => $conversionInfo['exchange_rate'] ?? 1.0
                    ])
                ]],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'locale' => 'es-DO',
                    'landing_page' => 'NO_PREFERENCE',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('payment.show', $token),
                    'cancel_url' => route('payment.show', $token)
                ]
            ];
            
            Log::info('PayPal order data', ['data' => $orderData]);
            
            $response = $this->makePayPalRequest('POST', '/v2/checkout/orders', $accessToken, $orderData);
            
            Log::info('PayPal order created successfully', ['order_id' => $response['id']]);
            
            return response()->json(['id' => $response['id']]);
            
        } catch (\Exception $e) {
            Log::error('Error creating PayPal order', [
                'message' => $e->getMessage(),
                'invoice' => $invoice->invoice_number,
                'amount' => $amount,
                'currency' => $invoice->currency
            ]);
            
            // Parse PayPal error if available
            $errorMessage = 'Error al crear la orden de pago';
            if (strpos($e->getMessage(), 'CURRENCY_NOT_SUPPORTED') !== false) {
                $errorMessage = "La moneda {$invoice->currency} no está soportada por PayPal. Use USD, EUR, etc.";
            } elseif (strpos($e->getMessage(), 'INVALID_REQUEST') !== false) {
                $errorMessage = 'Los datos de la orden son inválidos. Contacte al administrador.';
            } elseif (preg_match('/\{.*\}/', $e->getMessage(), $matches)) {
                // Try to extract PayPal error details
                $errorData = json_decode($matches[0], true);
                if (isset($errorData['details'][0]['description'])) {
                    $errorMessage .= ': ' . $errorData['details'][0]['description'];
                }
            }
            
            return response()->json(['error' => $errorMessage], 500);
        }
    }
    
    /**
     * Capture PayPal order and record payment
     */
    public function captureOrder(Request $request, $token)
    {
        $invoice = Invoice::where('payment_token', '=', $token)->firstOrFail();
        
        if (!$invoice->isPaymentTokenValid()) {
            return response()->json(['error' => 'Token de pago inválido o expirado'], 400);
        }
        
        $orderId = $request->input('orderID');
        
        try {
            $accessToken = $this->getPayPalAccessToken();
            
            $response = $this->makePayPalRequest('POST', "/v2/checkout/orders/{$orderId}/capture", $accessToken);
            
            if ($response['status'] === 'COMPLETED') {
                $capture = $response['purchase_units'][0]['payments']['captures'][0];
                $capturedAmount = (float) $capture['amount']['value'];
                $capturedCurrency = $capture['amount']['currency_code'];
                
                Log::info('PayPal capture response details', [
                    'captured_amount' => $capturedAmount,
                    'captured_currency' => $capturedCurrency,
                    'purchase_unit' => $response['purchase_units'][0]
                ]);
                
                // Get original amount from custom_id (for currency conversion)
                $customId = $response['purchase_units'][0]['custom_id'] ?? null;
                $originalAmount = $capturedAmount;
                $originalCurrency = $capturedCurrency;
                $conversionRate = 1.0;
                $paymentNote = 'Pago procesado automáticamente vía PayPal';
                
                Log::info('Custom ID extraction', [
                    'custom_id_found' => $customId !== null,
                    'custom_id_value' => $customId
                ]);
                
                if ($customId) {
                    $customData = json_decode($customId, true);
                    if ($customData && isset($customData['original_amount'])) {
                        $originalAmount = (float) $customData['original_amount'];
                        $originalCurrency = $customData['original_currency'] ?? $capturedCurrency;
                        $conversionRate = $customData['conversion_rate'] ?? 1.0;
                        
                        if ($originalCurrency !== $capturedCurrency) {
                            $paymentNote = sprintf(
                                'Pago procesado vía PayPal. Monto capturado: %s %s (convertido desde %s %s, tasa: %s)',
                                number_format($capturedAmount, 2),
                                $capturedCurrency,
                                number_format($originalAmount, 2),
                                $originalCurrency,
                                $conversionRate
                            );
                        }
                        
                        Log::info('PayPal payment captured with conversion', [
                            'captured' => $capturedAmount . ' ' . $capturedCurrency,
                            'original' => $originalAmount . ' ' . $originalCurrency,
                            'rate' => $conversionRate
                        ]);
                    }
                }
                
                DB::beginTransaction();
                try {
                    // Create payment record with ORIGINAL amount
                    $payment = Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $originalAmount, // Use original amount, not captured
                        'payment_method' => 'paypal',
                        'payment_date' => now(),
                        'reference' => $capture['id'],
                        'notes' => $paymentNote
                    ]);
                    
                    Log::info('Payment created in database', [
                        'payment_id' => $payment->id,
                        'amount_saved' => $payment->amount,
                        'original_amount' => $originalAmount,
                        'captured_amount' => $capturedAmount,
                        'invoice_currency' => $invoice->currency
                    ]);
                    
                    // Update invoice with ORIGINAL amount
                    $invoice->amount_paid += $originalAmount;
                    
                    if ($invoice->amount_paid >= $invoice->total) {
                        $invoice->status = 'paid';
                        $invoice->paid_at = now();
                    } else {
                        $invoice->status = 'partial';
                    }
                    
                    $invoice->save();
                    
                    DB::commit();
                    
                    return response()->json([
                        'success' => true,
                        'payment_id' => $payment->id,
                        'invoice_status' => $invoice->status
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
            
            return response()->json(['error' => 'El pago no pudo ser completado'], 400);
            
        } catch (\Exception $e) {
            Log::error('Error capturing PayPal order: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar el pago'], 500);
        }
    }
    
    /**
     * Get PayPal configuration from DB or .env
     */
    private function getPayPalConfig()
    {
        // Try to get from database first
        $clientId = Setting::get('paypal_client_id', config('services.paypal.client_id'));
        $clientSecret = Setting::get('paypal_client_secret', config('services.paypal.client_secret'));
        $mode = Setting::get('paypal_mode', config('services.paypal.mode', 'sandbox'));
        
        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'mode' => $mode,
        ];
    }
    
    /**
     * Get PayPal access token
     */
    private function getPayPalAccessToken()
    {
        $config = $this->getPayPalConfig();
        $clientId = $config['client_id'];
        $clientSecret = $config['client_secret'];
        $mode = $config['mode'];
        
        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('Las credenciales de PayPal no están configuradas');
        }
        
        $baseUrl = $mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
        
        $ch = curl_init("{$baseUrl}/v1/oauth2/token");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "{$clientId}:{$clientSecret}",
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
            Log::error('cURL error getting PayPal token', ['error' => $curlError]);
            throw new \Exception('Error de conexión con PayPal: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            Log::error('PayPal auth failed', ['code' => $httpCode, 'response' => $response]);
            throw new \Exception('Error al autenticar con PayPal (código ' . $httpCode . '): ' . $response);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new \Exception('No se recibió token de acceso de PayPal');
        }
        
        return $data['access_token'];
    }
    
    /**
     * Make a PayPal API request
     */
    private function makePayPalRequest($method, $endpoint, $accessToken, $data = null)
    {
        $config = $this->getPayPalConfig();
        $mode = $config['mode'];
        $baseUrl = $mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
        
        $ch = curl_init("{$baseUrl}{$endpoint}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$accessToken}",
            ],
        ]);
        
        if ($data) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            Log::debug('PayPal request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $jsonData
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            Log::error('cURL error in PayPal request', ['error' => $curlError]);
            throw new \Exception('Error de conexión: ' . $curlError);
        }
        
        Log::debug('PayPal response', [
            'code' => $httpCode,
            'response' => $response
        ]);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            Log::error('PayPal API error', [
                'code' => $httpCode,
                'response' => $response,
                'endpoint' => $endpoint
            ]);
            throw new \Exception('PayPal API Error [' . $httpCode . ']: ' . $response);
        }
        
        return json_decode($response, true);
    }
}
