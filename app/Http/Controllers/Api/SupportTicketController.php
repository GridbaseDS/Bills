<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Setting;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportTicketController extends Controller
{
    private const SUPPORT_EMAIL = 'soporte@gridbase.com.do';

    /**
     * List support tickets.
     * Regular users see only their own tickets; admins and gerentes see all tickets.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['admin', 'gerente']);

        $query = SupportTicket::with(['user:id,name,email,role', 'latestMessage']);

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Stats queries based on permission scope
        $statsBase = SupportTicket::query();
        if (!$isAdmin) {
            $statsBase->where('user_id', $user->id);
        }

        $stats = [
            'total' => (clone $statsBase)->count(),
            'open' => (clone $statsBase)->where('status', 'abierto')->count(),
            'in_progress' => (clone $statsBase)->where('status', 'en_proceso')->count(),
            'resolved' => (clone $statsBase)->whereIn('status', ['resuelto', 'cerrado'])->count(),
        ];

        // Filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $tickets = $query->orderBy('updated_at', 'desc')->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
            ],
            'stats' => $stats,
            'is_admin' => $isAdmin,
            'support_email' => self::SUPPORT_EMAIL,
        ]);
    }

    /**
     * Create a new support ticket and send notification email to soporte@gridbase.com.do.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'priority' => 'required|in:baja,media,alta,urgente',
            'message' => 'required|string|min:5',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            // Generate sequential ticket number: TKT-1001, TKT-1002...
            $lastId = SupportTicket::max('id') ?? 0;
            $ticketNumber = 'TKT-' . str_pad((string)($lastId + 1001), 5, '0', STR_PAD_LEFT);

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $user->id,
                'subject' => trim($request->subject),
                'category' => $request->category,
                'priority' => $request->priority,
                'status' => 'abierto',
                'support_email' => self::SUPPORT_EMAIL,
                'last_reply_at' => now(),
            ]);

            $message = SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'sender_type' => 'user',
                'sender_name' => $user->name,
                'sender_email' => $user->email,
                'message' => trim($request->message),
            ]);

            // Dispatch email notifications
            $this->notifySupportNewTicket($ticket, $message, $user);
            $this->notifyUserTicketCreated($ticket, $user);

            return response()->json([
                'success' => true,
                'message' => 'Ticket de soporte creado exitosamente.',
                'data' => $ticket->load(['user:id,name,email,role', 'messages']),
            ], 201);
        });
    }

    /**
     * Show ticket details with full message thread.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['admin', 'gerente']);

        $ticket = SupportTicket::with(['user:id,name,email,role', 'messages.user:id,name,email,role'])
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', $id)->orWhere('ticket_number', $id);
                } else {
                    $q->where('ticket_number', $id);
                }
            })
            ->firstOrFail();

        if (!$isAdmin && $ticket->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket,
            'is_admin' => $isAdmin,
            'support_email' => self::SUPPORT_EMAIL,
        ]);
    }

    /**
     * Add reply to a ticket thread and notify the other party.
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|min:2',
            'status' => 'nullable|in:abierto,en_proceso,resuelto,cerrado',
        ]);

        $user = $request->user();
        $isAdmin = in_array($user->role, ['admin', 'gerente']);

        $ticket = SupportTicket::with(['user:id,name,email,role'])->findOrFail($id);

        if (!$isAdmin && $ticket->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        if ($isAdmin) {
            $senderType = 'support';
            $senderName = 'Soporte Gridbase (' . $user->name . ')';
            $senderEmail = self::SUPPORT_EMAIL;
        } else {
            $senderType = 'user';
            $senderName = $user->name;
            $senderEmail = $user->email;
        }

        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'sender_email' => $senderEmail,
            'message' => trim($request->message),
        ]);

        // Update ticket state and timestamp
        $ticket->last_reply_at = now();
        if ($isAdmin) {
            $ticket->status = $request->input('status', 'en_proceso');
        } else {
            // Reopen if user replies to resolved/closed ticket
            if (in_array($ticket->status, ['resuelto', 'cerrado'])) {
                $ticket->status = 'abierto';
            }
        }
        $ticket->save();

        // Send email notifications
        if ($isAdmin) {
            $this->notifyUserSupportReply($ticket, $message);
        } else {
            $this->notifySupportUserReply($ticket, $message, $user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Respuesta enviada correctamente.',
            'data' => $ticket->fresh(['user:id,name,email,role', 'messages.user:id,name,email,role']),
        ]);
    }

    /**
     * Update status of a ticket.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:abierto,en_proceso,resuelto,cerrado',
        ]);

        $user = $request->user();
        $isAdmin = in_array($user->role, ['admin', 'gerente']);

        $ticket = SupportTicket::findOrFail($id);

        if (!$isAdmin && $ticket->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $oldStatus = $ticket->status;
        $ticket->status = $request->status;
        $ticket->save();

        // Add system message to log status change
        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'sender_type' => 'system',
            'sender_name' => 'Sistema',
            'sender_email' => self::SUPPORT_EMAIL,
            'message' => "Estado actualizado de '{$oldStatus}' a '{$ticket->status}' por {$user->name}.",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'data' => $ticket->fresh(['user:id,name,email,role', 'messages.user:id,name,email,role']),
        ]);
    }

    // ── Email Notification Helpers ─────────────────────────────

    private function notifySupportNewTicket(SupportTicket $ticket, SupportTicketMessage $message, $user): void
    {
        try {
            EmailService::applySmtpConfig([]);
            $settings = Setting::getAll();
            $companyName = trim($settings['company_name'] ?? '') ?: 'Gridbase Bills';
            $rnc = trim($settings['tax_id'] ?? '') ?: 'N/D';

            $subject = "[{$ticket->ticket_number}] Nuevo Ticket de Soporte: {$ticket->subject}";
            $html = "
            <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:10px;background:#ffffff;color:#1e293b;'>
                <div style='border-bottom:2px solid #2563eb;padding-bottom:12px;margin-bottom:16px;'>
                    <h2 style='margin:0;color:#0f172a;font-size:20px;'>Nuevo Ticket de Soporte</h2>
                    <span style='display:inline-block;margin-top:6px;background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700;'>{$ticket->ticket_number}</span>
                </div>
                <table style='width:100%;border-collapse:collapse;margin-bottom:16px;font-size:13px;'>
                    <tr><td style='padding:6px 0;color:#64748b;width:120px;'><strong>Usuario:</strong></td><td style='color:#0f172a;'>{$user->name} ({$user->email})</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'><strong>Empresa / RNC:</strong></td><td style='color:#0f172a;'>{$companyName} (RNC: {$rnc})</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'><strong>Categoría:</strong></td><td style='text-transform:capitalize;'>{$ticket->category}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'><strong>Prioridad:</strong></td><td style='text-transform:capitalize;'>{$ticket->priority}</td></tr>
                    <tr><td style='padding:6px 0;color:#64748b;'><strong>Asunto:</strong></td><td style='font-weight:700;color:#0f172a;'>{$ticket->subject}</td></tr>
                </table>
                <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:20px;'>
                    <div style='font-size:12px;color:#64748b;margin-bottom:8px;font-weight:600;'>Mensaje del usuario:</div>
                    <div style='font-size:14px;line-height:1.6;color:#334155;white-space:pre-wrap;'>" . htmlspecialchars($message->message) . "</div>
                </div>
                <div style='font-size:12px;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:12px;text-align:center;'>
                    Puedes responder directamente a este correo o gestionarlo en GridBase Bills.<br>
                    Canal oficial: <a href='mailto:soporte@gridbase.com.do' style='color:#2563eb;'>soporte@gridbase.com.do</a>
                </div>
            </div>";

            $fromEmail = config('mail.from.address') ?: 'bills@gridbase.com.do';
            $fromName = config('mail.from.name') ?: 'Gridbase Bills';

            Mail::html($html, function ($msg) use ($subject, $user, $fromEmail, $fromName) {
                $msg->from($fromEmail, $fromName)
                    ->to(self::SUPPORT_EMAIL, 'Soporte Gridbase')
                    ->replyTo($user->email, $user->name)
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning("Support notification to " . self::SUPPORT_EMAIL . " failed: " . $e->getMessage());
        }
    }

    private function notifyUserTicketCreated(SupportTicket $ticket, $user): void
    {
        try {
            EmailService::applySmtpConfig([]);
            $subject = "[{$ticket->ticket_number}] Hemos recibido tu solicitud de soporte: {$ticket->subject}";
            $html = "
            <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:10px;background:#ffffff;color:#1e293b;'>
                <div style='border-bottom:2px solid #10b981;padding-bottom:12px;margin-bottom:16px;'>
                    <h2 style='margin:0;color:#0f172a;font-size:20px;'>Solicitud de Soporte Recibida</h2>
                    <span style='display:inline-block;margin-top:6px;background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700;'>{$ticket->ticket_number}</span>
                </div>
                <p style='font-size:14px;line-height:1.5;'>Hola <strong>{$user->name}</strong>,</p>
                <p style='font-size:14px;line-height:1.5;color:#475569;'>
                    Hemos recibido tu ticket y nuestro equipo de soporte lo está revisando. Te responderemos a la mayor brevedad posible a través de la plataforma y por este correo.
                </p>
                <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:16px 0;'>
                    <div style='font-size:13px;color:#64748b;'><strong>Asunto:</strong> {$ticket->subject}</div>
                    <div style='font-size:13px;color:#64748b;margin-top:4px;'><strong>Categoría:</strong> " . ucfirst($ticket->category) . " | <strong>Prioridad:</strong> " . ucfirst($ticket->priority) . "</div>
                </div>
                <div style='font-size:12px;color:#64748b;line-height:1.5;margin-top:20px;border-top:1px solid #f1f5f9;padding-top:12px;'>
                    Equipo de Soporte Gridbase<br>
                    Contacto directo: <a href='mailto:soporte@gridbase.com.do' style='color:#2563eb;'>soporte@gridbase.com.do</a>
                </div>
            </div>";

            $fromEmail = config('mail.from.address') ?: 'bills@gridbase.com.do';
            $fromName = 'Soporte Gridbase';

            Mail::html($html, function ($msg) use ($subject, $user, $fromEmail, $fromName) {
                $msg->from($fromEmail, $fromName)
                    ->to($user->email, $user->name)
                    ->replyTo(self::SUPPORT_EMAIL, 'Soporte Gridbase')
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning("User ticket confirmation to {$user->email} failed: " . $e->getMessage());
        }
    }

    private function notifyUserSupportReply(SupportTicket $ticket, SupportTicketMessage $message): void
    {
        try {
            EmailService::applySmtpConfig([]);
            $user = $ticket->user;
            if (!$user || empty($user->email)) return;

            $subject = "[{$ticket->ticket_number}] Respuesta del Equipo de Soporte: {$ticket->subject}";
            $html = "
            <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:10px;background:#ffffff;color:#1e293b;'>
                <div style='border-bottom:2px solid #2563eb;padding-bottom:12px;margin-bottom:16px;'>
                    <h2 style='margin:0;color:#0f172a;font-size:20px;'>Nueva Respuesta de Soporte</h2>
                    <span style='display:inline-block;margin-top:6px;background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700;'>{$ticket->ticket_number}</span>
                </div>
                <p style='font-size:14px;line-height:1.5;'>Hola <strong>{$user->name}</strong>,</p>
                <p style='font-size:14px;line-height:1.5;color:#475569;'>
                    El equipo de Soporte Gridbase ha respondido a tu ticket:
                </p>
                <div style='background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px;margin:16px 0;'>
                    <div style='font-size:12px;color:#1e40af;font-weight:700;margin-bottom:6px;'>Respuesta de Soporte:</div>
                    <div style='font-size:14px;line-height:1.6;color:#1e3a8a;white-space:pre-wrap;'>" . htmlspecialchars($message->message) . "</div>
                </div>
                <div style='font-size:12px;color:#64748b;line-height:1.5;margin-top:20px;border-top:1px solid #f1f5f9;padding-top:12px;'>
                    Puedes responder a este correo o acceder a GridBase Bills para continuar el seguimiento.<br>
                    Contacto: <a href='mailto:soporte@gridbase.com.do' style='color:#2563eb;'>soporte@gridbase.com.do</a>
                </div>
            </div>";

            $fromEmail = config('mail.from.address') ?: 'bills@gridbase.com.do';
            $fromName = 'Soporte Gridbase';

            Mail::html($html, function ($msg) use ($subject, $user, $fromEmail, $fromName) {
                $msg->from($fromEmail, $fromName)
                    ->to($user->email, $user->name)
                    ->replyTo(self::SUPPORT_EMAIL, 'Soporte Gridbase')
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning("Support reply notification to user failed: " . $e->getMessage());
        }
    }

    private function notifySupportUserReply(SupportTicket $ticket, SupportTicketMessage $message, $user): void
    {
        try {
            EmailService::applySmtpConfig([]);
            $subject = "[{$ticket->ticket_number}] Nueva respuesta del cliente ({$user->name}): {$ticket->subject}";
            $html = "
            <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:10px;background:#ffffff;color:#1e293b;'>
                <div style='border-bottom:2px solid #f59e0b;padding-bottom:12px;margin-bottom:16px;'>
                    <h2 style='margin:0;color:#0f172a;font-size:20px;'>Respuesta del Cliente en Ticket</h2>
                    <span style='display:inline-block;margin-top:6px;background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700;'>{$ticket->ticket_number}</span>
                </div>
                <p style='font-size:14px;color:#475569;'>El usuario <strong>{$user->name}</strong> ({$user->email}) ha respondido al ticket:</p>
                <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:16px 0;'>
                    <div style='font-size:14px;line-height:1.6;color:#334155;white-space:pre-wrap;'>" . htmlspecialchars($message->message) . "</div>
                </div>
                <div style='font-size:12px;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:12px;text-align:center;'>
                    Puedes responder directamente a este correo o abrir el ticket en GridBase Bills.
                </div>
            </div>";

            $fromEmail = config('mail.from.address') ?: 'bills@gridbase.com.do';
            $fromName = config('mail.from.name') ?: 'Gridbase Bills';

            Mail::html($html, function ($msg) use ($subject, $user, $fromEmail, $fromName) {
                $msg->from($fromEmail, $fromName)
                    ->to(self::SUPPORT_EMAIL, 'Soporte Gridbase')
                    ->replyTo($user->email, $user->name)
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning("User reply notification to support failed: " . $e->getMessage());
        }
    }
}
