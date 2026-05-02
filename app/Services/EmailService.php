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
        // Generate payment link if not exists
        if (!$invoice->isPaymentTokenValid()) {
            $invoice->generatePaymentToken();
        }
        
        $paymentUrl = $invoice->getPaymentUrl();
        
        $subject = "Factura {$invoice->invoice_number} de {$this->config['from_name']}";
        
        $body = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Hola {$invoice->client->contact_name},</h2>
            
            <p>Adjuntamos la factura <strong>{$invoice->invoice_number}</strong> por el monto de {$invoice->currency} " . number_format($invoice->total, 2) . ".</p>
            
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <p style='margin: 0 0 5px 0; color: #666; font-size: 13px;'>Balance Pendiente</p>
                <p style='margin: 0; font-size: 24px; font-weight: bold; color: #667eea;'>{$invoice->currency} " . number_format($invoice->getRemainingBalance(), 2) . "</p>
                <p style='margin: 10px 0 0 0; color: #666; font-size: 13px;'>Fecha de Vencimiento: <strong>{$invoice->due_date->format('d/m/Y')}</strong></p>
            </div>
            
            <p>Puede pagar esta factura de forma segura usando el siguiente enlace:</p>
            
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$paymentUrl}' 
                   style='background: #667eea; color: white; padding: 14px 28px; 
                          text-decoration: none; border-radius: 6px; display: inline-block; 
                          font-weight: bold; font-size: 16px;'>
                    💳 Pagar Ahora
                </a>
            </p>
            
            <p style='font-size: 13px; color: #666;'>
                <strong>Nota:</strong> Este enlace es válido hasta el {$invoice->payment_token_expires_at->format('d/m/Y')}.
                También puede pagar ingresando el código de factura en nuestro portal de pagos.
            </p>
            
            <p>Una copia en PDF de la factura ha sido adjuntada a este correo.</p>
            
            <p>Si tiene alguna pregunta, por favor responda a este correo.</p>
            
            <p style='margin-top: 30px;'>Gracias,<br><strong>{$this->config['from_name']}</strong></p>
        </div>
        ";

        return $this->send($invoice->client->email, $invoice->client->contact_name, $subject, $body, $pdfPath, "Factura-{$invoice->invoice_number}.pdf");
    }

    public function sendQuote($quote, string $pdfPath): array
    {
        $subject = "Cotizacion {$quote->quote_number} de {$this->config['from_name']}";
        $body = "<p>Hola {$quote->client->contact_name},</p><p>Adjuntamos la cotizacion {$quote->quote_number}.</p>";

        return $this->send($quote->client->email, $quote->client->contact_name, $subject, $body, $pdfPath, "Cotizacion-{$quote->quote_number}.pdf");
    }

    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation($invoice, $payment): array
    {
        $subject = "✓ Pago Recibido - Factura {$invoice->invoice_number}";
        
        $statusText = $invoice->status === 'paid' ? 'PAGADA COMPLETAMENTE' : 'PAGO PARCIAL APLICADO';
        $statusColor = $invoice->status === 'paid' ? '#10B981' : '#F59E0B';
        
        $body = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2>¡Pago Recibido!</h2>
            
            <p>Hola {$invoice->client->contact_name},</p>
            
            <p>Hemos recibido exitosamente su pago para la factura <strong>{$invoice->invoice_number}</strong>.</p>
            
            <div style='background: #F0FDF4; padding: 20px; border-left: 4px solid #10B981; border-radius: 8px; margin: 20px 0;'>
                <p style='margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #065F46;'>Detalles del Pago</p>
                <table style='width: 100%; font-size: 14px;'>
                    <tr>
                        <td style='padding: 5px 0; color: #6B7280;'>Monto Pagado:</td>
                        <td style='padding: 5px 0; text-align: right; font-weight: bold;'>{$invoice->currency} " . number_format($payment->amount, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px 0; color: #6B7280;'>Método de Pago:</td>
                        <td style='padding: 5px 0; text-align: right; font-weight: bold;'>" . strtoupper($payment->payment_method) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px 0; color: #6B7280;'>Referencia:</td>
                        <td style='padding: 5px 0; text-align: right; font-family: monospace;'>" . $payment->reference . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px 0; color: #6B7280;'>Fecha:</td>
                        <td style='padding: 5px 0; text-align: right;'>" . $payment->payment_date->format('d/m/Y H:i') . "</td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #F9FAFB; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <p style='margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #111827;'>Estado de la Factura</p>
                <table style='width: 100%; font-size: 14px;'>
                    <tr>
                        <td style='padding: 5px 0; color: #6B7280;'>Total Factura:</td>
                        <td style='padding: 5px 0; text-align: right; font-weight: bold;'>{$invoice->currency} " . number_format($invoice->total, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px 0; color: #6B7280;'>Total Pagado:</td>
                        <td style='padding: 5px 0; text-align: right; font-weight: bold; color: #10B981;'>{$invoice->currency} " . number_format($invoice->amount_paid, 2) . "</td>
                    </tr>
                    <tr style='border-top: 2px solid #E5E7EB;'>
                        <td style='padding: 10px 0 5px 0; color: #111827; font-weight: bold;'>Balance Restante:</td>
                        <td style='padding: 10px 0 5px 0; text-align: right; font-size: 18px; font-weight: bold; color: " . ($invoice->getRemainingBalance() > 0 ? '#F59E0B' : '#10B981') . ";'>{$invoice->currency} " . number_format($invoice->getRemainingBalance(), 2) . "</td>
                    </tr>
                </table>
            </div>
            
            <div style='background: {$statusColor}; color: white; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 16px; margin: 20px 0;'>
                {$statusText}
            </div>
            
            " . ($invoice->status !== 'paid' ? "<p>El balance restante de {$invoice->currency} " . number_format($invoice->getRemainingBalance(), 2) . " puede ser pagado cuando desee.</p>" : "<p style='color: #10B981; font-weight: bold;'>✓ Esta factura ha sido pagada en su totalidad. ¡Gracias!</p>") . "
            
            <p style='font-size: 13px; color: #6B7280; margin-top: 30px;'>
                Este es un email de confirmación automática. Si tiene alguna pregunta sobre este pago, 
                por favor responda a este correo.
            </p>
            
            <p style='margin-top: 30px;'>Gracias por su pago,<br><strong>{$this->config['from_name']}</strong></p>
        </div>
        ";

        return $this->send($invoice->client->email, $invoice->client->contact_name, $subject, $body);
    }
    
    public function sendReminder($invoice): array
    {
        // Generate payment link if not exists
        if (!$invoice->isPaymentTokenValid()) {
            $invoice->generatePaymentToken();
        }
        
        $paymentUrl = $invoice->getPaymentUrl();
        
        $subject = "Recordatorio de Pago: Factura {$invoice->invoice_number}";
        
        $body = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Hola {$invoice->client->contact_name},</h2>
            
            <p>Este es un recordatorio de pago para la factura <strong>{$invoice->invoice_number}</strong>.</p>
            
            <div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                <p style='margin: 0 0 5px 0; color: #856404; font-size: 13px;'>Balance Pendiente</p>
                <p style='margin: 0; font-size: 24px; font-weight: bold; color: #856404;'>{$invoice->currency} " . number_format($invoice->getRemainingBalance(), 2) . "</p>
                <p style='margin: 10px 0 0 0; color: #856404; font-size: 13px;'>Fecha de Vencimiento: <strong>{$invoice->due_date->format('d/m/Y')}</strong></p>
            </div>
            
            <p>Puede realizar el pago de forma rápida y segura:</p>
            
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$paymentUrl}' 
                   style='background: #28a745; color: white; padding: 14px 28px; 
                          text-decoration: none; border-radius: 6px; display: inline-block; 
                          font-weight: bold; font-size: 16px;'>
                    💳 Pagar Ahora
                </a>
            </p>
            
            <p>Si ya realizó el pago, por favor ignore este mensaje.</p>
            
            <p style='margin-top: 30px;'>Gracias por su atención,<br><strong>{$this->config['from_name']}</strong></p>
        </div>
        ";

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
