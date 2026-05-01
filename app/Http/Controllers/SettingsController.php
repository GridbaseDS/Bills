<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $settings = [
            'paypal_client_id' => Setting::get('paypal_client_id', config('services.paypal.client_id')),
            'paypal_client_secret' => Setting::get('paypal_client_secret', config('services.paypal.client_secret')),
            'paypal_mode' => Setting::get('paypal_mode', config('services.paypal.mode', 'sandbox')),
        ];
        
        return view('settings.index', compact('settings'));
    }
    
    /**
     * Update PayPal settings
     */
    public function updatePayPal(Request $request)
    {
        $request->validate([
            'paypal_mode' => 'required|in:sandbox,live',
            'paypal_client_id' => 'nullable|string',
            'paypal_client_secret' => 'nullable|string',
        ]);
        
        Setting::set('paypal_mode', $request->paypal_mode, 'paypal');
        Setting::set('paypal_client_id', $request->paypal_client_id ?? '', 'paypal');
        Setting::set('paypal_client_secret', $request->paypal_client_secret ?? '', 'paypal');
        
        return redirect()->route('settings.index')->with('success', '✅ Configuración de PayPal actualizada correctamente');
    }
    
    /**
     * Test PayPal connection
     */
    public function testPayPalConnection(Request $request)
    {
        $clientId = $request->input('client_id') ?: Setting::get('paypal_client_id');
        $clientSecret = $request->input('client_secret') ?: Setting::get('paypal_client_secret');
        $mode = $request->input('mode') ?: Setting::get('paypal_mode', 'sandbox');
        
        if (empty($clientId) || empty($clientSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor, ingrese Client ID y Client Secret'
            ], 400);
        }
        
        try {
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
            
            if ($httpCode === 200) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Conexión exitosa con PayPal ' . ($mode === 'live' ? 'Producción' : 'Sandbox')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '❌ Error de autenticación. Verifica tus credenciales.'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Error al conectar con PayPal: ' . $e->getMessage()
            ], 500);
        }
    }
}
