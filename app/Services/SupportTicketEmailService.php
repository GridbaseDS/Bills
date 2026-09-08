<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class SupportTicketEmailService
{
    public const SENDER_EMAIL = 'billsticket@gridbase.com.do';
    public const SENDER_PASSWORD = 'SamDP_9903';
    public const SENDER_NAME = 'GridBase Bills Tickets';
    public const SMTP_HOST = 'mail.gridbase.com.do';
    public const SMTP_PORT = 465;
    public const SMTP_ENCRYPTION = 'ssl';
    public const TARGET_SUPPORT_EMAIL = 'soporte@gridbase.com.do';

    /**
     * Send notification for a newly created support ticket to soporte@gridbase.com.do.
     */
    public static function sendNewTicketNotification(SupportTicket $ticket, SupportTicketMessage $message, $user): bool
    {
        $domain = self::resolveDomain();
        $company = self::resolveCompany();
        $dateDR = self::resolveDateDR();
        $userRole = self::resolveUserRole($user);

        $subject = "[{$ticket->ticket_number}] [{$domain}] {$company['name']} - {$ticket->subject}";

        $html = self::buildHtmlTemplate([
            'title' => 'Nuevo Ticket de Soporte',
            'badge_type' => 'nuevo',
            'ticket' => $ticket,
            'message' => $message,
            'user' => $user,
            'user_role' => $userRole,
            'domain' => $domain,
            'company' => $company,
            'date_dr' => $dateDR,
        ]);

        return self::dispatchToSupport($subject, $html, $user->email, $user->name);
    }

    /**
     * Send notification for a user reply on an existing support ticket to soporte@gridbase.com.do.
     */
    public static function sendUserReplyNotification(SupportTicket $ticket, SupportTicketMessage $message, $user): bool
    {
        $domain = self::resolveDomain();
        $company = self::resolveCompany();
        $dateDR = self::resolveDateDR();
        $userRole = self::resolveUserRole($user);

        $subject = "Re: [{$ticket->ticket_number}] [{$domain}] {$company['name']} - {$ticket->subject}";

        $html = self::buildHtmlTemplate([
            'title' => 'Nueva Respuesta en Ticket de Soporte',
            'badge_type' => 'respuesta',
            'ticket' => $ticket,
            'message' => $message,
            'user' => $user,
            'user_role' => $userRole,
            'domain' => $domain,
            'company' => $company,
            'date_dr' => $dateDR,
        ]);

        return self::dispatchToSupport($subject, $html, $user->email, $user->name);
    }

    /**
     * Dispatch email strictly and exclusively to soporte@gridbase.com.do.
     */
    private static function dispatchToSupport(string $subject, string $html, string $replyToEmail, string $replyToName): bool
    {
        try {
            $host = env('SUPPORT_MAIL_HOST', self::SMTP_HOST);
            $port = (int) env('SUPPORT_MAIL_PORT', self::SMTP_PORT);
            $username = env('SUPPORT_MAIL_USERNAME', self::SENDER_EMAIL);
            $password = env('SUPPORT_MAIL_PASSWORD', self::SENDER_PASSWORD);
            $encryption = env('SUPPORT_MAIL_ENCRYPTION', self::SMTP_ENCRYPTION);
            $target = self::TARGET_SUPPORT_EMAIL;

            $scheme = ($encryption === 'ssl' || $port === 465) ? 'smtps' : 'smtp';
            $dsn = sprintf(
                '%s://%s:%s@%s:%d',
                $scheme,
                urlencode($username),
                urlencode($password),
                $host,
                $port
            );

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $email = (new Email())
                ->from(new Address($username, self::SENDER_NAME))
                ->to(new Address($target, 'Soporte GridBase'))
                ->replyTo(new Address($replyToEmail, $replyToName ?: 'Cliente Bills'))
                ->subject($subject)
                ->html($html);

            $mailer->send($email);

            Log::info("Support ticket email sent via dedicated transport to {$target} for subject: {$subject}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send support ticket email via dedicated transport: " . $e->getMessage(), [
                'exception' => $e,
                'target' => self::TARGET_SUPPORT_EMAIL,
                'sender' => self::SENDER_EMAIL,
            ]);
            return false;
        }
    }

    /**
     * Resolve the active domain or distribution for this Bills instance.
     */
    public static function resolveDomain(): string
    {
        $domain = '';
        if (function_exists('request') && request()) {
            $domain = request()->getHost();
        }
        if (empty($domain) || $domain === 'localhost' || $domain === '127.0.0.1') {
            $appUrl = config('app.url');
            if ($appUrl) {
                $parsed = parse_url($appUrl, PHP_URL_HOST);
                if ($parsed) {
                    $domain = $parsed;
                }
            }
        }
        return $domain ?: 'gridbase.com.do';
    }

    /**
     * Resolve the base URL for generating links to the Bills instance.
     */
    public static function resolveBaseUrl(): string
    {
        if (function_exists('request') && request()) {
            $schemeAndHost = request()->getSchemeAndHttpHost();
            if ($schemeAndHost && !str_contains($schemeAndHost, 'localhost') && !str_contains($schemeAndHost, '127.0.0.1')) {
                return rtrim($schemeAndHost, '/');
            }
        }
        return rtrim((string) config('app.url', 'https://gridbase.com.do'), '/');
    }

    /**
     * Resolve company details from Settings.
     */
    public static function resolveCompany(): array
    {
        try {
            $settings = Setting::getAll();
            $name = trim($settings['company_name'] ?? '') ?: 'Gridbase Bills';
            $taxId = trim($settings['tax_id'] ?? '') ?: 'N/D';
            $phone = trim($settings['company_phone'] ?? '') ?: '';
            $email = trim($settings['company_email'] ?? '') ?: '';

            return [
                'name' => $name,
                'tax_id' => $taxId,
                'phone' => $phone,
                'email' => $email,
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'Gridbase Bills',
                'tax_id' => 'N/D',
                'phone' => '',
                'email' => '',
            ];
        }
    }

    /**
     * Format current date and time in Dominican Republic timezone (America/Santo_Domingo).
     */
    public static function resolveDateDR(): string
    {
        return now('America/Santo_Domingo')->format('d/m/Y h:i:s A') . ' (Hora Rep. Dominicana)';
    }

    /**
     * Format user role in friendly Spanish title.
     */
    public static function resolveUserRole($user): string
    {
        $role = strtolower(trim($user->role ?? 'usuario'));
        $map = [
            'admin' => 'Administrador',
            'gerente' => 'Gerente',
            'cajero' => 'Cajero',
            'vendedor' => 'Vendedor',
            'soporte' => 'Soporte Técnico',
            'usuario' => 'Usuario del Sistema',
        ];

        return $map[$role] ?? ucfirst($role);
    }

    /**
     * Generate responsive HTML email template for GridBase Support.
     */
    private static function buildHtmlTemplate(array $data): string
    {
        $ticket = $data['ticket'];
        $message = $data['message'];
        $user = $data['user'];
        $userRole = $data['user_role'];
        $domain = $data['domain'];
        $company = $data['company'];
        $dateDR = $data['date_dr'];
        $title = $data['title'];
        $isNew = ($data['badge_type'] ?? '') === 'nuevo';

        $baseUrl = self::resolveBaseUrl();
        $ticketUrl = "{$baseUrl}/#soporte/{$ticket->id}";

        $badgeColor = $isNew ? '#00a460' : '#2563eb';
        $badgeBg = $isNew ? '#ecfdf5' : '#eff6ff';
        $badgeText = $isNew ? 'NUEVO TICKET' : 'RESPUESTA DE CLIENTE';

        $priorityColors = [
            'urgente' => ['bg' => '#fef2f2', 'text' => '#b91c1c'],
            'alta' => ['bg' => '#fff7ed', 'text' => '#c2410c'],
            'media' => ['bg' => '#fefce8', 'text' => '#a16207'],
            'baja' => ['bg' => '#f0fdf4', 'text' => '#15803d'],
        ];
        $prio = strtolower($ticket->priority ?? 'media');
        $prioStyle = $priorityColors[$prio] ?? ['bg' => '#f1f5f9', 'text' => '#475569'];

        $safeMessage = nl2br(htmlspecialchars($message->message));

        return "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
</head>
<body style='margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;color:#1e293b;'>
    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f1f5f9;padding:28px 12px;'>
        <tr>
            <td align='center'>
                <table width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width:620px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.06),0 2px 4px -2px rgba(0,0,0,0.04);border:1px solid #e2e8f0;'>
                    
                    <!-- Header -->
                    <tr>
                        <td style='background:linear-gradient(135deg, #0b484c 0%, #062729 100%);padding:26px 30px;color:#ffffff;border-bottom:3px solid #00a460;'>
                            <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                                <tr>
                                    <td>
                                        <div style='font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#5eead4;margin-bottom:6px;'>
                                            GridBase Bills - Centro de Soporte
                                        </div>
                                        <h1 style='margin:0;font-size:21px;font-weight:700;color:#ffffff;line-height:1.3;'>
                                            {$title}
                                        </h1>
                                    </td>
                                    <td align='right' style='vertical-align:middle;'>
                                        <span style='background:{$badgeBg};color:{$badgeColor};font-size:11px;font-weight:800;padding:6px 12px;border-radius:20px;letter-spacing:0.5px;display:inline-block;white-space:nowrap;border:1px solid {$badgeColor};'>
                                            {$badgeText}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style='padding:28px 30px 20px;'>
                            
                            <!-- Ticket Identifiers Banner -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:24px;'>
                                <tr>
                                    <td style='padding:14px 18px;'>
                                        <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                                            <tr>
                                                <td>
                                                    <span style='font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;'>Ticket No.</span>
                                                    <div style='font-size:18px;font-weight:800;color:#0f172a;font-family:\"JetBrains Mono\",monospace;margin-top:2px;'>{$ticket->ticket_number}</div>
                                                </td>
                                                <td align='right'>
                                                    <span style='background:{$prioStyle['bg']};color:{$prioStyle['text']};font-size:11px;font-weight:700;padding:4px 10px;border-radius:6px;text-transform:uppercase;display:inline-block;'>
                                                        Prioridad: " . htmlspecialchars($ticket->priority) . "
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Technical Specs Table -->
                            <div style='font-size:12px;font-weight:700;color:#0f172a;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid #f1f5f9;'>
                                Especificaciones del Incidente
                            </div>

                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='font-size:13px;line-height:1.6;margin-bottom:24px;border-collapse:separate;border-spacing:0 8px;'>
                                <tr>
                                    <td style='width:160px;color:#64748b;font-weight:600;vertical-align:top;padding:4px 0;'>
                                        Distribución de Bills:
                                    </td>
                                    <td style='color:#0f172a;font-weight:700;vertical-align:top;padding:4px 0;'>
                                        <a href='https://{$domain}' target='_blank' style='color:#00a460;text-decoration:none;'>{$domain}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='color:#64748b;font-weight:600;vertical-align:top;padding:4px 0;'>
                                        Empresa:
                                    </td>
                                    <td style='color:#0f172a;vertical-align:top;padding:4px 0;'>
                                        <strong>" . htmlspecialchars($company['name']) . "</strong>
                                        <span style='color:#64748b;font-size:12px;margin-left:6px;'>(RNC/Cédula: " . htmlspecialchars($company['tax_id']) . ")</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='color:#64748b;font-weight:600;vertical-align:top;padding:4px 0;'>
                                        Usuario Remitente:
                                    </td>
                                    <td style='color:#0f172a;vertical-align:top;padding:4px 0;'>
                                        <strong>" . htmlspecialchars($user->name) . "</strong> 
                                        <span style='color:#64748b;'>(&lt;" . htmlspecialchars($user->email) . "&gt;)</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='color:#64748b;font-weight:600;vertical-align:top;padding:4px 0;'>
                                        Rol en Bills:
                                    </td>
                                    <td style='color:#0f172a;vertical-align:top;padding:4px 0;'>
                                        <span style='background:#f1f5f9;color:#334155;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;'>
                                            {$userRole}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='color:#64748b;font-weight:600;vertical-align:top;padding:4px 0;'>
                                        Fecha y Hora (REP DOM):
                                    </td>
                                    <td style='color:#0f172a;font-weight:600;vertical-align:top;padding:4px 0;'>
                                        {$dateDR}
                                    </td>
                                </tr>
                                <tr>
                                    <td style='color:#64748b;font-weight:600;vertical-align:top;padding:4px 0;'>
                                        Categoría:
                                    </td>
                                    <td style='color:#0f172a;vertical-align:top;padding:4px 0;text-transform:capitalize;'>
                                        " . htmlspecialchars($ticket->category) . "
                                    </td>
                                </tr>
                                <tr>
                                    <td style='color:#64748b;font-weight:600;vertical-align:top;padding:4px 0;'>
                                        Asunto:
                                    </td>
                                    <td style='color:#0f172a;font-weight:700;vertical-align:top;padding:4px 0;'>
                                        " . htmlspecialchars($ticket->subject) . "
                                    </td>
                                </tr>
                            </table>

                            <!-- Message Callout Box -->
                            <div style='background-color:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #00a460;border-radius:6px;padding:18px 20px;margin-bottom:24px;'>
                                <div style='font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;'>
                                    Detalle del Mensaje Reportado:
                                </div>
                                <div style='font-size:14px;line-height:1.65;color:#1e293b;white-space:pre-wrap;'>{$safeMessage}</div>
                            </div>

                            <!-- Action Button -->
                            <div style='text-align:center;margin:28px 0 16px;'>
                                <a href='{$ticketUrl}' target='_blank' style='background-color:#00a460;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 28px;border-radius:8px;display:inline-block;box-shadow:0 2px 4px rgba(0,164,96,0.25);'>
                                    Ver Ticket en Bills &rarr;
                                </a>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style='background-color:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 30px;color:#64748b;font-size:12px;line-height:1.6;text-align:center;'>
                            <div style='margin-bottom:8px;'>
                                Este correo fue generado automáticamente por la distribución <strong>{$domain}</strong> de <strong>GridBase Bills</strong> a través de la cuenta técnica exclusiva <code style='font-family:monospace;background:#e2e8f0;padding:2px 5px;border-radius:3px;'>billsticket@gridbase.com.do</code>.
                            </div>
                            <div style='color:#94a3b8;font-size:11px;'>
                                Puede responder directamente a este correo para enviar su respuesta al cliente (" . htmlspecialchars($user->email) . ").
                            </div>
                            <div style='margin-top:12px;color:#94a3b8;font-size:11px;'>
                                Copyright &copy; " . date('Y') . " <strong>GridBase Digital Solutions</strong>. Todos los derechos reservados.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
    }
}
