<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use App\Services\WhatsApp\EvolutionWhatsAppDriver;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::getAll();
        $settings['server_date_dr'] = now('America/Santo_Domingo')->format('Y-m-d');
        return response()->json($settings);
    }

    public function updateMultiple(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value ?? '']
            );
        }
        return response()->json(['success' => true]);
    }

    public function testSmtp(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // Use input() to avoid collision with the HTTP Host header
            $smtpSettings = [
                'host'       => $request->input('host', 'localhost'),
                'port'       => $request->input('port', 25),
                'encryption' => $request->input('encryption', null),
                'username'   => $request->input('username', ''),
                'password'   => $request->input('password', ''),
                'from_email' => $request->input('from_email', 'bills@gridbase.com.do'),
                'from_name'  => $request->input('from_name', 'Gridbase Bills'),
            ];

            // Use the centralized config method
            EmailService::applySmtpConfig($smtpSettings);

            $host = $smtpSettings['host'];
            $encryption = config('mail.mailers.smtp.encryption');
            $method = ($host === 'localhost' || $host === '127.0.0.1' || empty($host)) 
                ? 'SMTP Local (localhost:' . config('mail.mailers.smtp.port') . ')' 
                : "SMTP ({$host}:" . config('mail.mailers.smtp.port') . ($encryption ? "/{$encryption}" : '') . ")";

            \Illuminate\Support\Facades\Mail::raw(
                "¡Felicidades! Tu configuración de correo está funcionando perfectamente en Gridbase Bills.\n\nMétodo: {$method}\nHost: " . config('mail.mailers.smtp.host') . "\nPuerto: " . config('mail.mailers.smtp.port') . "\nCifrado: " . (config('mail.mailers.smtp.encryption') ?: 'Ninguno'),
                function ($message) use ($request) {
                    $message->to($request->input('test_email'))
                            ->subject('✅ Prueba de Conexión Exitosa - Gridbase Bills');
                }
            );

            \Illuminate\Support\Facades\Log::info("SMTP test email sent successfully to {$request->input('test_email')} via {$method}");

            return response()->json(['success' => true, 'message' => "Correo de prueba enviado con éxito via {$method}"]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SMTP test failed: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'error' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }

    public function diagnoseSmtp()
    {
        $hosts = ['localhost', '127.0.0.1'];
        
        // Add the mail server for the domain
        $mailDomain = 'mail.gridbase.com.do';
        $hosts[] = $mailDomain;
        
        // Try to get hostname
        try { $hosts[] = gethostname(); } catch (\Exception $e) {}
        
        $hosts = array_unique($hosts);
        $ports = [25, 465, 587];
        $results = [];

        foreach ($hosts as $host) {
            foreach ($ports as $port) {
                $conn = @fsockopen($host, $port, $errno, $errstr, 3);
                $results[] = [
                    'host' => $host,
                    'port' => $port,
                    'status' => $conn ? 'OPEN' : 'CLOSED',
                    'error' => $conn ? null : $errstr
                ];
                if ($conn) fclose($conn);
            }
        }

        $openPorts = collect($results)->where('status', 'OPEN');
        $recommendation = null;
        
        // Recommend localhost:25 first (most reliable on cPanel)
        $localPort25 = $openPorts->first(fn($r) => ($r['host'] === 'localhost' || $r['host'] === '127.0.0.1') && $r['port'] === 25);
        if ($localPort25) {
            $recommendation = $localPort25;
            $recommendation['note'] = 'Recomendado: SMTP local sin cifrado (más confiable en cPanel)';
        } else {
            $recommendation = $openPorts->first();
        }

        return response()->json([
            'success' => true,
            'hostname' => gethostname(),
            'results' => $results,
            'recommendation' => $recommendation,
            'current_config' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
            ]
        ]);
    }

    public function resetDatabase(Request $request)
    {
        $request->validate([
            'confirm_email' => 'required|email',
        ]);

        $currentUser = auth()->user();
        if ($request->input('confirm_email') !== $currentUser->email) {
            return response()->json([
                'success' => false,
                'error' => 'El correo de confirmación no coincide con tu correo actual.'
            ], 422);
        }

        try {
            // Disable foreign key checks
            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

            // Clear operational tables
            \Illuminate\Support\Facades\DB::table('payments')->truncate();
            \Illuminate\Support\Facades\DB::table('quote_items')->truncate();
            \Illuminate\Support\Facades\DB::table('quotes')->truncate();
            \Illuminate\Support\Facades\DB::table('invoice_items')->truncate();
            \Illuminate\Support\Facades\DB::table('invoices')->truncate();
            \Illuminate\Support\Facades\DB::table('recurring_invoice_items')->truncate();
            \Illuminate\Support\Facades\DB::table('recurring_invoices')->truncate();
            \Illuminate\Support\Facades\DB::table('clients')->truncate();
            \Illuminate\Support\Facades\DB::table('expenses')->truncate();
            \Illuminate\Support\Facades\DB::table('items')->truncate();
            \Illuminate\Support\Facades\DB::table('received_invoices')->truncate();
            \Illuminate\Support\Facades\DB::table('activity_log')->truncate();
            if (\Illuminate\Support\Facades\Schema::hasTable('api_keys')) {
                \Illuminate\Support\Facades\DB::table('api_keys')->truncate();
            }
            
            // Clear sessions except current session
            \Illuminate\Support\Facades\DB::table('sessions')
                ->where('id', '!=', session()->getId())
                ->delete();

            // Reset Settings table to default values using the Seeder
            \Illuminate\Support\Facades\DB::table('settings')->truncate();

            // Run the seeder to repopulate default settings (including is_installed = '0')
            $seeder = new \Database\Seeders\DatabaseSeeder();
            $seeder->run();

            // Re-enable foreign key checks
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

            // Log activity
            \Illuminate\Support\Facades\Log::warning("DATABASE RESET PERFORMED by Admin user ID {$currentUser->id} ({$currentUser->email})");

            return response()->json([
                'success' => true,
                'message' => 'La base de datos ha sido reiniciada con éxito. Se mantuvieron los usuarios y roles.'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
            \Illuminate\Support\Facades\Log::error("Database reset failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al reiniciar la base de datos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadCertificate(Request $request)
    {
        $request->validate([
            'certificate' => 'required|file|max:5120', // max 5MB
        ]);

        $file = $request->file('certificate');
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['p12', 'pfx'])) {
            return response()->json([
                'success' => false,
                'error' => 'Solo se permiten archivos .p12 o .pfx'
            ], 422);
        }

        try {
            $filename = $file->getClientOriginalName();

            // Ensure the secure directory exists
            $securePath = storage_path('app/secure');
            if (!is_dir($securePath)) {
                mkdir($securePath, 0755, true);
            }

            // Move the file
            $file->move($securePath, $filename);

            // Auto-update the setting
            Setting::updateOrCreate(
                ['setting_key' => 'dgii_certificate_path'],
                ['setting_value' => $filename, 'setting_group' => 'dgii']
            );

            \Illuminate\Support\Facades\Log::info("[SettingController] Certificate uploaded: {$filename}");

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'message' => "Certificado '{$filename}' subido correctamente."
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("[SettingController] Certificate upload failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Error al subir el certificado: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // WhatsApp Testing & Evolution API
    // ─────────────────────────────────────────────────────────────

    /**
     * Test the active WhatsApp driver by sending a test message.
     * POST /api/settings/whatsapp-test
     */
    public function testWhatsapp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:7',
        ]);

        try {
            $wa     = new WhatsAppService();
            $driver = $wa->getDriverName();

            if (!$wa->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'driver'  => $driver,
                    'error'   => "El driver '{$driver}' no está habilitado o faltan credenciales.",
                ], 422);
            }

            $result = $wa->sendTextMessage(
                $request->input('phone'),
                "Mensaje de prueba desde Gridbase Bills.\nDriver activo: *{$driver}*\n_Esta es una prueba de conexion._"
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'driver'  => $driver,
                    'message' => "Mensaje enviado correctamente via {$driver}.",
                ]);
            }

            return response()->json([
                'success' => false,
                'driver'  => $driver,
                'error'   => $result['message'] ?? 'Error desconocido',
            ], 500);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsApp test failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Evolution API connection state.
     * GET /api/settings/evolution-status
     */
    public function getEvolutionStatus()
    {
        try {
            $settings = Setting::getAll();
            $driver   = new EvolutionWhatsAppDriver($settings);

            if (!$driver->isEnabled()) {
                return response()->json([
                    'success'   => false,
                    'connected' => false,
                    'state'     => 'not_configured',
                    'message'   => 'Evolution API no está configurada. Ingresa la URL, API key e instancia.',
                ]);
            }

            return response()->json($driver->getConnectionState());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get QR code for linking Evolution API WhatsApp session.
     * GET /api/settings/evolution-qr
     */
    public function getEvolutionQr()
    {
        try {
            $settings = Setting::getAll();
            $driver   = new EvolutionWhatsAppDriver($settings);

            if (!$driver->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evolution API no está configurada.',
                ], 422);
            }

            return response()->json($driver->getQrCode());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate a pairing code for linking WhatsApp via phone number.
     * More reliable than QR scanning with Baileys.
     * POST /api/settings/evolution-pairing-code
     */
    public function getEvolutionPairingCode(Request $request)
    {
        try {
            $settings = Setting::getAll();
            $driver   = new EvolutionWhatsAppDriver($settings);

            if (!$driver->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evolution API no está configurada.',
                ], 422);
            }

            $phoneNumber = $request->input('phone_number', $settings['evolution_phone_number'] ?? '');

            if (empty($phoneNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere un número de teléfono. Configúralo en los ajustes de Evolution API.',
                ], 422);
            }

            return response()->json($driver->getPairingCode($phoneNumber));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function publicSettings()
    {
        $all = Setting::getAll();
        $changelog = config('changelog') ?? [];
        
        return response()->json([
            'company_name' => $all['company_name'] ?? 'Bills',
            'company_logo' => $all['company_logo'] ?? '',
            'login_logo' => $all['login_logo'] ?? '',
            'company_favicon' => $all['company_favicon'] ?? '',
            'pdf_primary_color' => $all['pdf_primary_color'] ?? '#0B484C',
            'pdf_accent_color' => $all['pdf_accent_color'] ?? '#00DF83',
            'sidebar_bg_color' => $all['sidebar_bg_color'] ?? '#FFFFFF',
            'sidebar_text_color' => $all['sidebar_text_color'] ?? '#374151',
            'sidebar_hover_color' => $all['sidebar_hover_color'] ?? '#F3F4F6',
            'sidebar_dark_bg_color' => $all['sidebar_dark_bg_color'] ?? '#111827',
            'sidebar_dark_text_color' => $all['sidebar_dark_text_color'] ?? '#FFFFFF',
            'sidebar_dark_hover_color' => $all['sidebar_dark_hover_color'] ?? '#1F2937',
            'sidebar_logo_height' => $all['sidebar_logo_height'] ?? '45',
            'is_installed' => $all['is_installed'] ?? '0',
            'system_version' => $changelog['version'] ?? '1.0.0',
            'system_changelog' => $changelog['changes'] ?? [],
        ]);
    }
}

