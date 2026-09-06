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
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\Expense;
use App\Models\ReceivedInvoice;
use App\Models\Item;
use App\Models\Client;
use App\Models\Payment;
use Database\Seeders\DemoDataSeeder;
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

        $hasDemoData = Invoice::count() > 0 || Item::count() > 0;

        return response()->json([
            'is_demo' => $isDemo,
            'expires_at' => $expiresAt->toDateTimeString(),
            'expires_at_iso' => $expiresAt->toISOString(),
            'remaining_seconds' => $remainingSeconds,
            'remaining_hours' => $remainingHours,
            'is_expired' => $isExpired,
            'has_demo_data' => $hasDemoData,
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
            $withDemoData = $request->boolean('with_demo_data', true);
            Artisan::call('demo:reset', [
                '--force' => true,
                '--clean' => !$withDemoData,
            ]);

            $expiresAtStr = Setting::get('demo_expires_at');
            $expiresAt = $expiresAtStr ? Carbon::parse($expiresAtStr) : now()->addHours(72);
            $remainingSeconds = max(0, now()->diffInSeconds($expiresAt, false));

            return response()->json([
                'success' => true,
                'message' => $withDemoData
                    ? 'Los datos de prueba han sido restablecidos con éxito.'
                    : 'La instancia ha sido restablecida en modo limpio.',
                'expires_at' => $expiresAt->toDateTimeString(),
                'remaining_seconds' => $remainingSeconds,
                'has_demo_data' => $withDemoData,
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
            $withDemoData = $request->boolean('with_demo_data', true);

            // 1. Limpieza y siembra condicional de datos de prueba
            Artisan::call('demo:reset', [
                '--force' => true,
                '--clean' => !$withDemoData,
            ]);

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

            // Reasignar autoría de datos demo al usuario cliente principal si se sembraron datos
            if ($firstUser) {
                if ($withDemoData) {
                    Invoice::query()->update(['created_by' => $firstUser->id]);
                    Quote::query()->update(['created_by' => $firstUser->id]);
                    Expense::query()->update(['created_by' => $firstUser->id]);
                }
                Auth::login($firstUser);
            }

            // 5. Garantizar 72 horas a partir de ahora
            $expiresAt = now()->addHours(72);
            Setting::updateOrCreate(
                ['setting_key' => 'demo_expires_at'],
                ['setting_value' => $expiresAt->toDateTimeString(), 'setting_group' => 'demo']
            );

            Cache::flush();

            Log::info("[DemoController] Acceso Demo otorgado a '{$companyName}' con " . count($createdUsers) . " usuario(s) (Con datos: " . ($withDemoData ? 'Sí' : 'No') . ").");

            return response()->json([
                'success' => true,
                'message' => "Acceso Demo otorgado con éxito para {$companyName}.",
                'company_name' => $companyName,
                'with_demo_data' => $withDemoData,
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

    /**
     * Insert demo data into an existing session.
     */
    public function seedData(Request $request)
    {
        if (!Auth::check() && !$this->isAuthorizedDemoAdmin($request)) {
            return response()->json([
                'success' => false,
                'error' => 'Debes iniciar sesión para modificar los datos de prueba.',
            ], 401);
        }

        try {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Payment::truncate();
            InvoiceItem::truncate();
            Invoice::truncate();
            QuoteItem::truncate();
            Quote::truncate();
            RecurringInvoiceItem::truncate();
            RecurringInvoice::truncate();
            Expense::truncate();
            ReceivedInvoice::truncate();
            Item::truncate();
            Client::truncate();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $seeder = new DemoDataSeeder();
            $seeder->run();

            $currentUserId = Auth::id() ?? User::where('role', 'admin')->value('id');
            if ($currentUserId) {
                Invoice::query()->update(['created_by' => $currentUserId]);
                Quote::query()->update(['created_by' => $currentUserId]);
                Expense::query()->update(['created_by' => $currentUserId]);
            }

            Cache::flush();

            return response()->json([
                'success' => true,
                'has_demo_data' => true,
                'message' => '¡Datos de demostración cargados exitosamente!',
            ]);
        } catch (\Throwable $e) {
            Log::error("[DemoController] Error sembrando datos demo: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar datos de prueba: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all demo transactional data (leave instance clean).
     */
    public function clearData(Request $request)
    {
        if (!Auth::check() && !$this->isAuthorizedDemoAdmin($request)) {
            return response()->json([
                'success' => false,
                'error' => 'Debes iniciar sesión para modificar los datos de prueba.',
            ], 401);
        }

        try {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Payment::truncate();
            InvoiceItem::truncate();
            Invoice::truncate();
            QuoteItem::truncate();
            Quote::truncate();
            RecurringInvoiceItem::truncate();
            RecurringInvoice::truncate();
            Expense::truncate();
            ReceivedInvoice::truncate();
            Item::truncate();
            Client::truncate();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            Cache::flush();

            return response()->json([
                'success' => true,
                'has_demo_data' => false,
                'message' => '¡Instancia limpiada con éxito! Ahora puedes ingresar tus propios datos.',
            ]);
        } catch (\Throwable $e) {
            Log::error("[DemoController] Error limpiando datos demo: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al limpiar datos de prueba: ' . $e->getMessage(),
            ], 500);
        }
    }
}
