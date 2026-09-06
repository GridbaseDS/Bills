<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Setting;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;

class DemoResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset {--force : Forzar reinicio ignorando si ya expiró} {--clean : Iniciar instancia limpia sin datos de prueba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reinicia y restablece los datos del entorno Demo de Gridbase Bills cada 72 horas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════════════╗');
        $this->info('║             GRIDBASE BILLS — REINICIO ENTORNO DEMO                 ║');
        $this->info('╚════════════════════════════════════════════════════════════════════╝');

        $isForce = $this->option('force');
        $expiresAtStr = Setting::get('demo_expires_at');

        if (!$isForce && $expiresAtStr) {
            $expiresAt = \Carbon\Carbon::parse($expiresAtStr);
            if (now()->lt($expiresAt)) {
                $remaining = now()->diffForHumans($expiresAt, [
                    'parts' => 2,
                    'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW,
                ]);
                $this->warn("El período de demo aún no ha expirado ({$remaining}).");
                $this->info("Usa --force para forzar el reinicio inmediato.");
                return Command::SUCCESS;
            }
        }

        $this->info('Iniciando limpieza y restauración segura de la base de datos...');

        try {
            Schema::disableForeignKeyConstraints();

            // 1. Limpieza de tablas transaccionales
            $tablesToTruncate = [
                'payments',
                'quote_items',
                'quotes',
                'invoice_items',
                'invoices',
                'recurring_invoice_items',
                'recurring_invoices',
                'clients',
                'expenses',
                'items',
                'received_invoices',
                'activity_log',
                'dgii_logs',
            ];

            foreach ($tablesToTruncate as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->line("  ✓ Tabla vaciada: {$table}");
                }
            }

            if (Schema::hasTable('api_keys')) {
                DB::table('api_keys')->truncate();
            }

            // Limpiar sesiones activas
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->truncate();
            }

            Schema::enableForeignKeyConstraints();

            // 2. Garantizar usuarios base de demo
            $adminUser = User::updateOrCreate(
                ['email' => 'admin@gridbase.com.do'],
                [
                    'name' => 'Demo Admin',
                    'password' => bcrypt('admin123'),
                    'role' => 'admin',
                ]
            );
            $this->info("  ✓ Usuario administrador demo garantizado: admin@gridbase.com.do");

            User::updateOrCreate(
                ['email' => 'soporte@gridbase.com.do'],
                [
                    'name' => 'Soporte Gridbase',
                    'password' => bcrypt('SamDP_9903'),
                    'role' => 'admin',
                ]
            );

            // 3. Ejecutar seeder base de configuración
            $baseSeeder = new DatabaseSeeder();
            $baseSeeder->run();
            $this->info("  ✓ Configuraciones del sistema restablecidas");

            // 4. Configurar parámetros del entorno Demo
            $expiresAt = now()->addHours(72);
            Setting::updateOrCreate(
                ['setting_key' => 'demo_expires_at'],
                ['setting_value' => $expiresAt->toDateTimeString(), 'setting_group' => 'demo']
            );
            Setting::updateOrCreate(
                ['setting_key' => 'demo_started_at'],
                ['setting_value' => now()->toDateTimeString(), 'setting_group' => 'demo']
            );
            Setting::updateOrCreate(
                ['setting_key' => 'is_installed'],
                ['setting_value' => '1', 'setting_group' => 'system']
            );
            Setting::updateOrCreate(
                ['setting_key' => 'company_name'],
                ['setting_value' => 'Gridbase Digital Solutions (Demo)', 'setting_group' => 'company']
            );
            Setting::updateOrCreate(
                ['setting_key' => 'company_tax_id'],
                ['setting_value' => '1-01-00789-3', 'setting_group' => 'company']
            );
            Setting::updateOrCreate(
                ['setting_key' => 'dgii_env'],
                ['setting_value' => 'production', 'setting_group' => 'dgii']
            );
            Setting::updateOrCreate(
                ['setting_key' => 'dgii_razon_social'],
                ['setting_value' => 'GRIDBASE DIGITAL SOLUTIONS SRL', 'setting_group' => 'dgii']
            );
            Setting::updateOrCreate(
                ['setting_key' => 'dgii_ncf_expiry_date'],
                ['setting_value' => '2028-12-31', 'setting_group' => 'dgii']
            );
            Setting::updateOrCreate(
                ['setting_key' => 'default_currency'],
                ['setting_value' => 'DOP', 'setting_group' => 'invoice']
            );

            // 5. Sembrar datos demo transaccionales ricos si no se especificó --clean
            if ($this->option('clean')) {
                $this->info("  ✨ Modo limpio activo: No se insertaron datos de demostración.");
            } else {
                $this->info("  → Sembrando datos transaccionales demo (clientes, facturas e-CF, compras 606, pagos)...");
                $demoSeeder = new DemoDataSeeder();
                $demoSeeder->run();
                $this->info("  ✓ Datos demo sembrados con éxito");
            }

            // 6. Limpiar cachés
            Cache::flush();

            Log::info("[DemoResetCommand] Entorno Demo reiniciado con éxito. Nueva fecha de expiración: {$expiresAt->toDateTimeString()}");

            $this->info('════════════════════════════════════════════════════════════════════');
            $this->info("✅ Entorno Demo reiniciado correctamente.");
            $this->info("⏳ Próxima expiración (72h): {$expiresAt->toDateTimeString()}");
            $this->info('════════════════════════════════════════════════════════════════════');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Schema::enableForeignKeyConstraints();
            $this->error("Error al reiniciar entorno demo: " . $e->getMessage());
            Log::error("[DemoResetCommand] Fallo en reinicio: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }
}
