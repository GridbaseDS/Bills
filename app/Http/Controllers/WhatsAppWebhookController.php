<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify webhook for WhatsApp Cloud API
     * Meta sends this GET request to verify your endpoint
     */
    public function verify(Request $request)
    {
        // El token que definiste en la configuración de Meta
        $verifyToken = config('services.whatsapp.verify_token', 'GridBase_WhatsApp_2026_SecureToken');
        
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        // Verificar que el token coincida
        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp Webhook verified successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp Webhook verification failed', [
            'mode' => $mode,
            'token_received' => $token,
            'expected_token' => $verifyToken
        ]);

        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * Receive webhook notifications from WhatsApp
     * This handles incoming messages, status updates, etc.
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->all();
            
            // Log completo para debugging
            Log::info('WhatsApp Webhook received', ['payload' => $data]);

            // Verificar que venga de WhatsApp
            if (!isset($data['object']) || $data['object'] !== 'whatsapp_business_account') {
                return response()->json(['status' => 'ignored'], 200);
            }

            // Procesar cada entrada
            foreach ($data['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $this->processChange($change);
                }
            }

            // Siempre responder 200 para que WhatsApp sepa que recibiste el webhook
            return response()->json(['status' => 'received'], 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Aún así devolver 200 para no saturar los reintentos de WhatsApp
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Process a single webhook change/notification
     */
    private function processChange(array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        switch ($field) {
            case 'messages':
                $this->handleIncomingMessages($value);
                break;
            
            case 'message_status':
                $this->handleMessageStatus($value);
                break;
            
            default:
                Log::info('WhatsApp: Unhandled webhook field', ['field' => $field]);
        }
    }

    /**
     * Handle incoming messages from users
     */
    private function handleIncomingMessages(array $value): void
    {
        $messages = $value['messages'] ?? [];
        
        foreach ($messages as $message) {
            $from = $message['from'] ?? 'unknown';
            $messageId = $message['id'] ?? 'unknown';
            $timestamp = $message['timestamp'] ?? time();
            $type = $message['type'] ?? 'unknown';

            Log::info('WhatsApp: Incoming message', [
                'from' => $from,
                'message_id' => $messageId,
                'type' => $type,
                'timestamp' => $timestamp
            ]);

            // Aquí puedes agregar lógica para responder mensajes automáticamente
            // Por ejemplo: buscar factura por número, enviar información, etc.
            
            switch ($type) {
                case 'text':
                    $text = $message['text']['body'] ?? '';
                    Log::info('WhatsApp: Text message received', ['text' => $text]);
                    // TODO: Procesar mensaje de texto
                    break;
                
                case 'button':
                    $buttonText = $message['button']['text'] ?? '';
                    Log::info('WhatsApp: Button pressed', ['button' => $buttonText]);
                    // TODO: Procesar botón presionado
                    break;
            }
        }
    }

    /**
     * Handle message status updates (sent, delivered, read, failed)
     */
    private function handleMessageStatus(array $value): void
    {
        $statuses = $value['statuses'] ?? [];
        
        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? 'unknown';
            $statusType = $status['status'] ?? 'unknown';
            $timestamp = $status['timestamp'] ?? time();
            $recipientId = $status['recipient_id'] ?? 'unknown';

            Log::info('WhatsApp: Message status update', [
                'message_id' => $messageId,
                'status' => $statusType,
                'recipient' => $recipientId,
                'timestamp' => $timestamp
            ]);

            // Aquí puedes actualizar el estado de mensajes en tu base de datos
            // Por ejemplo: marcar factura como "leída" cuando el cliente vea el mensaje
        }
    }
}
