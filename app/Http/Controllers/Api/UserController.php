<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;
use App\Services\EmailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List all users with activity counts, formatted metadata and system statistics.
     */
    public function index()
    {
        $users = User::withCount(['invoices', 'quotes', 'devices', 'biometrics'])
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($user) {
                $user->has_2fa = !empty($user->two_factor_secret);
                $user->has_biometrics = $user->biometrics_count > 0;
                $user->last_login_formatted = $user->last_login 
                    ? $user->last_login->setTimezone('America/Santo_Domingo')->format('d/m/Y h:i A') 
                    : null;
                $user->last_login_human = $user->last_login 
                    ? $user->last_login->diffForHumans() 
                    : 'Sin accesos registrados';
                $user->created_at_formatted = $user->created_at 
                    ? $user->created_at->setTimezone('America/Santo_Domingo')->format('d/m/Y') 
                    : null;
                return $user;
            });

        $stats = [
            'total' => $users->count(),
            'active' => $users->where('is_active', true)->count(),
            'inactive' => $users->where('is_active', false)->count(),
            'with_2fa' => $users->where('has_2fa', true)->count(),
            'by_role' => [
                'admin' => $users->where('role', 'admin')->count(),
                'gerente' => $users->where('role', 'gerente')->count(),
                'contador' => $users->where('role', 'contador')->count(),
                'vendedor' => $users->where('role', 'vendedor')->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'gerente', 'contador', 'vendedor'])],
            'is_active' => 'boolean'
        ]);

        $plainPassword = $validated['password'];
        $validated['password'] = Hash::make($plainPassword);
        $validated['is_active'] = $request->input('is_active', true);

        $user = User::create($validated);

        $emailSent = false;
        $emailError = null;
        if ($request->boolean('send_welcome_email') && !empty($user->email)) {
            $dispatchResult = EmailService::sendUserWelcomeEmail($user, $plainPassword);
            $emailSent = $dispatchResult['success'];
            if (!$emailSent) {
                $emailError = $dispatchResult['error'] ?? 'Fallo de conexión SMTP';
            }
        }

        $message = 'Usuario creado exitosamente.';
        if ($request->boolean('send_welcome_email')) {
            if ($emailSent) {
                $message .= " Se enviaron las credenciales de acceso a {$user->email}.";
            } else {
                $message .= " Advertencia: No se pudo entregar el correo ({$emailError}).";
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'email_sent' => $emailSent,
            'email_error' => $emailError,
            'user' => $user
        ], 201);
    }

    /**
     * Show a single user details.
     */
    public function show($id)
    {
        $user = User::withCount(['invoices', 'quotes', 'devices', 'biometrics'])->findOrFail($id);
        $user->has_2fa = !empty($user->two_factor_secret);
        return response()->json($user);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'gerente', 'contador', 'vendedor'])],
            'is_active' => 'boolean'
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'string|min:6';
        }

        $validated = $request->validate($rules);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Prevent self-deactivation
        if (auth()->id() == $user->id && isset($validated['is_active']) && !$validated['is_active']) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propio usuario en sesión.'
            ], 400);
        }

        $validated['is_active'] = $request->input('is_active', $user->is_active);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente.',
            'user' => $user
        ]);
    }

    /**
     * Toggle active/inactive status of a user directly from the list.
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propia cuenta de acceso.'
            ], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'Usuario activado exitosamente.' : 'Usuario desactivado exitosamente.',
            'is_active' => $user->is_active,
            'user' => $user
        ]);
    }

    /**
     * Reset user password with optional notification email.
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6',
            'send_email' => 'nullable|boolean',
        ]);

        $user = User::findOrFail($id);
        $newPassword = $request->password;
        $user->password = Hash::make($newPassword);
        $user->save();

        $emailSent = false;
        $emailError = null;
        if ($request->boolean('send_email') && !empty($user->email)) {
            $dispatchResult = EmailService::sendUserPasswordReset($user, $newPassword);
            $emailSent = $dispatchResult['success'];
            if (!$emailSent) {
                $emailError = $dispatchResult['error'] ?? 'Fallo de conexión SMTP';
            }
        }

        $message = 'Contraseña restablecida exitosamente.';
        if ($request->boolean('send_email')) {
            if ($emailSent) {
                $message .= " Se notificó la nueva contraseña a {$user->email}.";
            } else {
                $message .= " Advertencia: No se pudo entregar el correo ({$emailError}).";
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'email_sent' => $emailSent,
            'email_error' => $emailError,
        ]);
    }

    /**
     * Resend access credentials to user with a new secure temporary password.
     */
    public function resendCredentials(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (empty($user->email)) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no cuenta con una dirección de correo válida configurada.'
            ], 422);
        }

        $tempPassword = $request->input('password') ?: \Illuminate\Support\Str::random(10) . rand(10, 99);
        $user->password = Hash::make($tempPassword);
        $user->save();

        $dispatchResult = EmailService::sendUserWelcomeEmail($user, $tempPassword);

        if ($dispatchResult['success']) {
            return response()->json([
                'success' => true,
                'message' => "Credenciales enviadas exitosamente a {$user->email}.",
                'temporary_password' => $tempPassword,
                'email_sent' => true,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "No se pudo entregar el correo a {$user->email}: " . ($dispatchResult['error'] ?? 'Error SMTP'),
            'temporary_password' => $tempPassword,
            'email_sent' => false,
        ], 500);
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting oneself
        if (auth()->id() == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propio usuario en sesión.'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente.'
        ]);
    }
}
