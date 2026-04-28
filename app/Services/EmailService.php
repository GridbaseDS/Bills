<?php
namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private array $config;

    public function __construct()
    {
        $settings = Setting::getAll();
        
        $this->config = [
            'host'       => $settings['smtp_host'] ?? 'localhost',
            'port'       => (int)($settings['smtp_port'] ?? 25),
            'username'   => $settings['smtp_username'] ?? '',
            'password'   => $settings['smtp_password'] ?? '',
            'encryption' => $settings['smtp_encryption'] ?? null,
            'from_name'  => $settings['smtp_from_name'] ?? 'Gridbase Bills',
            'from_email' => $settings['smtp_from_email'] ?? 'bills@gridbase.com.do',
        ];

        // Apply config through the centralized method
        self::applySmtpConfig($this->config);
    }

    /**
     * Centralized SMTP configuration for the entire application.
     * This is the SINGLE source of truth for mail config.
     */
    public static function applySmtpConfig(array $smtpSettings): void
    {
        $host = trim($smtpSettings['host'] ?? $smtpSettings['smtp_host'] ?? '') ?: 'localhost';
        $port = (int)($smtpSettings['port'] ?? $smtpSettings['smtp_port'] ?? 25) ?: 25;
        $encryption = $smtpSettings['encryption'] ?? $smtpSettings['smtp_encryption'] ?? null;
        $username = $smtpSettings['username'] ?? $smtpSettings['smtp_username'] ?? null;
        $password = $smtpSettings['password'] ?? $smtpSettings['smtp_password'] ?? null;
        $fromEmail = $smtpSettings['from_email'] ?? $smtpSettings['smtp_from_email'] ?? 'bills@gridbase.com.do';
        $fromName = $smtpSettings['from_name'] ?? $smtpSettings['smtp_from_name'] ?? 'Gridbase Bills';

        // Normalize encryption: treat empty strings and 'none' as null
        if (empty($encryption) || $encryption === 'none' || $encryption === 'null') {
            $encryption = null;
        }

        // For localhost/127.0.0.1 never use encryption (cPanel local SMTP doesn't support it)
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $encryption = null;
        }

        // Normalize empty credentials to null
        if (empty($username)) $username = null;
        if (empty($password)) $password = null;

        // Determine EHLO domain from the app URL
        $ehloDomain = parse_url((string) config('app.url', 'https://bills.gridbase.com.do'), PHP_URL_HOST) ?: 'bills.gridbase.com.do';

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.timeout' => 30,
            'mail.mailers.smtp.local_domain' => $ehloDomain,
            'mail.mailers.smtp.stream' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
            'mail.from.address' => $fromEmail,
            'mail.from.name' => $fromName,
        ]);

        // CRITICAL: Force Laravel to completely rebuild the mail transport
        // This ensures the new config is actually used
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');

        // Force Symfony transport to pick up SSL stream options
        try {
            $transport = app('mailer')->getSymfonyTransport();
            if (method_exists($transport, 'getStream')) {
                $stream = $transport->getStream();
                if (method_exists($stream, 'setStreamOptions')) {
                    $stream->setStreamOptions([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ]
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Silently continue
        }

        Log::debug('SMTP Config Applied', [
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'username' => $username ? '***' : '(none)',
            'from' => $fromEmail,
        ]);
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
            Log::info("Attempting to send email", [
                'to' => $toEmail,
                'subject' => $subject,
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'smtp_encryption' => config('mail.mailers.smtp.encryption'),
            ]);

            Mail::html($htmlBody, function ($message) use ($toEmail, $toName, $subject, $attachPath, $attachName) {
                $message->to($toEmail, $toName)->subject($subject);
                if ($attachPath && file_exists($attachPath)) {
                    $message->attach($attachPath, ['as' => $attachName ?? basename($attachPath)]);
                }
            });

            Log::info("Email sent successfully to {$toEmail}");
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (\Exception $e) {
            Log::error("Email failed to {$toEmail}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
        }
    }
}
