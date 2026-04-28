<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\EmailService;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::getAll());
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
}
