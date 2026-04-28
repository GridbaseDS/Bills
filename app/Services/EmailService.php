<?php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class EmailService
{
    private array $config;

    public function __construct()
    {
        $settings = Setting::getAll();
        
        $this->config = [
            'host'       => $settings['smtp_host'] ?? '',
            'port'       => (int)($settings['smtp_port'] ?? 587),
            'username'   => $settings['smtp_username'] ?? '',
            'password'   => $settings['smtp_password'] ?? '',
            'encryption' => $settings['smtp_encryption'] ?? 'tls',
            'from_name'  => $settings['smtp_from_name'] ?? 'GridBase',
            'from_email' => $settings['smtp_from_email'] ?? 'hello@gridbase.com.do',
        ];

        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'encryption' => $this->config['encryption'],
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);

        Config::set('mail.from.address', $this->config['from_email']);
        Config::set('mail.from.name', $this->config['from_name']);
    }

    public function sendInvoice($invoice, string $pdfPath): array
    {
        $subject = "Factura {$invoice->invoice_number} de {$this->config['from_name']}";
        $body = "<p>Hola {$invoice->client->contact_name},</p><p>Adjuntamos la factura {$invoice->invoice_number}.</p>";

        return $this->send($invoice->client->email, $invoice->client->contact_name, $subject, $body, $pdfPath, "Factura-{$invoice->invoice_number}.pdf");
    }

    public function sendQuote($quote, string $pdfPath): array
    {
        $subject = "Cotizacion {$quote->quote_number} de {$this->config['from_name']}";
        $body = "<p>Hola {$quote->client->contact_name},</p><p>Adjuntamos la cotizacion {$quote->quote_number}.</p>";

        return $this->send($quote->client->email, $quote->client->contact_name, $subject, $body, $pdfPath, "Cotizacion-{$quote->quote_number}.pdf");
    }

    public function sendReminder($invoice): array
    {
        $subject = "Recordatorio de Pago: Factura {$invoice->invoice_number}";
        $body = "<p>Hola {$invoice->client->contact_name},</p><p>Este es un recordatorio de pago para la factura {$invoice->invoice_number}.</p>";

        return $this->send($invoice->client->email, $invoice->client->contact_name, $subject, $body);
    }

    private function send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $attachPath = null, ?string $attachName = null): array
    {
        try {
            Mail::html($htmlBody, function ($message) use ($toEmail, $toName, $subject, $attachPath, $attachName) {
                $message->to($toEmail, $toName)->subject($subject);
                if ($attachPath && file_exists($attachPath)) {
                    $message->attach($attachPath, ['as' => $attachName ?? basename($attachPath)]);
                }
            });
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
        }
    }
}
