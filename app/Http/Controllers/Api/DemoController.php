<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
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
     * Extend demo expiration time by N hours.
     */
    public function extend(Request $request)
    {
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
}
