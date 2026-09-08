<?php
namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    private array $config;

    public function __construct()
    {
        $settings = Setting::getAll();
        
        $fromEmail = trim($settings['smtp_from_email'] ?? '') ?: (trim($settings['company_email'] ?? '') ?: 'bills@gridbase.com.do');
        $fromName  = trim($settings['smtp_from_name'] ?? '') ?: (trim($settings['company_name'] ?? '') ?: 'Gridbase Bills');

        $this->config = [
            'host'       => trim($settings['smtp_host'] ?? '') ?: 'mail.gridbase.com.do',
            'port'       => (int)($settings['smtp_port'] ?? 465) ?: 465,
            'username'   => $settings['smtp_username'] ?? '',
            'password'   => $settings['smtp_password'] ?? '',
            'encryption' => $settings['smtp_encryption'] ?? 'ssl',
            'from_name'  => $fromName,
            'from_email' => $fromEmail,
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
        $host = trim($smtpSettings['host'] ?? $smtpSettings['smtp_host'] ?? '');
        $port = (int)($smtpSettings['port'] ?? $smtpSettings['smtp_port'] ?? 0);
        $encryption = $smtpSettings['encryption'] ?? $smtpSettings['smtp_encryption'] ?? null;
        $username = $smtpSettings['username'] ?? $smtpSettings['smtp_username'] ?? null;
        $password = $smtpSettings['password'] ?? $smtpSettings['smtp_password'] ?? null;

        // If no custom SMTP host is set in Settings, use the pre-configured Bills system SMTP
        if (empty($host) || $host === 'localhost' || $host === '127.0.0.1') {
            $host = env('MAIL_HOST', 'mail.gridbase.com.do');
            $port = (int) env('MAIL_PORT', 465);
            $encryption = env('MAIL_ENCRYPTION', 'ssl');
            $username = env('MAIL_USERNAME', 'bills@gridbase.com.do');
            $password = env('MAIL_PASSWORD', 'SamDP_9903');
        }

        // Resolve From address with safe fallbacks (never allow empty string)
        $fromEmail = trim($smtpSettings['from_email'] ?? $smtpSettings['smtp_from_email'] ?? '');
        if (empty($fromEmail)) {
            $fromEmail = trim($smtpSettings['company_email'] ?? '') ?: (config('mail.from.address') ?: 'bills@gridbase.com.do');
        }
        if (empty($fromEmail)) {
            $fromEmail = 'bills@gridbase.com.do';
        }

        // Resolve From name with safe fallbacks
        $fromName = trim($smtpSettings['from_name'] ?? $smtpSettings['smtp_from_name'] ?? '');
        if (empty($fromName)) {
            $fromName = trim($smtpSettings['company_name'] ?? '') ?: (config('mail.from.name') ?: 'Gridbase Bills');
        }
        if (empty($fromName)) {
            $fromName = 'Gridbase Bills';
        }

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

        // Force Symfony transport to pick up SSL stream options and handle auto-tls
        try {
            $transport = app('mailer')->getSymfonyTransport();
            if (empty($encryption) && method_exists($transport, 'setAutoTls')) {
                $transport->setAutoTls(false);
            }
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

            $fromAddress = config('mail.from.address') ?: 'bills@gridbase.com.do';
            $fromName    = config('mail.from.name') ?: 'Gridbase Bills';

            Mail::html($htmlBody, function ($message) use ($toEmail, $toName, $subject, $attachPath, $attachName, $fromAddress, $fromName) {
                $message->from($fromAddress, $fromName)
                        ->to($toEmail, $toName)
                        ->subject($subject);
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

    /**
     * Send welcome email with credentials to newly created user.
     */
    public static function sendUserWelcomeEmail($user, string $plainPassword): array
    {
        return self::dispatchUserCredentialEmail(
            $user,
            $plainPassword,
            'welcome',
            'Bienvenido a ' . (Setting::get('company_name') ?: 'GridBase Bills') . ' - Credenciales de Acceso'
        );
    }

    /**
     * Send password reset notification email to user.
     */
    public static function sendUserPasswordReset($user, string $newPassword): array
    {
        return self::dispatchUserCredentialEmail(
            $user,
            $newPassword,
            'reset',
            'Credenciales de Acceso Actualizadas - ' . (Setting::get('company_name') ?: 'GridBase Bills')
        );
    }

    /**
     * Dispatch user credentials email using a dedicated transport.
     */
    private static function dispatchUserCredentialEmail($user, string $password, string $type, string $subject): array
    {
        try {
            $settings = Setting::getAll();

            $host = trim($settings['smtp_host'] ?? '') ?: '';
            $port = (int)($settings['smtp_port'] ?? 0) ?: 0;
            $encryption = $settings['smtp_encryption'] ?? null;
            $username = $settings['smtp_username'] ?? null;
            $password_smtp = $settings['smtp_password'] ?? null;

            // Fallback to pre-configured GridBase Bills SMTP if no valid custom SMTP is set
            if (empty($host) || $host === 'localhost' || $host === '127.0.0.1') {
                $host = env('MAIL_HOST', 'mail.gridbase.com.do');
                $port = (int) env('MAIL_PORT', 465);
                $encryption = env('MAIL_ENCRYPTION', 'ssl');
                $username = env('MAIL_USERNAME', 'bills@gridbase.com.do');
                $password_smtp = env('MAIL_PASSWORD', 'SamDP_9903');
            }

            $fromEmail = trim($settings['smtp_from_email'] ?? '') ?: (trim($settings['company_email'] ?? '') ?: ($username ?: 'bills@gridbase.com.do'));
            $fromName = trim($settings['smtp_from_name'] ?? '') ?: (trim($settings['company_name'] ?? '') ?: 'GridBase Bills');

            $scheme = ($encryption === 'ssl' || $port === 465) ? 'smtps' : 'smtp';
            $dsn = $username 
                ? sprintf('%s://%s:%s@%s:%d', $scheme, urlencode($username), urlencode($password_smtp), $host, $port) 
                : sprintf('smtp://%s:%d', $host, $port);

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $domain = parse_url(config('app.url', url('/')), PHP_URL_HOST) ?: request()->getHost();
            $company = $settings['company_name'] ?? 'GridBase Bills';
            $dateDR = now('America/Santo_Domingo')->format('d/m/Y h:i A');
            $loginUrl = url('/');

            $roleNames = [
                'admin' => 'Administrador',
                'gerente' => 'Gerente Operativo',
                'contador' => 'Contador',
                'vendedor' => 'Vendedor / Facturación',
            ];
            $roleName = $roleNames[$user->role] ?? ucfirst($user->role);

            $badgeLabel = $type === 'welcome' ? 'NUEVO USUARIO' : 'SEGURIDAD Y ACCESO';
            $introText = $type === 'welcome'
                ? "Has sido registrado como usuario en la plataforma de facturaci&oacute;n electr&oacute;nica y gesti&oacute;n empresarial de <strong>{$company}</strong>. A continuaci&oacute;n encontrar&aacute;s tus datos para iniciar sesi&oacute;n:"
                : "Un administrador ha restablecido tu contrase&ntilde;a de acceso a la plataforma de <strong>{$company}</strong>. A continuaci&oacute;n encontrar&aacute;s tus nuevas credenciales:";

            $html = "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='utf-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>{$subject}</title>
            </head>
            <body style='margin:0;padding:24px 12px;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;color:#1e293b;'>
                <div style='max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.03);'>
                    
                    <!-- Top Accent Bar -->
                    <div style='height:4px;background:#00a460;'></div>

                    <!-- Header -->
                    <div style='padding:24px 28px 20px;border-bottom:1px solid #f1f5f9;'>
                        <table style='width:100%;border-collapse:collapse;'>
                            <tr>
                                <td>
                                    <span style='font-size:18px;font-weight:800;letter-spacing:-0.5px;color:#0f172a;'>GridBase <span style='color:#00a460;'>Bills</span></span>
                                    <div style='font-size:11px;color:#64748b;margin-top:2px;'>{$domain}</div>
                                </td>
                                <td style='text-align:right;'>
                                    <span style='background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;letter-spacing:0.5px;display:inline-block;'>
                                        {$badgeLabel}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Body -->
                    <div style='padding:28px;'>
                        <h2 style='margin:0 0 10px;font-size:20px;font-weight:700;color:#0f172a;letter-spacing:-0.3px;'>
                            Hola {$user->name},
                        </h2>
                        <p style='margin:0 0 20px;font-size:14px;line-height:1.6;color:#475569;'>
                            {$introText}
                        </p>

                        <!-- Credentials Card -->
                        <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin:0 0 24px;'>
                            <div style='margin-bottom:12px;'>
                                <div style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:3px;'>Usuario / Correo Electr&oacute;nico</div>
                                <div style='font-size:14px;font-weight:600;color:#0f172a;font-family:monospace;'>{$user->email}</div>
                            </div>

                            <div style='margin-bottom:12px;'>
                                <div style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:3px;'>Contrase&ntilde;a Temporal</div>
                                <code style='display:inline-block;background:#0f172a;color:#ffffff;font-size:15px;font-weight:700;padding:6px 14px;border-radius:6px;font-family:monospace;letter-spacing:1px;'>{$password}</code>
                            </div>

                            <div>
                                <div style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:3px;'>Rol y Privilegios Asignados</div>
                                <div style='font-size:13px;font-weight:700;color:#00a460;'>{$roleName}</div>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <div style='text-align:center;margin:28px 0 20px;'>
                            <a href='{$loginUrl}' target='_blank' style='background:#00a460;color:#ffffff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;'>
                                Iniciar Sesi&oacute;n en Bills &rarr;
                            </a>
                        </div>

                        <p style='font-size:12px;color:#94a3b8;line-height:1.5;margin:0;text-align:center;'>
                            Por motivos de seguridad, te sugerimos iniciar sesi&oacute;n y cambiar esta contrase&ntilde;a desde la configuraci&oacute;n de tu perfil.
                        </p>
                    </div>

                    <!-- Footer -->
                    <div style='background:#f8fafc;padding:16px 28px;border-top:1px solid #e2e8f0;font-size:11.5px;color:#64748b;line-height:1.5;'>
                        <table style='width:100%;border-collapse:collapse;'>
                            <tr>
                                <td>Empresa: <strong>{$company}</strong></td>
                                <td style='text-align:right;'>Fecha: {$dateDR}</td>
                            </tr>
                        </table>
                        <div style='margin-top:8px;text-align:center;color:#94a3b8;font-size:11px;'>
                            &copy; " . date('Y') . " GridBase Digital Solutions. Todos los derechos reservados.
                        </div>
                    </div>

                </div>
            </body>
            </html>
            ";

            $email = (new Email())
                ->from(new Address($username ?: $fromEmail, $fromName))
                ->to(new Address($user->email, $user->name))
                ->subject($subject)
                ->html($html);

            $mailer->send($email);

            Log::info("User credential email ({$type}) dispatched successfully to {$user->email}");
            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch user credential email ({$type}) to {$user->email}: " . $e->getMessage(), [
                'exception' => $e
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

