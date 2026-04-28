<?php
namespace App\Services;

use App\Models\Setting;

/**
 * WhatsApp Service using Meta Cloud API.
 * Sends pre-approved template messages for invoices, quotes, and reminders.
 */
class WhatsAppService
{
    private array $config;
    private bool $enabled;

    public function __construct()
    {
        $fileConfig = require __DIR__ . '/../../config/whatsapp.php';
        try {
            $setting = new Setting();
            $dbConfig = $setting->getGroup('whatsapp');
            $this->enabled = (bool)($dbConfig['whatsapp_enabled'] ?? $fileConfig['enabled']);
            $this->config = [
                'api_version'  => $fileConfig['api_version'],
                'api_base_url' => $fileConfig['api_base_url'],
                'access_token' => $dbConfig['whatsapp_access_token'] ?: $fileConfig['access_token'],
                'phone_id'     => $dbConfig['whatsapp_phone_id'] ?: $fileConfig['phone_id'],
                'templates'    => $fileConfig['templates'],
                'default_country_code' => $fileConfig['default_country_code'],
            ];
        } catch (\Exception $e) {
            $this->config = $fileConfig;
            $this->enabled = (bool)$fileConfig['enabled'];
        }
    }

    public function isEnabled(): bool { return $this->enabled; }

    /**
     * Send invoice notification via WhatsApp.
     */
    public function sendInvoiceNotification(array $invoice, string $recipientPhone): array
    {
        if (!$this->enabled) return ['success' => false, 'message' => 'WhatsApp is not enabled'];

        $phone = $this->formatPhone($recipientPhone);
        $templateName = $this->config['templates']['invoice_sent'] ?? 'gridbase_invoice_notification';

        return $this->sendTemplate($phone, $templateName, [
            ['type' => 'text', 'text' => $invoice['contact_name'] ?? 'Client'],
            ['type' => 'text', 'text' => $invoice['invoice_number']],
            ['type' => 'text', 'text' => $this->formatCurrency($invoice['total'], $invoice['currency'])],
            ['type' => 'text', 'text' => $this->formatDate($invoice['due_date'])],
        ]);
    }

    /**
     * Send payment reminder via WhatsApp.
     */
    public function sendPaymentReminder(array $invoice, string $recipientPhone): array
    {
        if (!$this->enabled) return ['success' => false, 'message' => 'WhatsApp is not enabled'];

        $phone = $this->formatPhone($recipientPhone);
        $templateName = $this->config['templates']['invoice_reminder'] ?? 'gridbase_payment_reminder';

        return $this->sendTemplate($phone, $templateName, [
            ['type' => 'text', 'text' => $invoice['contact_name'] ?? 'Client'],
            ['type' => 'text', 'text' => $invoice['invoice_number']],
            ['type' => 'text', 'text' => $this->formatCurrency($invoice['total'] - $invoice['amount_paid'], $invoice['currency'])],
        ]);
    }

    /**
     * Send quote notification via WhatsApp.
     */
    public function sendQuoteNotification(array $quote, string $recipientPhone): array
    {
        if (!$this->enabled) return ['success' => false, 'message' => 'WhatsApp is not enabled'];

        $phone = $this->formatPhone($recipientPhone);
        $templateName = $this->config['templates']['quote_sent'] ?? 'gridbase_quote_notification';

        return $this->sendTemplate($phone, $templateName, [
            ['type' => 'text', 'text' => $quote['contact_name'] ?? 'Client'],
            ['type' => 'text', 'text' => $quote['quote_number']],
            ['type' => 'text', 'text' => $this->formatCurrency($quote['total'], $quote['currency'])],
            ['type' => 'text', 'text' => $this->formatDate($quote['expiry_date'])],
        ]);
    }

    /**
     * Send a free-form text message (only works within 24h conversation window).
     */
    public function sendTextMessage(string $recipientPhone, string $message): array
    {
        if (!$this->enabled) return ['success' => false, 'message' => 'WhatsApp is not enabled'];

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
     * Send a template message via the Meta Cloud API.
     */
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

    /**
     * Make an API request to Meta's WhatsApp Cloud API.
     */
    private function apiRequest(array $payload): array
    {
        $url = sprintf('%s/%s/%s/messages',
            $this->config['api_base_url'],
            $this->config['api_version'],
            $this->config['phone_id']
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->config['access_token'],
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) return ['success' => false, 'message' => "cURL error: $error"];

        $data = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'WhatsApp message sent', 'data' => $data];
        }
        $errMsg = $data['error']['message'] ?? 'Unknown API error';
        return ['success' => false, 'message' => "WhatsApp API error: $errMsg", 'data' => $data];
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10) $phone = $this->config['default_country_code'] . $phone;
        return $phone;
    }

    private function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        $symbols = ['USD'=>'$','EUR'=>'E','MXN'=>'$','DOP'=>'RD$'];
        return ($symbols[$currency] ?? $currency . ' ') . number_format($amount, 2);
    }

    private function formatDate(string $date): string
    {
        return date('M d, Y', strtotime($date));
    }
}
