<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use Carbon\Carbon;

class DemoCheckExpirationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica si la demo ha alcanzado su límite de 72 horas y ejecuta el reinicio automático';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiresAtStr = Setting::get('demo_expires_at');

        if (!$expiresAtStr) {
            $this->info("No hay fecha de expiración configurada. Inicializando período de 72h...");
            Artisan::call('demo:reset', ['--force' => true]);
            return Command::SUCCESS;
        }

        $expiresAt = Carbon::parse($expiresAtStr);

        if (now()->gte($expiresAt)) {
            $this->warn("Período demo expirado en: {$expiresAt->toDateTimeString()}. Reiniciando datos...");
            Log::info("[DemoCheckExpiration] Período demo expirado. Ejecutando demo:reset...");
            Artisan::call('demo:reset', ['--force' => true]);
            $this->info("Reinicio completado.");
        } else {
            $diff = now()->diffForHumans($expiresAt, ['parts' => 2]);
            $this->info("El período demo está activo. Tiempo restante: {$diff}.");
        }

        return Command::SUCCESS;
    }
}
