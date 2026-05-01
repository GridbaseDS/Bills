<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
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
        
        $invoice = Invoice::where('invoice_number', '=', $invoiceNumber)->first();
        
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
            'payment_url' => $invoice->getPaymentUrl(),
            'invoice_number' => $invoice->invoice_number,
            'total' => $invoice->total,
            'remaining' => $invoice->getRemainingBalance()
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
        
        return view('payment.show', [
            'invoice' => $invoice,
            'paypalClientId' => config('services.paypal.client_id'),
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
            $accessToken = $this->getPayPalAccessToken();
            
            $response = $this->makePayPalRequest('POST', '/v2/checkout/orders', $accessToken, [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $invoice->invoice_number,
                    'description' => "Factura #{$invoice->invoice_number}",
                    'amount' => [
                        'currency_code' => $invoice->currency,
                        'value' => number_format($amount, 2, '.', '')
                    ]
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
            ]);
            
            return response()->json(['id' => $response['id']]);
            
        } catch (\Exception $e) {
            Log::error('Error creating PayPal order: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear la orden de pago'], 500);
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
                $amount = (float) $capture['amount']['value'];
                
                DB::beginTransaction();
                try {
                    // Create payment record
                    $payment = Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_method' => 'paypal',
                        'payment_date' => now(),
                        'reference' => $capture['id'],
                        'notes' => 'Pago procesado automáticamente vía PayPal'
                    ]);
                    
                    // Update invoice
                    $invoice->amount_paid += $amount;
                    
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
     * Get PayPal access token
     */
    private function getPayPalAccessToken()
    {
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');
        $mode = config('services.paypal.mode', 'sandbox');
        
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
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Error al obtener token de PayPal');
        }
        
        $data = json_decode($response, true);
        return $data['access_token'];
    }
    
    /**
     * Make a PayPal API request
     */
    private function makePayPalRequest($method, $endpoint, $accessToken, $data = null)
    {
        $mode = config('services.paypal.mode', 'sandbox');
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
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception('Error en petición a PayPal: ' . $response);
        }
        
        return json_decode($response, true);
    }
}
