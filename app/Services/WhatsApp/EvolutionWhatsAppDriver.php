<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Driver for Evolution API — open-source WhatsApp Web gateway.
 *
 * Requires:
 *   - EVOLUTION_API_URL      e.g. https://wa.gridbase.com.do
 *   - EVOLUTION_API_KEY      API key configured in Evolution API
 *   - EVOLUTION_INSTANCE     instance name e.g. gridbase-bills
 *
 * Evolution API docs: https://doc.evolution-api.com
 */
class EvolutionWhatsAppDriver implements WhatsAppDriverInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $instance;
    private bool $enabled;
    private string $defaultCountryCode = '1';
    private string $phoneNumber = '';

    public function __construct(array $settings = [])
    {
        $this->baseUrl  = rtrim(env('EVOLUTION_API_URL')  ?: ($settings['evolution_api_url']  ?? ''), '/');
        $this->apiKey   = env('EVOLUTION_API_KEY')        ?: ($settings['evolution_api_key']   ?? '');
        $this->instance = env('EVOLUTION_INSTANCE')       ?: ($settings['evolution_instance']  ?? 'gridbase-bills');
        $this->phoneNumber = $settings['evolution_phone_number'] ?? '';

        $this->enabled = !empty($this->baseUrl) && !empty($this->apiKey) && !empty($this->instance);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    // ─────────────────────────────────────────────────────────────
    // Public interface methods
    // ─────────────────────────────────────────────────────────────

    public function sendInvoice($invoice, string $recipientPhone, ?string $paymentLink = null, ?string $pdfContent = null, ?string $pdfFilename = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Evolution) no está habilitado'];
        }

        $clientName    = $invoice->client->contact_name ?? 'Cliente';
        $invoiceNumber = $invoice->invoice_number;
        $total         = $this->formatCurrency($invoice->total, $invoice->currency);
        $dueDate       = $this->formatDate($invoice->due_date);

        $caption  = "Hola {$clientName},\n\n";
        $caption .= "Te enviamos la factura *{$invoiceNumber}*\n\n";
        $caption .= "Total: *{$total}*\n";
        $caption .= "Vence: {$dueDate}\n";
        if ($paymentLink) {
            $caption .= "\nPaga aqui: {$paymentLink}\n";
        }
        $caption .= "\nGracias por tu preferencia.\n";
        $caption .= "_GridBase Digital Solutions_";

        if ($pdfContent) {
            $filename = $pdfFilename ?: "Factura-{$invoiceNumber}.pdf";
            $result   = $this->sendDocument($recipientPhone, $pdfContent, $filename, $caption);
            if ($result['success']) {
                return $result;
            }
            Log::warning('WhatsApp (Evolution) document send failed, falling back to text: ' . ($result['message'] ?? ''));
        }

        return $this->sendTextMessage($recipientPhone, $caption);
    }

    public function sendQuote($quote, string $recipientPhone, ?string $pdfContent = null, ?string $pdfFilename = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Evolution) no está habilitado'];
        }

        $clientName  = $quote->client->contact_name ?? 'Cliente';
        $quoteNumber = $quote->quote_number;
        $total       = $this->formatCurrency($quote->total, $quote->currency);
        $expiry      = $this->formatDate($quote->expiry_date);

        $caption  = "Hola {$clientName},\n\n";
        $caption .= "Te enviamos la cotizacion *{$quoteNumber}*\n\n";
        $caption .= "Total: *{$total}*\n";
        $caption .= "Valida hasta: {$expiry}\n";
        $caption .= "\nGracias por tu preferencia.\n";
        $caption .= "_GridBase Digital Solutions_";

        if ($pdfContent) {
            $filename = $pdfFilename ?: "Cotizacion-{$quoteNumber}.pdf";
            $result   = $this->sendDocument($recipientPhone, $pdfContent, $filename, $caption);
            if ($result['success']) {
                return $result;
            }
            Log::warning('WhatsApp (Evolution) document send failed for quote: ' . ($result['message'] ?? ''));
        }

        return $this->sendTextMessage($recipientPhone, $caption);
    }

    public function sendPaymentReminder($invoice, string $recipientPhone, ?string $paymentLink = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Evolution) no está habilitado'];
        }

        $clientName = $invoice->client->contact_name ?? 'Cliente';
        $balance    = $this->formatCurrency($invoice->total - $invoice->amount_paid, $invoice->currency);

        $message  = "Hola {$clientName},\n\n";
        $message .= "Te recordamos que tienes un saldo pendiente:\n\n";
        $message .= "Factura: *{$invoice->invoice_number}*\n";
        $message .= "Saldo: *{$balance}*\n";
        if ($paymentLink) {
            $message .= "\nPaga aqui: {$paymentLink}\n";
        }
        $message .= "\nGracias por tu preferencia.\n";
        $message .= "_GridBase Digital Solutions_";

        return $this->sendTextMessage($recipientPhone, $message);
    }

    public function sendPaymentConfirmation($invoice, string $recipientPhone, $paymentAmount = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Evolution) no está habilitado'];
        }

        $clientName = $invoice->client->contact_name ?? 'Cliente';
        $currency   = $invoice->currency ?? 'USD';

        $message  = "Hola {$clientName},\n\n";
        $message .= "*Pago recibido*\n\n";
        $message .= "Factura: *{$invoice->invoice_number}*\n";

        if ($paymentAmount) {
            $message .= "Monto recibido: *" . $this->formatCurrency((float)$paymentAmount, $currency) . "*\n";
        }

        if ($invoice->status === 'paid') {
            $message .= "\nEsta factura ha sido saldada en su totalidad.\n";
        } else {
            $pending  = $invoice->total - $invoice->amount_paid;
            $message .= "Saldo pendiente: *" . $this->formatCurrency($pending, $currency) . "*\n";
        }

        $message .= "\n¡Gracias por su pago!\n";
        $message .= "_GridBase Digital Solutions_";

        return $this->sendTextMessage($recipientPhone, $message);
    }

    /**
     * Send a plain text message via Evolution API.
     * POST /message/sendText/{instance}
     */
    public function sendTextMessage(string $recipientPhone, string $message): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Evolution) no está habilitado'];
        }

        $number  = $this->formatPhone($recipientPhone);
        $url     = "{$this->baseUrl}/message/sendText/{$this->instance}";
        $payload = [
            'number'  => $number,
            'text'    => $message,
            'options' => [
                'delay' => 1200,
                'presence' => 'composing'
            ]
        ];

        return $this->apiRequest('POST', $url, $payload);
    }

    /**
     * Send a document (PDF) via Evolution API.
     * Evolution API accepts the file as a base64-encoded string.
     * POST /message/sendMedia/{instance}
     */
    public function sendDocument(string $recipientPhone, string $fileContent, string $filename, string $caption = ''): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Evolution) no está habilitado'];
        }

        $number  = $this->formatPhone($recipientPhone);
        $url     = "{$this->baseUrl}/message/sendMedia/{$this->instance}";
        $payload = [
            'number'    => $number,
            'mediatype' => 'document',
            'mimetype'  => 'application/pdf',
            'caption'   => $caption,
            'fileName'  => $filename,
            'media'     => base64_encode($fileContent),
        ];

        return $this->apiRequest('POST', $url, $payload);
    }

    /**
     * Check if the WhatsApp instance is connected (QR scanned and session active).
     * GET /instance/connectionState/{instance}
     */
    public function getConnectionState(): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'state' => 'disabled', 'message' => 'Evolution API no está configurada'];
        }

        $url = "{$this->baseUrl}/instance/connectionState/{$this->instance}";
        $result = $this->apiRequest('GET', $url);

        if ($result['success']) {
            $state = $result['data']['instance']['state'] ?? $result['data']['state'] ?? 'unknown';
            return [
                'success'   => true,
                'state'     => $state,
                'connected' => $state === 'open',
                'message'   => $state === 'open' ? 'Conectado' : "Estado: {$state}",
            ];
        }

        return $result;
    }

    /**
     * Get the QR code for connecting a new session.
     * GET /instance/connect/{instance}
     */
    public function getQrCode(): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Evolution API no está configurada'];
        }

        $url    = "{$this->baseUrl}/instance/connect/{$this->instance}";
        $result = $this->apiRequest('GET', $url);

        // Auto-heal: If connect fails (due to conflict, corrupt state, or missing instance), reset it.
        if (!$result['success']) {
            Log::warning("La conexión de Evolution falló para {$this->instance}. Iniciando autorecuperación/recreación de la instancia...");
            
            // Delete instance (silently ignore failure if it doesn't exist)
            $this->deleteInstance();
            
            // Recreate instance
            $createResult = $this->createInstance();
            if (!$createResult['success']) {
                Log::error("Autorecuperación fallida: No se pudo recrear la instancia {$this->instance}");
                return $result; // Return original error
            }

            Log::info("Autorecuperación exitosa: Instancia {$this->instance} recreada. Reintentando connect...");
            
            // Retry connect request
            $result = $this->apiRequest('GET', $url);
        }

        if ($result['success']) {
            $qrCode      = $result['data']['base64'] ?? $result['data']['qrcode']['base64'] ?? null;
            $pairingCode = $result['data']['pairingCode'] ?? $result['data']['qrcode']['pairingCode'] ?? null;
            return [
                'success'      => true,
                'qr_code'      => $qrCode,
                'pairing_code' => $pairingCode,
                'message'      => $qrCode ? 'QR generado' : 'Ya conectado (no necesita QR)',
            ];
        }

        return $result;
    }

    /**
     * Get a pairing code for connecting via phone number (more reliable than QR).
     * Recreates the instance with the phone number to trigger pairing code generation.
     */
    public function getPairingCode(string $phoneNumber = ''): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Evolution API no está configurada'];
        }

        $phone = $this->formatPhone($phoneNumber ?: $this->phoneNumber);
        if (empty($phone)) {
            return ['success' => false, 'message' => 'Se requiere un número de teléfono para generar el código de vinculación'];
        }

        // Delete existing instance to start fresh
        $this->deleteInstance();
        usleep(500000); // 500ms

        // Create instance with phone number and qrcode=true to trigger pairing code
        $url = "{$this->baseUrl}/instance/create";
        $payload = [
            'instanceName' => $this->instance,
            'token'        => 'E34467E0-7502-4173-A25E-F313497F7EA7',
            'number'       => $phone,
            'qrcode'       => true,
            'integration'  => 'WHATSAPP-BAILEYS',
        ];

        $result = $this->apiRequest('POST', $url, $payload);

        if (!$result['success']) {
            return ['success' => false, 'message' => 'Error al crear instancia: ' . ($result['message'] ?? 'Unknown')];
        }

        $pairingCode = $result['data']['qrcode']['pairingCode'] ?? null;

        if ($pairingCode) {
            // Format as XXXX-XXXX for readability
            $formatted = strlen($pairingCode) === 8
                ? substr($pairingCode, 0, 4) . '-' . substr($pairingCode, 4)
                : $pairingCode;

            return [
                'success'      => true,
                'pairing_code' => $pairingCode,
                'formatted'    => $formatted,
                'phone'        => $phone,
                'message'      => 'Código de vinculación generado. Introdúcelo en WhatsApp → Dispositivos vinculados → Vincular con número de teléfono.',
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo generar el código de vinculación. Intenta de nuevo.',
        ];
    }

    /**
     * Delete the instance from Evolution API.
     * DELETE /instance/delete/{instance}
     */
    public function deleteInstance(): array
    {
        $url = "{$this->baseUrl}/instance/delete/{$this->instance}";
        return $this->apiRequest('DELETE', $url);
    }

    /**
     * Create the instance on Evolution API.
     * POST /instance/create
     */
    public function createInstance(): array
    {
        $url = "{$this->baseUrl}/instance/create";
        $phone = $this->formatPhone($this->phoneNumber);
        $payload = [
            'instanceName' => $this->instance,
            'token'        => 'E34467E0-7502-4173-A25E-F313497F7EA7',
            'qrcode'       => true,
            'integration'  => 'WHATSAPP-BAILEYS',
        ];
        if (!empty($phone)) {
            $payload['number'] = $phone;
        }
        return $this->apiRequest('POST', $url, $payload);
    }

    /**
     * Logout / disconnect the current WhatsApp session.
     * DELETE /instance/logout/{instance}
     */
    public function logout(): array
    {
        $url = "{$this->baseUrl}/instance/logout/{$this->instance}";
        return $this->apiRequest('DELETE', $url);
    }

    // ─────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────

    private function apiRequest(string $method, string $url, array $payload = []): array
    {
        try {
            $request = Http::withHeaders([
                'apikey'       => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30);

            $response = match (strtoupper($method)) {
                'POST'   => $request->post($url, $payload),
                'GET'    => $request->get($url),
                'DELETE' => $request->delete($url),
                default  => $request->post($url, $payload),
            };

            if ($response->successful()) {
                Log::info("WhatsApp (Evolution) {$method} {$url} - OK");
                return ['success' => true, 'message' => 'OK', 'data' => $response->json()];
            }

            $error = $response->json('message', $response->json('error', 'Unknown error'));
            Log::error("WhatsApp (Evolution) API error [{$response->status()}]", [
                'url'      => $url,
                'error'    => $error,
                'response' => $response->json(),
            ]);
            return [
                'success' => false,
                'message' => "Error Evolution API ({$response->status()}): {$error}",
                'data'    => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error("WhatsApp (Evolution) exception: " . $e->getMessage(), ['url' => $url]);
            return ['success' => false, 'message' => 'Error de conexion con Evolution API: ' . $e->getMessage()];
        }
    }

    private function formatPhone(string $phone): string
    {
        // Strip non-numeric chars
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Dominican numbers: 10 digits starting with 8 or 9 → prepend country code 1
        if (strlen($phone) === 10 && in_array($phone[0], ['8', '9'])) {
            $phone = '1' . $phone;
        } elseif (strlen($phone) === 10) {
            $phone = $this->defaultCountryCode . $phone;
        }

        // Evolution API expects the number with @s.whatsapp.net or just the number
        // Format: 18091234567 (no spaces, no +, no dashes)
        return $phone;
    }

    private function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        $symbols = ['USD' => '$', 'EUR' => '€', 'MXN' => '$', 'DOP' => 'RD$'];
        return ($symbols[$currency] ?? $currency . ' ') . number_format($amount, 2);
    }

    private function formatDate($date): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        return $date->format('d/m/Y');
    }
}
