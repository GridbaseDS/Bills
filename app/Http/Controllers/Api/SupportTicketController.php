<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Setting;
use App\Services\EmailService;
use App\Services\SupportTicketEmailService;
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

            // Dispatch email notification strictly to soporte@gridbase.com.do using pre-installed billsticket account
            SupportTicketEmailService::sendNewTicketNotification($ticket, $message, $user);

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

        // Send email notifications strictly to soporte@gridbase.com.do when client replies
        if (!$isAdmin) {
            SupportTicketEmailService::sendUserReplyNotification($ticket, $message, $user);
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
}
