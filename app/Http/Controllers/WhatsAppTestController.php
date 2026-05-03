<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppTestController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Show the WhatsApp test page
     */
    public function index()
    {
        $isEnabled = $this->whatsappService->isEnabled();
        
        $config = [
            'enabled' => $isEnabled,
            'phone_id' => env('WHATSAPP_PHONE_ID', 'No configurado'),
            'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', 'No configurado'),
            'has_token' => !empty(env('WHATSAPP_ACCESS_TOKEN'))
        ];

        return view('whatsapp-test', compact('config'));
    }

    /**
     * Send a test text message
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:4096'
        ]);

        try {
            $result = $this->whatsappService->sendTextMessage(
                $request->phone,
                $request->message
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mensaje enviado exitosamente',
                    'data' => $result['data'] ?? null
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Error al enviar mensaje'
            ], 400);

        } catch (\Exception $e) {
            Log::error('WhatsApp test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test invoice notification
     */
    public function testInvoice(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'client_name' => 'required|string',
            'invoice_number' => 'required|string',
            'total' => 'required|numeric',
            'currency' => 'required|string',
            'payment_link' => 'nullable|url'
        ]);

        try {
            // Create a mock invoice object
            $mockInvoice = (object)[
                'invoice_number' => $request->invoice_number,
                'total' => $request->total,
                'currency' => $request->currency,
                'due_date' => now()->addDays(30),
                'client' => (object)[
                    'contact_name' => $request->client_name
                ]
            ];

            $result = $this->whatsappService->sendInvoice(
                $mockInvoice,
                $request->phone,
                $request->payment_link
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notificación de factura enviada',
                    'data' => $result['data'] ?? null
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Error al enviar notificación'
            ], 400);

        } catch (\Exception $e) {
            Log::error('WhatsApp invoice test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test quote notification
     */
    public function testQuote(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'client_name' => 'required|string',
            'quote_number' => 'required|string',
            'total' => 'required|numeric',
            'currency' => 'required|string'
        ]);

        try {
            // Create a mock quote object
            $mockQuote = (object)[
                'quote_number' => $request->quote_number,
                'total' => $request->total,
                'currency' => $request->currency,
                'expiry_date' => now()->addDays(30),
                'client' => (object)[
                    'contact_name' => $request->client_name
                ]
            ];

            $result = $this->whatsappService->sendQuote(
                $mockQuote,
                $request->phone
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notificación de cotización enviada',
                    'data' => $result['data'] ?? null
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Error al enviar notificación'
            ], 400);

        } catch (\Exception $e) {
            Log::error('WhatsApp quote test error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service status
     */
    public function status()
    {
        return response()->json([
            'enabled' => $this->whatsappService->isEnabled(),
            'phone_id' => env('WHATSAPP_PHONE_ID') ? 'Configurado' : 'No configurado',
            'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID') ? 'Configurado' : 'No configurado',
            'access_token' => env('WHATSAPP_ACCESS_TOKEN') ? 'Configurado' : 'No configurado',
            'webhook_token' => env('WHATSAPP_VERIFY_TOKEN') ? 'Configurado' : 'No configurado',
        ]);
    }
}
