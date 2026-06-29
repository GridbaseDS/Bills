<?php

namespace App\Services\WhatsApp;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Driver for the official Meta / WhatsApp Cloud API (graph.facebook.com).
 *
 * Requires:
 *   - WHATSAPP_ACCESS_TOKEN or setting whatsapp_access_token
 *   - WHATSAPP_PHONE_ID     or setting whatsapp_phone_id
 */
class MetaWhatsAppDriver implements WhatsAppDriverInterface
{
    private array $config;
    private bool $enabled;

    public function __construct(array $settings = [])
    {
        $accessToken = env('WHATSAPP_ACCESS_TOKEN') ?: ($settings['whatsapp_access_token'] ?? '');
        $phoneId     = env('WHATSAPP_PHONE_ID')     ?: ($settings['whatsapp_phone_id'] ?? '');
        $businessId  = env('WHATSAPP_BUSINESS_ACCOUNT_ID') ?: ($settings['whatsapp_business_account_id'] ?? '');

        $this->enabled = !empty($accessToken) && !empty($phoneId);

        $this->config = [
            'api_version'          => 'v19.0',
            'api_base_url'         => 'https://graph.facebook.com',
            'access_token'         => $accessToken,
            'phone_id'             => $phoneId,
            'business_account_id'  => $businessId,
            'default_country_code' => '1',
        ];
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
            return ['success' => false, 'message' => 'WhatsApp (Meta) no está habilitado'];
        }

        $phone       = $this->formatPhone($recipientPhone);
        $clientName  = $invoice->client->contact_name ?? 'Cliente';
        $invoiceNumber = $invoice->invoice_number;
        $total       = $this->formatCurrency($invoice->total, $invoice->currency);
        $dueDate     = $this->formatDate($invoice->due_date);

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
            $result   = $this->sendDocument($phone, $pdfContent, $filename, $caption);
            if ($result['success']) {
                return $result;
            }
            Log::warning('WhatsApp (Meta) document send failed, falling back to text: ' . ($result['message'] ?? ''));
        }

        return $this->sendTextMessage($phone, $caption);
    }

    public function sendQuote($quote, string $recipientPhone, ?string $pdfContent = null, ?string $pdfFilename = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Meta) no está habilitado'];
        }

        $phone      = $this->formatPhone($recipientPhone);
        $clientName = $quote->client->contact_name ?? 'Cliente';
        $quoteNumber = $quote->quote_number;
        $total      = $this->formatCurrency($quote->total, $quote->currency);
        $expiry     = $this->formatDate($quote->expiry_date);

        $caption  = "Hola {$clientName},\n\n";
        $caption .= "Te enviamos la cotizacion *{$quoteNumber}*\n\n";
        $caption .= "Total: *{$total}*\n";
        $caption .= "Valida hasta: {$expiry}\n";
        $caption .= "\nGracias por tu preferencia.\n";
        $caption .= "_GridBase Digital Solutions_";

        if ($pdfContent) {
            $filename = $pdfFilename ?: "Cotizacion-{$quoteNumber}.pdf";
            $result   = $this->sendDocument($phone, $pdfContent, $filename, $caption);
            if ($result['success']) {
                return $result;
            }
            Log::warning('WhatsApp (Meta) document send failed for quote, falling back to text: ' . ($result['message'] ?? ''));
        }

        return $this->sendTextMessage($phone, $caption);
    }

    public function sendPaymentReminder($invoice, string $recipientPhone, ?string $paymentLink = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Meta) no está habilitado'];
        }

        $phone      = $this->formatPhone($recipientPhone);
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

        return $this->sendTextMessage($phone, $message);
    }

    public function sendPaymentConfirmation($invoice, string $recipientPhone, $paymentAmount = null): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Meta) no está habilitado'];
        }

        $phone      = $this->formatPhone($recipientPhone);
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

        return $this->sendTextMessage($phone, $message);
    }

    public function sendTextMessage(string $recipientPhone, string $message): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Meta) no está habilitado'];
        }

        $phone   = $this->formatPhone($recipientPhone);
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'text',
            'text'              => ['body' => $message],
        ];

        return $this->apiRequest($payload);
    }

    public function sendDocument(string $recipientPhone, string $fileContent, string $filename, string $caption = ''): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp (Meta) no está habilitado'];
        }

        $phone   = $this->formatPhone($recipientPhone);
        $mediaId = $this->uploadMedia($fileContent, 'application/pdf', $filename);

        if (!$mediaId) {
            return ['success' => false, 'message' => 'Error al subir el documento a WhatsApp (Meta)'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'document',
            'document'          => [
                'id'       => $mediaId,
                'filename' => $filename,
                'caption'  => $caption,
            ],
        ];

        return $this->apiRequest($payload);
    }

    // ─────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────

    private function uploadMedia(string $fileContent, string $mimeType, string $filename): ?string
    {
        $url = sprintf('%s/%s/%s/media',
            $this->config['api_base_url'],
            $this->config['api_version'],
            $this->config['phone_id']
        );

        try {
            $tmpPath = tempnam(sys_get_temp_dir(), 'wa_pdf_');
            file_put_contents($tmpPath, $fileContent);

            $response = Http::withToken($this->config['access_token'])
                ->timeout(60)
                ->attach('file', fopen($tmpPath, 'r'), $filename, ['Content-Type' => $mimeType])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type'              => $mimeType,
                ]);

            @unlink($tmpPath);

            if ($response->successful() && $response->json('id')) {
                $mediaId = $response->json('id');
                Log::info("WhatsApp (Meta) media uploaded: {$mediaId} ({$filename})");
                return $mediaId;
            }

            Log::error('WhatsApp (Meta) media upload failed', [
                'error'    => $response->json('error.message', 'Unknown error'),
                'response' => $response->json(),
            ]);
            return null;
        } catch (\Exception $e) {
            @unlink($tmpPath ?? '');
            Log::error('WhatsApp (Meta) media upload exception: ' . $e->getMessage());
            return null;
        }
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
                Log::info('WhatsApp (Meta) message sent', [
                    'to'   => $payload['to'] ?? 'unknown',
                    'type' => $payload['type'] ?? 'unknown',
                ]);
                return ['success' => true, 'message' => 'Mensaje enviado (Meta)', 'data' => $response->json()];
            }

            $error = $response->json('error.message', 'Unknown API error');
            Log::error('WhatsApp (Meta) API error', ['error' => $error, 'response' => $response->json()]);
            return ['success' => false, 'message' => "Error Meta API: {$error}", 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('WhatsApp (Meta) exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar (Meta): ' . $e->getMessage()];
        }
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 10 && in_array($phone[0], ['8', '9'])) {
            $phone = '1' . $phone;
        } elseif (strlen($phone) === 10) {
            $phone = $this->config['default_country_code'] . $phone;
        }

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
