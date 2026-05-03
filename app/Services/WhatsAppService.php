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
     * Send invoice notification via WhatsApp
     * Can send with payment link or just notification
     */
    public function sendInvoice($invoice, string $recipientPhone, ?string $paymentLink = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp no está habilitado'];
        }

        $phone = $this->formatPhone($recipientPhone);
        
        // Build message
        $clientName = $invoice->client->contact_name ?? 'Cliente';
        $invoiceNumber = $invoice->invoice_number;
        $total = $this->formatCurrency($invoice->total, $invoice->currency);
        $dueDate = $this->formatDate($invoice->due_date);
        
        $message = "Hola {$clientName},\n\n";
        $message .= "Te enviamos la factura *{$invoiceNumber}*\n\n";
        $message .= "📄 Total: *{$total}*\n";
        $message .= "📅 Vence: {$dueDate}\n";
        
        if ($paymentLink) {
            $message .= "\n💳 Paga aquí: {$paymentLink}\n";
        }
        
        $message .= "\nGracias por tu preferencia.\n";
        $message .= "_GridBase Digital Solutions_";
        
        return $this->sendTextMessage($phone, $message);
    }

    /**
     * Send quote notification via WhatsApp
     */
    public function sendQuote($quote, string $recipientPhone): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp no está habilitado'];
        }

        $phone = $this->formatPhone($recipientPhone);
        
        // Build message
        $clientName = $quote->client->contact_name ?? 'Cliente';
        $quoteNumber = $quote->quote_number;
        $total = $this->formatCurrency($quote->total, $quote->currency);
        $expiryDate = $this->formatDate($quote->expiry_date);
        
        $message = "Hola {$clientName},\n\n";
        $message .= "Te enviamos la cotización *{$quoteNumber}*\n\n";
        $message .= "📄 Total: *{$total}*\n";
        $message .= "📅 Válida hasta: {$expiryDate}\n";
        $message .= "\nGracias por tu preferencia.\n";
        $message .= "_GridBase Digital Solutions_";
        
        return $this->sendTextMessage($phone, $message);
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

