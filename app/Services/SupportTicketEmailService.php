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
            'title' => 'Nueva Respuesta en Ticket de Soporte',
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

            // Embed Bills logo for flawless offline/inbox display
            $logoPath = public_path('assets/img/bills-logo-white.png');
            $logoSrc = "https://{$templateData['domain']}/assets/img/bills-logo-white.png";
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
     * Generate responsive HTML email template styled to match the Bills application UI.
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
        $logoSrc = $data['logo_src'] ?? "https://{$domain}/assets/img/bills-logo-white.png";
        $isNew = ($data['badge_type'] ?? '') === 'nuevo';

        $baseUrl = self::resolveBaseUrl();
        $ticketUrl = "{$baseUrl}/#soporte/{$ticket->id}";

        // Priority badge styling matching Bills Design System
        $priorityColors = [
            'urgente' => ['bg' => 'rgba(239, 68, 68, 0.12)', 'text' => '#DC2626', 'border' => 'rgba(239, 68, 68, 0.28)'],
            'alta'    => ['bg' => 'rgba(249, 115, 22, 0.12)', 'text' => '#EA580C', 'border' => 'rgba(249, 115, 22, 0.28)'],
            'media'   => ['bg' => 'rgba(245, 158, 11, 0.12)', 'text' => '#D97706', 'border' => 'rgba(245, 158, 11, 0.28)'],
            'baja'    => ['bg' => 'rgba(16, 185, 129, 0.12)', 'text' => '#059669', 'border' => 'rgba(16, 185, 129, 0.28)'],
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
    <title>{$title} - GridBase Bills</title>
</head>
<body style='margin:0;padding:0;background-color:#0B131E;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;color:#1E293B;'>
    
    <!-- Outer Wrapper -->
    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#0B131E;padding:36px 12px;'>
        <tr>
            <td align='center'>
                
                <!-- Main Container Card -->
                <table width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width:640px;background-color:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 20px 25px -5px rgba(0,0,0,0.4),0 8px 10px -6px rgba(0,0,0,0.3);border:1px solid #1E293B;'>
                    
                    <!-- App Topbar (Bills UI Header) -->
                    <tr>
                        <td style='background:linear-gradient(180deg, #0A0F1D 0%, #080D1A 100%);padding:22px 28px;border-bottom:2px solid #00A460;'>
                            <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                                <tr>
                                    <!-- Logo & Brand Title -->
                                    <td style='vertical-align:middle;'>
                                        <table cellpadding='0' cellspacing='0' border='0'>
                                            <tr>
                                                <td style='vertical-align:middle;padding-right:16px;'>
                                                    <a href='https://{$domain}' target='_blank' style='text-decoration:none;display:block;'>
                                                        <img src='{$logoSrc}' alt='Bills' width='62' style='display:block;width:62px;height:auto;border:0;' />
                                                    </a>
                                                </td>
                                                <td style='vertical-align:middle;'>
                                                    <div style='font-size:16px;font-weight:800;letter-spacing:-0.2px;color:#FFFFFF;'>
                                                        GridBase Bills
                                                    </div>
                                                    <div style='font-size:11px;font-weight:600;color:#94A3B8;letter-spacing:0.3px;margin-top:2px;'>
                                                        Módulo de Soporte y Tickets
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Status Pill Badge -->
                                    <td align='right' style='vertical-align:middle;'>
                                        " . ($isNew ? "
                                        <span style='background:rgba(0,164,96,0.15);color:#00A460;font-size:11px;font-weight:800;padding:6px 14px;border-radius:9999px;letter-spacing:0.6px;text-transform:uppercase;border:1px solid rgba(0,164,96,0.35);display:inline-block;white-space:nowrap;'>
                                            ● Nuevo Ticket
                                        </span>
                                        " : "
                                        <span style='background:rgba(59,130,246,0.15);color:#3B82F6;font-size:11px;font-weight:800;padding:6px 14px;border-radius:9999px;letter-spacing:0.6px;text-transform:uppercase;border:1px solid rgba(59,130,246,0.35);display:inline-block;white-space:nowrap;'>
                                            ● Respuesta de Cliente
                                        </span>
                                        ") . "
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content Area -->
                    <tr>
                        <td style='padding:28px 30px 24px;background-color:#FFFFFF;'>
                            
                            <!-- Ticket Overview Header Bar -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;margin-bottom:24px;'>
                                <tr>
                                    <td style='padding:16px 20px;'>
                                        <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                                            <tr>
                                                <td style='vertical-align:middle;'>
                                                    <span style='font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;'>
                                                        Identificador de Ticket
                                                    </span>
                                                    <div style='font-size:19px;font-weight:800;color:#0F172A;font-family:\"JetBrains Mono\",monospace;letter-spacing:0.5px;margin-top:2px;'>
                                                        {$ticket->ticket_number}
                                                    </div>
                                                </td>
                                                <td align='right' style='vertical-align:middle;'>
                                                    <span style='background:{$prioStyle['bg']};color:{$prioStyle['text']};border:1px solid {$prioStyle['border']};font-size:11px;font-weight:800;padding:5px 12px;border-radius:6px;text-transform:uppercase;letter-spacing:0.5px;display:inline-block;'>
                                                        Prioridad: " . htmlspecialchars($ticket->priority) . "
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <!-- Subject Heading -->
                                        <div style='margin-top:12px;padding-top:12px;border-top:1px solid #E2E8F0;'>
                                            <div style='font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;'>Asunto:</div>
                                            <div style='font-size:16px;font-weight:700;color:#0F172A;line-height:1.4;margin-top:3px;'>
                                                " . htmlspecialchars($ticket->subject) . "
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- System Specifications Grid (4 Cards in Bills Style) -->
                            <div style='font-size:12px;font-weight:700;color:#0F172A;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:12px;'>
                                Especificaciones del Sistema y Origen
                            </div>

                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom:24px;'>
                                <tr>
                                    <!-- Card 1: Distribution / Domain -->
                                    <td width='48%' style='vertical-align:top;background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 14px;'>
                                        <div style='font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;'>
                                            Distribución de Bills
                                        </div>
                                        <div style='font-size:13px;font-weight:700;color:#00A460;'>
                                            <a href='https://{$domain}' target='_blank' style='color:#00A460;text-decoration:none;'>
                                                {$domain} &rarr;
                                            </a>
                                        </div>
                                        <div style='font-size:11px;color:#94A3B8;margin-top:2px;'>
                                            Instancia Cloud Activa
                                        </div>
                                    </td>

                                    <td width='4%'>&nbsp;</td>

                                    <!-- Card 2: Company & Tax ID -->
                                    <td width='48%' style='vertical-align:top;background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 14px;'>
                                        <div style='font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;'>
                                            Empresa Emisora
                                        </div>
                                        <div style='font-size:13px;font-weight:700;color:#0F172A;'>
                                            " . htmlspecialchars($company['name']) . "
                                        </div>
                                        <div style='font-size:11px;color:#64748B;margin-top:2px;'>
                                            RNC: <strong>" . htmlspecialchars($company['tax_id']) . "</strong>
                                        </div>
                                    </td>
                                </tr>

                                <tr><td colspan='3' height='10' style='font-size:1px;line-height:10px;'>&nbsp;</td></tr>

                                <tr>
                                    <!-- Card 3: User & Role -->
                                    <td width='48%' style='vertical-align:top;background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 14px;'>
                                        <div style='font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;'>
                                            Usuario y Rol
                                        </div>
                                        <div style='font-size:13px;font-weight:700;color:#0F172A;'>
                                            " . htmlspecialchars($user->name) . "
                                        </div>
                                        <div style='font-size:11px;color:#64748B;margin-top:2px;'>
                                            Rol: <span style='background:#E2E8F0;color:#334155;padding:1px 6px;border-radius:3px;font-weight:600;font-size:10.5px;'>{$userRole}</span>
                                        </div>
                                        <div style='font-size:11px;color:#94A3B8;margin-top:2px;'>
                                            " . htmlspecialchars($user->email) . "
                                        </div>
                                    </td>

                                    <td width='4%'>&nbsp;</td>

                                    <!-- Card 4: Timestamp DR -->
                                    <td width='48%' style='vertical-align:top;background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 14px;'>
                                        <div style='font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;'>
                                            Fecha y Hora (REP DOM)
                                        </div>
                                        <div style='font-size:13px;font-weight:700;color:#0F172A;'>
                                            {$dateDR}
                                        </div>
                                        <div style='font-size:11px;color:#64748B;margin-top:2px;'>
                                            Categoría: <strong style='text-transform:capitalize;'>" . htmlspecialchars($ticket->category) . "</strong>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Message Thread Bubble (Bills Chat Style) -->
                            <div style='font-size:12px;font-weight:700;color:#0F172A;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;'>
                                Mensaje del Ticket
                            </div>

                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#FFFFFF;border:1px solid #E2E8F0;border-left:4px solid #00A460;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:26px;'>
                                <tr>
                                    <td style='padding:18px 20px;'>
                                        <!-- User Header inside Message -->
                                        <table cellpadding='0' cellspacing='0' border='0' style='margin-bottom:12px;'>
                                            <tr>
                                                <td style='vertical-align:middle;padding-right:10px;'>
                                                    <div style='width:32px;height:32px;border-radius:50%;background:#0B484C;color:#FFFFFF;font-weight:700;font-size:12px;line-height:32px;text-align:center;'>
                                                        {$initials}
                                                    </div>
                                                </td>
                                                <td style='vertical-align:middle;'>
                                                    <div style='font-size:13px;font-weight:700;color:#0F172A;'>
                                                        " . htmlspecialchars($user->name) . "
                                                    </div>
                                                    <div style='font-size:11px;color:#64748B;'>
                                                        " . htmlspecialchars($user->email) . "
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Message Text -->
                                        <div style='font-size:14px;line-height:1.7;color:#334155;white-space:pre-wrap;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>{$safeMessage}</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Action Buttons Bar (Bills Primary & Secondary Buttons) -->
                            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom:12px;'>
                                <tr>
                                    <td align='center'>
                                        <table cellpadding='0' cellspacing='0' border='0'>
                                            <tr>
                                                <td style='padding:0 6px;'>
                                                    <a href='{$ticketUrl}' target='_blank' style='background-color:#00A460;color:#FFFFFF;font-size:14px;font-weight:700;text-decoration:none;padding:12px 26px;border-radius:8px;display:inline-block;box-shadow:0 2px 4px rgba(0,164,96,0.3);letter-spacing:0.2px;'>
                                                        Abrir Ticket en Bills &rarr;
                                                    </a>
                                                </td>
                                                <td style='padding:0 6px;'>
                                                    <a href='mailto:" . htmlspecialchars($user->email) . "' style='background-color:#F8FAFC;color:#334155;border:1px solid #CBD5E1;font-size:13.5px;font-weight:600;text-decoration:none;padding:11px 20px;border-radius:8px;display:inline-block;'>
                                                        Responder por Correo
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer (Bills UI Standard Footer) -->
                    <tr>
                        <td style='background-color:#F8FAFC;border-top:1px solid #E2E8F0;padding:22px 30px;color:#64748B;font-size:12px;line-height:1.6;text-align:center;'>
                            <div style='margin-bottom:6px;color:#334155;font-weight:600;'>
                                Canal Exclusivo de Soporte &bull; GridBase Bills
                            </div>
                            <div style='color:#64748B;font-size:11.5px;'>
                                Despachado por <code style='font-family:monospace;background:#E2E8F0;padding:2px 5px;border-radius:3px;color:#0F172A;'>billsticket@gridbase.com.do</code> exclusivamente hacia <a href='mailto:soporte@gridbase.com.do' style='color:#00A460;text-decoration:none;font-weight:600;'>soporte@gridbase.com.do</a>.
                            </div>
                            <div style='color:#94A3B8;font-size:11px;margin-top:4px;'>
                                Al responder a este mensaje, la respuesta será enviada directamente al cliente (" . htmlspecialchars($user->email) . ").
                            </div>
                            <div style='margin-top:14px;padding-top:12px;border-top:1px solid #E2E8F0;color:#94A3B8;font-size:11px;'>
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
