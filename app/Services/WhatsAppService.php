<?php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private array $config;
    private bool $enabled;

    public function __construct()
    {
        $settings = Setting::getAll();
        
        // Try .env first, then database settings
        $accessToken = env('WHATSAPP_ACCESS_TOKEN') ?: ($settings['whatsapp_access_token'] ?? '');
        $phoneId = env('WHATSAPP_PHONE_ID') ?: ($settings['whatsapp_phone_id'] ?? '');
        $businessAccountId = env('WHATSAPP_BUSINESS_ACCOUNT_ID') ?: ($settings['whatsapp_business_account_id'] ?? '');
        
        // WhatsApp is enabled if we have both access token and phone ID
        $this->enabled = !empty($accessToken) && !empty($phoneId);
        
        $this->config = [
            'api_version'  => 'v19.0',
            'api_base_url' => 'https://graph.facebook.com',
            'access_token' => $accessToken,
            'phone_id'     => $phoneId,
            'business_account_id' => $businessAccountId,
            'templates'    => [
                'invoice_sent' => 'gridbase_invoice_notification',
                'invoice_reminder' => 'gridbase_payment_reminder',
                'quote_sent' => 'gridbase_quote_notification'
            ],
            'default_country_code' => '1',
        ];
    }

    public function isEnabled(): bool { return $this->enabled; }

    /**
     * Send invoice notification via WhatsApp with optional PDF attachment
     */
    public function sendInvoice($invoice, string $recipientPhone, ?string $paymentLink = null, ?string $pdfContent = null, ?string $pdfFilename = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp no está habilitado'];
        }

        $phone = $this->formatPhone($recipientPhone);
        
        // Build caption/message
        $clientName = $invoice->client->contact_name ?? 'Cliente';
        $invoiceNumber = $invoice->invoice_number;
        $total = $this->formatCurrency($invoice->total, $invoice->currency);
        $dueDate = $this->formatDate($invoice->due_date);
        
        $caption = "Hola {$clientName},\n\n";
        $caption .= "Te enviamos la factura *{$invoiceNumber}*\n\n";
        $caption .= "📄 Total: *{$total}*\n";
        $caption .= "📅 Vence: {$dueDate}\n";
        
        if ($paymentLink) {
            $caption .= "\n💳 Paga aquí: {$paymentLink}\n";
        }
        
        $caption .= "\nGracias por tu preferencia.\n";
        $caption .= "_GridBase Digital Solutions_";

        // If we have PDF content, send as document with caption
        if ($pdfContent) {
            $filename = $pdfFilename ?: "Factura-{$invoiceNumber}.pdf";
            $result = $this->sendDocument($phone, $pdfContent, $filename, $caption);
            if ($result['success']) {
                return $result;
            }
            // If document send fails, fall back to text message
            Log::warning("WhatsApp document send failed, falling back to text: " . ($result['message'] ?? ''));
        }
        
        return $this->sendTextMessage($phone, $caption);
    }

    /**
     * Send quote notification via WhatsApp with optional PDF attachment
     */
    public function sendQuote($quote, string $recipientPhone, ?string $pdfContent = null, ?string $pdfFilename = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp no está habilitado'];
        }

        $phone = $this->formatPhone($recipientPhone);
        
        // Build caption
        $clientName = $quote->client->contact_name ?? 'Cliente';
        $quoteNumber = $quote->quote_number;
        $total = $this->formatCurrency($quote->total, $quote->currency);
        $expiryDate = $this->formatDate($quote->expiry_date);
        
        $caption = "Hola {$clientName},\n\n";
        $caption .= "Te enviamos la cotización *{$quoteNumber}*\n\n";
        $caption .= "📄 Total: *{$total}*\n";
        $caption .= "📅 Válida hasta: {$expiryDate}\n";
        $caption .= "\nGracias por tu preferencia.\n";
        $caption .= "_GridBase Digital Solutions_";

        // If we have PDF content, send as document
        if ($pdfContent) {
            $filename = $pdfFilename ?: "Cotizacion-{$quoteNumber}.pdf";
            $result = $this->sendDocument($phone, $pdfContent, $filename, $caption);
            if ($result['success']) {
                return $result;
            }
            Log::warning("WhatsApp document send failed for quote, falling back to text: " . ($result['message'] ?? ''));
        }
        
        return $this->sendTextMessage($phone, $caption);
    }

    /**
     * Send payment reminder via WhatsApp
     */
    public function sendPaymentReminder($invoice, string $recipientPhone, ?string $paymentLink = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp no está habilitado'];
        }

        $phone = $this->formatPhone($recipientPhone);
        
        $clientName = $invoice->client->contact_name ?? 'Cliente';
        $invoiceNumber = $invoice->invoice_number;
        $balance = $this->formatCurrency($invoice->total - $invoice->amount_paid, $invoice->currency);
        
        $message = "Hola {$clientName},\n\n";
        $message .= "Te recordamos que tienes un saldo pendiente:\n\n";
        $message .= "📄 Factura: *{$invoiceNumber}*\n";
        $message .= "💰 Saldo: *{$balance}*\n";
        
        if ($paymentLink) {
            $message .= "\n💳 Paga aquí: {$paymentLink}\n";
        }
        
        $message .= "\nGracias por tu preferencia.\n";
        $message .= "_GridBase Digital Solutions_";
        
        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send payment confirmation via WhatsApp
     */
    public function sendPaymentConfirmation($invoice, string $recipientPhone, $paymentAmount = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp no está habilitado'];
        }

        $phone = $this->formatPhone($recipientPhone);

        $clientName = $invoice->client->contact_name ?? 'Cliente';
        $invoiceNumber = $invoice->invoice_number;
        $currency = $invoice->currency ?? 'USD';

        $message = "Hola {$clientName},\n\n";
        $message .= "✅ *Pago recibido*\n\n";
        $message .= "📄 Factura: *{$invoiceNumber}*\n";

        if ($paymentAmount) {
            $message .= "💰 Monto recibido: *" . $this->formatCurrency((float)$paymentAmount, $currency) . "*\n";
        }

        if ($invoice->status === 'paid') {
            $message .= "\n🎉 Esta factura ha sido saldada en su totalidad.\n";
        } else {
            $pending = $invoice->total - $invoice->amount_paid;
            $message .= "📊 Saldo pendiente: *" . $this->formatCurrency($pending, $currency) . "*\n";
        }

        $message .= "\n¡Gracias por su pago!\n";
        $message .= "_GridBase Digital Solutions_";

        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send a plain text message via WhatsApp
     */
    public function sendTextMessage(string $recipientPhone, string $message): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp no está habilitado'];
        }

        $phone = $this->formatPhone($recipientPhone);
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message],
        ];
        return $this->apiRequest($payload);
    }

    /**
     * Send a document (PDF) via WhatsApp
     * Uploads the file to Media API first, then sends as document message
     */
    public function sendDocument(string $recipientPhone, string $fileContent, string $filename, string $caption = ''): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp no está habilitado'];
        }

        $phone = $this->formatPhone($recipientPhone);

        // Step 1: Upload media to WhatsApp
        $mediaId = $this->uploadMedia($fileContent, 'application/pdf', $filename);
        if (!$mediaId) {
            return ['success' => false, 'message' => 'Error al subir el documento a WhatsApp'];
        }

        // Step 2: Send document message
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'document',
            'document' => [
                'id' => $mediaId,
                'filename' => $filename,
                'caption' => $caption,
            ],
        ];

        return $this->apiRequest($payload);
    }

    /**
     * Upload media (file) to WhatsApp Cloud API
     * Returns the media_id on success, null on failure
     */
    private function uploadMedia(string $fileContent, string $mimeType, string $filename): ?string
    {
        $url = sprintf('%s/%s/%s/media',
            $this->config['api_base_url'],
            $this->config['api_version'],
            $this->config['phone_id']
        );

        try {
            // Save to a temp file for the multipart upload
            $tmpPath = tempnam(sys_get_temp_dir(), 'wa_pdf_');
            file_put_contents($tmpPath, $fileContent);

            $response = Http::withToken($this->config['access_token'])
                ->timeout(60)
                ->attach('file', fopen($tmpPath, 'r'), $filename, ['Content-Type' => $mimeType])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type' => $mimeType,
                ]);

            // Clean up temp file
            @unlink($tmpPath);

            if ($response->successful() && $response->json('id')) {
                $mediaId = $response->json('id');
                Log::info("WhatsApp media uploaded: {$mediaId} ({$filename})");
                return $mediaId;
            }

            $error = $response->json('error.message', 'Unknown error');
            Log::error('WhatsApp media upload failed', [
                'error' => $error,
                'response' => $response->json()
            ]);
            return null;
        } catch (\Exception $e) {
            @unlink($tmpPath ?? '');
            Log::error('WhatsApp media upload exception: ' . $e->getMessage());
            return null;
        }
    }

    // Legacy methods for backward compatibility
    public function sendInvoiceNotification($invoice, string $recipientPhone): array
    {
        return $this->sendInvoice($invoice, $recipientPhone);
    }

    public function sendQuoteNotification($quote, string $recipientPhone): array
    {
        return $this->sendQuote($quote, $recipientPhone);
    }

    private function sendTemplate(string $phone, string $templateName, array $parameters): array
    {
        $components = [];
        if (!empty($parameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'en'],
                'components' => $components,
            ],
        ];

        return $this->apiRequest($payload);
    }

    private function apiRequest(array $payload): array
    {
        $url = sprintf('%s/%s/%s/messages',
            $this->config['api_base_url'],
            $this->config['api_version'],
            $this->config['phone_id']
        );

        try {
            $response = Http::withToken($this->config['access_token'])
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'to' => $payload['to'] ?? 'unknown',
                    'type' => $payload['type'] ?? 'unknown'
                ]);
                return ['success' => true, 'message' => 'Mensaje de WhatsApp enviado', 'data' => $response->json()];
            }

            $error = $response->json('error.message', 'Unknown API error');
            Log::error('WhatsApp API error', [
                'error' => $error,
                'response' => $response->json()
            ]);
            return ['success' => false, 'message' => "Error de WhatsApp API: $error", 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('WhatsApp exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error al enviar mensaje de WhatsApp: ' . $e->getMessage()];
        }
    }

    private function formatPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If it's a Dominican number (10 digits starting with 8 or 9), add country code
        if (strlen($phone) === 10 && in_array($phone[0], ['8', '9'])) {
            $phone = '1' . $phone; // Dominican Republic country code
        }
        // If it's exactly 10 digits, assume it needs the default country code
        elseif (strlen($phone) === 10) {
            $phone = $this->config['default_country_code'] . $phone;
        }
        
        return $phone;
    }

    private function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        $symbols = ['USD'=>'$','EUR'=>'€','MXN'=>'$','DOP'=>'RD$'];
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

