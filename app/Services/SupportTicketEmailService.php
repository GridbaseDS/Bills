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

        return self::dispatchToSupport($subject, [
            'title' => 'Nuevo Ticket de Soporte',
            'badge_type' => 'nuevo',
            'ticket' => $ticket,
            'message' => $message,
            'user' => $user,
            'user_role' => $userRole,
            'domain' => $domain,
            'company' => $company,
            'date_dr' => $dateDR,
        ], $user->email, $user->name);
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

        return self::dispatchToSupport($subject, [
            'title' => 'Nueva Respuesta en Ticket',
            'badge_type' => 'respuesta',
            'ticket' => $ticket,
            'message' => $message,
            'user' => $user,
            'user_role' => $userRole,
            'domain' => $domain,
            'company' => $company,
            'date_dr' => $dateDR,
        ], $user->email, $user->name);
    }

    /**
     * Dispatch email strictly and exclusively to soporte@gridbase.com.do using the dedicated Bills transport.
     */
    private static function dispatchToSupport(string $subject, array $templateData, string $replyToEmail, string $replyToName): bool
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
                ->subject($subject);

            // Use crisp dark-text logo on clean light background
            $logoPath = public_path('assets/img/bills-logo.png');
            $logoSrc = "https://{$templateData['domain']}/assets/img/bills-logo.png";
            if (file_exists($logoPath)) {
                $email->embedFromPath($logoPath, 'bills_logo', 'image/png');
                $logoSrc = 'cid:bills_logo';
            }

            $templateData['logo_src'] = $logoSrc;
            $html = self::buildHtmlTemplate($templateData);

            $email->html($html);

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
     * Generate clean, professional HTML email template matching modern SaaS standards and Bills branding.
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
        $logoSrc = $data['logo_src'] ?? "https://{$domain}/assets/img/bills-logo.png";
        $isNew = ($data['badge_type'] ?? '') === 'nuevo';

        $baseUrl = self::resolveBaseUrl();
        $ticketUrl = "{$baseUrl}/#soporte/{$ticket->id}";

        // Priority colors
        $priorityColors = [
            'urgente' => ['bg' => '#FEF2F2', 'text' => '#991B1B', 'border' => '#F87171'],
            'alta'    => ['bg' => '#FFF7ED', 'text' => '#9A3412', 'border' => '#FDBA74'],
            'media'   => ['bg' => '#FEFCE8', 'text' => '#854D0E', 'border' => '#FDE047'],
            'baja'    => ['bg' => '#F0FDF4', 'text' => '#166534', 'border' => '#86EFAC'],
        ];
        $prio = strtolower($ticket->priority ?? 'media');
        $prioStyle = $priorityColors[$prio] ?? ['bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#CBD5E1'];

        // User avatar initials
        $initials = '';
        $nameParts = explode(' ', trim($user->name ?? 'U'));
        foreach (array_slice($nameParts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        $initials = $initials ?: 'U';

        $safeMessage = nl2br(htmlspecialchars($message->message));

        return "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>[{$ticket->ticket_number}] {$ticket->subject}</title>
</head>
<body style='margin:0;padding:0;background-color:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;color:#1E293B;'>
    
    <!-- Outer Wrapper Table -->
    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#F8FAFC;padding:32px 12px;'>
        <tr>
            <td align='center'>
                
                <!-- Main Container Card -->
                <table width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width:600px;background-color:#FFFFFF;border-radius:12px;border:1px solid #E2E8F0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05),0 2px 4px -2px rgba(0,0,0,0.03);overflow:hidden;'>
                    
                    <!-- Clean Top Header -->
                    <tr>
                        <td style='padding:24px 30px 20px;border-bottom:1px solid #E2E8F0;'>
                            <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                                <tr>
                                    <!-- Bills Logo & System Name -->
                                    <td style='vertical-align:middle;'>
                                        <table cellpadding='0' cellspacing='0' border='0'>
                                            <tr>
                                                <td style='vertical-align:middle;padding-right:12px;'>
                                                    <a href='https://{$domain}' target='_blank' style='text-decoration:none;display:block;'>
                                                        <img src='{$logoSrc}' alt='Bills' width='38' style='display:block;width:38px;height:auto;border:0;' />
                                                    </a>
                                                </td>
                                                <td style='vertical-align:middle;'>
                                                    <div style='font-size:17px;font-weight:800;color:#0F172A;letter-spacing:-0.3px;line-height:1.2;'>
                                                        GridBase Bills
                                                    </div>
                                                    <div style='font-size:11.5px;font-weight:600;color:#64748B;letter-spacing:0.2px;margin-top:2px;'>
                                                        Centro de Soporte Técnico
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Ticket ID Pill Badge -->
                                    <td align='right' style='vertical-align:middle;'>
                                        <span style='font-family:\"JetBrains Mono\",monospace,Consolas;font-size:13px;font-weight:800;color:#0F172A;background:#F1F5F9;border:1px solid #E2E8F0;padding:6px 12px;border-radius:6px;display:inline-block;letter-spacing:0.5px;'>
                                            {$ticket->ticket_number}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Card Body -->
                    <tr>
                        <td style='padding:28px 30px 32px;'>
                            
                            <!-- Status & Priority Row -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom:12px;'>
                                <tr>
                                    <td>
                                        " . ($isNew ? "
                                        <span style='background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;font-size:11px;font-weight:800;padding:4px 10px;border-radius:9999px;letter-spacing:0.5px;text-transform:uppercase;display:inline-block;'>
                                            ● Nuevo Ticket
                                        </span>
                                        " : "
                                        <span style='background:#EFF6FF;color:#2563EB;border:1px solid #BFDBFE;font-size:11px;font-weight:800;padding:4px 10px;border-radius:9999px;letter-spacing:0.5px;text-transform:uppercase;display:inline-block;'>
                                            ● Respuesta de Cliente
                                        </span>
                                        ") . "
                                        
                                        <span style='background:{$prioStyle['bg']};color:{$prioStyle['text']};border:1px solid {$prioStyle['border']};font-size:11px;font-weight:800;padding:4px 10px;border-radius:9999px;letter-spacing:0.5px;text-transform:uppercase;display:inline-block;margin-left:6px;'>
                                            Prioridad: " . htmlspecialchars($ticket->priority) . "
                                        </span>

                                        <span style='background:#F8FAFC;color:#64748B;border:1px solid #E2E8F0;font-size:11px;font-weight:700;padding:4px 10px;border-radius:9999px;text-transform:capitalize;display:inline-block;margin-left:6px;'>
                                            " . htmlspecialchars($ticket->category) . "
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Subject Title -->
                            <h2 style='margin:12px 0 20px;font-size:19px;font-weight:800;color:#0F172A;line-height:1.35;letter-spacing:-0.2px;'>
                                " . htmlspecialchars($ticket->subject) . "
                            </h2>

                            <!-- User Message Box (Hero Content) -->
                            <div style='background-color:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #00A460;border-radius:8px;padding:20px;margin-bottom:26px;'>
                                
                                <!-- User Identity Row -->
                                <table cellpadding='0' cellspacing='0' border='0' style='margin-bottom:12px;'>
                                    <tr>
                                        <td style='vertical-align:middle;padding-right:10px;'>
                                            <div style='width:32px;height:32px;border-radius:50%;background:#00A460;color:#FFFFFF;font-weight:800;font-size:12px;line-height:32px;text-align:center;'>
                                                {$initials}
                                            </div>
                                        </td>
                                        <td style='vertical-align:middle;'>
                                            <div style='font-size:13.5px;font-weight:700;color:#0F172A;'>
                                                " . htmlspecialchars($user->name) . "
                                            </div>
                                            <div style='font-size:11.5px;color:#64748B;'>
                                                " . htmlspecialchars($user->email) . "
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Message Text -->
                                <div style='font-size:14.5px;line-height:1.65;color:#334155;white-space:pre-wrap;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>{$safeMessage}</div>
                            </div>

                            <!-- Primary Action CTA -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom:28px;'>
                                <tr>
                                    <td>
                                        <a href='{$ticketUrl}' target='_blank' style='background-color:#00A460;color:#FFFFFF;font-size:14px;font-weight:700;text-decoration:none;padding:12px 24px;border-radius:8px;display:inline-block;box-shadow:0 2px 4px rgba(0,164,96,0.25);letter-spacing:0.2px;'>
                                            Abrir Ticket en Bills &rarr;
                                        </a>

                                        <a href='mailto:" . htmlspecialchars($user->email) . "' style='background-color:#FFFFFF;color:#334155;border:1px solid #CBD5E1;font-size:13.5px;font-weight:600;text-decoration:none;padding:11px 18px;border-radius:8px;display:inline-block;margin-left:8px;'>
                                            Responder por Correo
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Origin & Incident Details Table -->
                            <div style='font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;'>
                                Información de Origen del Sistema
                            </div>

                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;line-height:1.55;border-collapse:collapse;'>
                                <tr>
                                    <td style='padding:10px 16px;border-bottom:1px solid #E2E8F0;color:#64748B;width:150px;font-weight:600;'>
                                        Distribución de Bills:
                                    </td>
                                    <td style='padding:10px 16px;border-bottom:1px solid #E2E8F0;color:#0F172A;font-weight:700;'>
                                        <a href='https://{$domain}' target='_blank' style='color:#00A460;text-decoration:none;'>
                                            {$domain} &rarr;
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding:10px 16px;border-bottom:1px solid #E2E8F0;color:#64748B;font-weight:600;'>
                                        Empresa Emisora:
                                    </td>
                                    <td style='padding:10px 16px;border-bottom:1px solid #E2E8F0;color:#0F172A;'>
                                        <strong>" . htmlspecialchars($company['name']) . "</strong>
                                        <span style='color:#64748B;margin-left:6px;'>(RNC: " . htmlspecialchars($company['tax_id']) . ")</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding:10px 16px;border-bottom:1px solid #E2E8F0;color:#64748B;font-weight:600;'>
                                        Usuario y Rol:
                                    </td>
                                    <td style='padding:10px 16px;border-bottom:1px solid #E2E8F0;color:#0F172A;'>
                                        <strong>" . htmlspecialchars($user->name) . "</strong>
                                        <span style='color:#64748B;'>(" . htmlspecialchars($user->email) . ")</span>
                                        &bull; <span style='background:#E2E8F0;color:#334155;font-size:11px;font-weight:600;padding:2px 7px;border-radius:4px;'>{$userRole}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding:10px 16px;color:#64748B;font-weight:600;'>
                                        Fecha y Hora (REP DOM):
                                    </td>
                                    <td style='padding:10px 16px;color:#0F172A;font-weight:600;'>
                                        {$dateDR}
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style='background-color:#F8FAFC;border-top:1px solid #E2E8F0;padding:20px 30px;color:#64748B;font-size:12px;line-height:1.6;text-align:center;'>
                            <div style='color:#334155;font-weight:600;margin-bottom:4px;'>
                                Canal Técnico de Soporte &bull; GridBase Bills
                            </div>
                            <div style='color:#64748B;font-size:11.5px;'>
                                Despachado automáticamente a <a href='mailto:soporte@gridbase.com.do' style='color:#00A460;text-decoration:none;font-weight:600;'>soporte@gridbase.com.do</a> vía <code style='font-family:monospace;background:#E2E8F0;padding:1px 5px;border-radius:3px;color:#0F172A;'>billsticket@gridbase.com.do</code>.
                            </div>
                            <div style='color:#94A3B8;font-size:11px;margin-top:4px;'>
                                Al responder directamente a este correo, el mensaje será enviado al cliente (" . htmlspecialchars($user->email) . ").
                            </div>
                            <div style='margin-top:12px;padding-top:12px;border-top:1px solid #E2E8F0;color:#94A3B8;font-size:11px;'>
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
