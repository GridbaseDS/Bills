<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class DemoController extends Controller
{
    /**
     * Get the current demo status and remaining time.
     */
    public function status()
    {
        $isDemo = (bool) config('app.demo_mode', false);
        $expiresAtStr = Setting::get('demo_expires_at');
        $expiresAt = $expiresAtStr ? Carbon::parse($expiresAtStr) : now()->addHours(72);

        $now = now();
        $isExpired = $now->gte($expiresAt);

        // Si expiró y estamos en modo demo, ejecutar auto-reinicio
        if ($isDemo && $isExpired) {
            Artisan::call('demo:reset', ['--force' => true]);
            $expiresAtStr = Setting::get('demo_expires_at');
            $expiresAt = Carbon::parse($expiresAtStr);
            $isExpired = false;
        }

        $remainingSeconds = max(0, $now->diffInSeconds($expiresAt, false));
        $remainingHours = round($remainingSeconds / 3600, 1);

        return response()->json([
            'is_demo' => $isDemo,
            'expires_at' => $expiresAt->toDateTimeString(),
            'expires_at_iso' => $expiresAt->toISOString(),
            'remaining_seconds' => $remainingSeconds,
            'remaining_hours' => $remainingHours,
            'is_expired' => $isExpired,
            'message' => 'Entorno Demo de Gridbase Bills activo',
        ]);
    }

    /**
     * Check if the request is authorized by Gridbase Master Admin.
     */
    private function isAuthorizedDemoAdmin(Request $request): bool
    {
        if (Auth::check() && Auth::user()->email === 'soporte@gridbase.com.do') {
            return true;
        }

        $providedKey = $request->input('admin_key') ?? $request->header('X-Demo-Admin-Key');
        $masterKey = env('DEMO_ADMIN_KEY', 'SamDP_9903');

        if ($providedKey && hash_equals((string)$masterKey, (string)$providedKey)) {
            return true;
        }

        return false;
    }

    /**
     * Extend demo expiration time by N hours.
     */
    public function extend(Request $request)
    {
        if (!$this->isAuthorizedDemoAdmin($request)) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado. Solo el personal de Gridbase puede extender el período de demostración.',
            ], 403);
        }

        $hours = (int) $request->input('hours', 24);
        if ($hours < 1 || $hours > 720) {
            $hours = 24;
        }

        $expiresAtStr = Setting::get('demo_expires_at');
        $baseDate = ($expiresAtStr && Carbon::parse($expiresAtStr)->gt(now()))
            ? Carbon::parse($expiresAtStr)
            : now();

        $newExpiresAt = $baseDate->copy()->addHours($hours);

        Setting::updateOrCreate(
            ['setting_key' => 'demo_expires_at'],
            ['setting_value' => $newExpiresAt->toDateTimeString(), 'setting_group' => 'demo']
        );

        $remainingSeconds = max(0, now()->diffInSeconds($newExpiresAt, false));

        Log::info("[DemoController] Período Demo extendido en +{$hours} horas. Nueva expiración: {$newExpiresAt->toDateTimeString()}");

        return response()->json([
            'success' => true,
            'message' => "Se han añadido +{$hours} horas a tu sesión demo.",
            'added_hours' => $hours,
            'expires_at' => $newExpiresAt->toDateTimeString(),
            'expires_at_iso' => $newExpiresAt->toISOString(),
            'remaining_seconds' => $remainingSeconds,
            'remaining_hours' => round($remainingSeconds / 3600, 1),
        ]);
    }

    /**
     * Reset demo data immediately.
     */
    public function reset(Request $request)
    {
        if (!$this->isAuthorizedDemoAdmin($request)) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado. Solo el personal de Gridbase puede restablecer los datos de demostración.',
            ], 403);
        }

        try {
            Artisan::call('demo:reset', ['--force' => true]);

            $expiresAtStr = Setting::get('demo_expires_at');
            $expiresAt = $expiresAtStr ? Carbon::parse($expiresAtStr) : now()->addHours(72);
            $remainingSeconds = max(0, now()->diffInSeconds($expiresAt, false));

            return response()->json([
                'success' => true,
                'message' => 'Los datos de prueba han sido restablecidos con éxito.',
                'expires_at' => $expiresAt->toDateTimeString(),
                'remaining_seconds' => $remainingSeconds,
            ]);
        } catch (\Throwable $e) {
            Log::error("[DemoController] Error reiniciando demo: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al restablecer los datos de prueba: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Provision a customized demo access for a client with up to 3 users.
     */
    public function provision(Request $request)
    {
        if (!$this->isAuthorizedDemoAdmin($request)) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado. Solo el personal de Gridbase puede otorgar accesos demo.',
            ], 403);
        }

        $request->validate([
            'company_name' => 'required|string|max:150',
            'users' => 'required|array|min:1|max:3',
            'users.*.name' => 'required|string|max:100',
            'users.*.email' => 'required|email|max:150',
            'users.*.password' => 'required|string|min:4',
            'users.*.role' => 'nullable|string|in:admin,gerente,vendedor,contador',
        ]);

        try {
            // 1. Limpieza y siembra de datos de prueba
            Artisan::call('demo:reset', ['--force' => true]);

            // 2. Asignar el nombre de la empresa solicitada
            $companyName = trim($request->input('company_name'));
            Setting::updateOrCreate(
                ['setting_key' => 'company_name'],
                ['setting_value' => $companyName, 'setting_group' => 'company']
            );

            // 3. Crear los usuarios solicitados (máximo 3)
            $userInputs = $request->input('users');
            $createdUsers = [];
            $firstUser = null;

            // Conservar solo soporte@gridbase.com.do como super-admin de rescate
            User::where('email', '!=', 'soporte@gridbase.com.do')->delete();

            foreach ($userInputs as $idx => $u) {
                $role = $u['role'] ?? ($idx === 0 ? 'admin' : 'vendedor');
                $user = User::create([
                    'name' => trim($u['name']),
                    'email' => strtolower(trim($u['email'])),
                    'password' => bcrypt($u['password']),
                    'role' => $role,
                ]);

                $createdUsers[] = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'password' => $u['password'],
                ];

                if ($idx === 0) {
                    $firstUser = $user;
                }
            }

            // 4. Iniciar sesión automática con el usuario principal
            if ($firstUser) {
                Auth::login($firstUser);
            }

            // 5. Garantizar 72 horas a partir de ahora
            $expiresAt = now()->addHours(72);
            Setting::updateOrCreate(
                ['setting_key' => 'demo_expires_at'],
                ['setting_value' => $expiresAt->toDateTimeString(), 'setting_group' => 'demo']
            );

            Cache::flush();

            Log::info("[DemoController] Acceso Demo otorgado a '{$companyName}' con " . count($createdUsers) . " usuario(s).");

            return response()->json([
                'success' => true,
                'message' => "Acceso Demo otorgado con éxito para {$companyName}.",
                'company_name' => $companyName,
                'expires_at' => $expiresAt->toDateTimeString(),
                'users' => $createdUsers,
                'authenticated_user' => $firstUser ? [
                    'id' => $firstUser->id,
                    'name' => $firstUser->name,
                    'email' => $firstUser->email,
                    'role' => $firstUser->role,
                ] : null,
            ]);
        } catch (\Throwable $e) {
            Log::error("[DemoController] Error otorgando demo: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error' => 'Error al otorgar acceso demo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
