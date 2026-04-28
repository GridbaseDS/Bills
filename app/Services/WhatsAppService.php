<?php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    private array $config;
    private bool $enabled;

    public function __construct()
    {
        $settings = Setting::getAll();
        $this->enabled = (bool)($settings['whatsapp_enabled'] ?? false);
        $this->config = [
            'api_version'  => 'v19.0',
            'api_base_url' => 'https://graph.facebook.com',
            'access_token' => $settings['whatsapp_access_token'] ?? '',
            'phone_id'     => $settings['whatsapp_phone_id'] ?? '',
            'templates'    => [
                'invoice_sent' => 'gridbase_invoice_notification',
                'invoice_reminder' => 'gridbase_payment_reminder',
                'quote_sent' => 'gridbase_quote_notification'
            ],
            'default_country_code' => '1',
        ];
    }

    public function isEnabled(): bool { return $this->enabled; }

    public function sendInvoiceNotification($invoice, string $recipientPhone): array
    {
        if (!$this->enabled) return ['success' => false, 'message' => 'WhatsApp is not enabled'];

        $phone = $this->formatPhone($recipientPhone);
        $templateName = $this->config['templates']['invoice_sent'];

        return $this->sendTemplate($phone, $templateName, [
            ['type' => 'text', 'text' => $invoice->client->contact_name ?? 'Client'],
            ['type' => 'text', 'text' => $invoice->invoice_number],
            ['type' => 'text', 'text' => $this->formatCurrency($invoice->total, $invoice->currency)],
            ['type' => 'text', 'text' => $this->formatDate($invoice->due_date)],
        ]);
    }

    public function sendPaymentReminder($invoice, string $recipientPhone): array
    {
        if (!$this->enabled) return ['success' => false, 'message' => 'WhatsApp is not enabled'];

        $phone = $this->formatPhone($recipientPhone);
        $templateName = $this->config['templates']['invoice_reminder'];

        return $this->sendTemplate($phone, $templateName, [
            ['type' => 'text', 'text' => $invoice->client->contact_name ?? 'Client'],
            ['type' => 'text', 'text' => $invoice->invoice_number],
            ['type' => 'text', 'text' => $this->formatCurrency($invoice->total - $invoice->amount_paid, $invoice->currency)],
        ]);
    }

    public function sendQuoteNotification($quote, string $recipientPhone): array
    {
        if (!$this->enabled) return ['success' => false, 'message' => 'WhatsApp is not enabled'];

        $phone = $this->formatPhone($recipientPhone);
        $templateName = $this->config['templates']['quote_sent'];

        return $this->sendTemplate($phone, $templateName, [
            ['type' => 'text', 'text' => $quote->client->contact_name ?? 'Client'],
            ['type' => 'text', 'text' => $quote->quote_number],
            ['type' => 'text', 'text' => $this->formatCurrency($quote->total, $quote->currency)],
            ['type' => 'text', 'text' => $this->formatDate($quote->expiry_date)],
        ]);
    }

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

        $response = Http::withToken($this->config['access_token'])
            ->timeout(30)
            ->post($url, $payload);

        if ($response->successful()) {
            return ['success' => true, 'message' => 'WhatsApp message sent', 'data' => $response->json()];
        }

        $error = $response->json('error.message', 'Unknown API error');
        return ['success' => false, 'message' => "WhatsApp API error: $error", 'data' => $response->json()];
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

    private function formatDate($date): string
    {
        return $date->format('M d, Y');
    }
}
