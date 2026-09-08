<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;
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
        if ($request->boolean('send_welcome_email') && !empty($user->email)) {
            try {
                $settings = Setting::getAll();
                $company = $settings['company_name'] ?? 'GridBase Bills';
                $loginUrl = url('/');
                $subject = "Bienvenido a {$company} - Datos de Acceso";
                $html = "
                <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;max-width:560px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:10px;background:#ffffff;color:#1e293b;'>
                    <h2 style='color:#0f172a;margin-top:0;'>Hola {$user->name},</h2>
                    <p style='font-size:14px;line-height:1.5;color:#334155;'>Has sido registrado como usuario en la plataforma de <strong>{$company}</strong>.</p>
                    <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:20px 0;'>
                        <div style='font-size:13px;color:#64748b;'><strong>Usuario / Correo:</strong> {$user->email}</div>
                        <div style='font-size:13px;color:#64748b;margin-top:6px;'><strong>Contraseña Temporal:</strong> <code style='background:#e2e8f0;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:14px;color:#0f172a;'>{$plainPassword}</code></div>
                        <div style='font-size:13px;color:#64748b;margin-top:6px;'><strong>Rol Asignado:</strong> " . ucfirst($user->role) . "</div>
                    </div>
                    <div style='text-align:center;margin-top:24px;'>
                        <a href='{$loginUrl}' target='_blank' style='background:#00a460;color:#ffffff;text-decoration:none;padding:11px 24px;border-radius:6px;font-weight:700;font-size:14px;display:inline-block;'>Acceder a la Plataforma &rarr;</a>
                    </div>
                </div>";

                Mail::html($html, function ($msg) use ($user, $subject) {
                    $msg->to($user->email, $user->name)->subject($subject);
                });
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::warning("Could not email welcome credentials to {$user->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente.' . ($emailSent ? ' Se envió correo de bienvenida.' : ''),
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
        if ($request->boolean('send_email') && !empty($user->email)) {
            try {
                $settings = Setting::getAll();
                $company = $settings['company_name'] ?? 'GridBase Bills';
                $loginUrl = url('/');
                $subject = "Credenciales de Acceso Actualizadas - {$company}";
                $html = "
                <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;max-width:560px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:10px;background:#ffffff;color:#1e293b;'>
                    <h2 style='color:#0f172a;margin-top:0;'>Hola {$user->name},</h2>
                    <p style='color:#334155;font-size:14px;line-height:1.5;'>Un administrador ha restablecido tu contraseña de acceso a <strong>{$company}</strong>.</p>
                    <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:20px 0;'>
                        <div style='font-size:13px;color:#64748b;'><strong>Usuario / Correo:</strong> {$user->email}</div>
                        <div style='font-size:13px;color:#64748b;margin-top:6px;'><strong>Nueva Contraseña:</strong> <code style='background:#e2e8f0;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:14px;color:#0f172a;'>{$newPassword}</code></div>
                    </div>
                    <div style='text-align:center;margin-top:24px;'>
                        <a href='{$loginUrl}' target='_blank' style='background:#00a460;color:#ffffff;text-decoration:none;padding:11px 24px;border-radius:6px;font-weight:700;font-size:14px;display:inline-block;'>Iniciar Sesión &rarr;</a>
                    </div>
                </div>";

                Mail::html($html, function ($msg) use ($user, $subject) {
                    $msg->to($user->email, $user->name)->subject($subject);
                });
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::warning("Could not email password reset to {$user->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente.' . ($emailSent ? ' Se notificó al usuario por correo.' : ''),
            'email_sent' => $emailSent
        ]);
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
