<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Http\Controllers\Api\InvoiceController;

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
            $settings = [
                'smtp_host' => $request->host ?? 'localhost',
                'smtp_port' => $request->port ?? 587,
                'smtp_encryption' => $request->encryption ?? 'tls',
                'smtp_username' => $request->username ?? '',
                'smtp_password' => $request->password ?? '',
                'smtp_from_email' => $request->from_email,
                'smtp_from_name' => $request->from_name,
            ];

            $transport = InvoiceController::buildTransport($settings);
            $mailer = new \Symfony\Component\Mailer\Mailer($transport);

            $fromEmail = !empty($request->from_email) ? $request->from_email : 'bills@gridbase.com.do';
            $fromName = !empty($request->from_name) ? $request->from_name : 'Gridbase Bills';
            $method = ($settings['smtp_host'] === 'localhost' || empty($settings['smtp_host'])) ? 'Sendmail (Local)' : "SMTP ({$settings['smtp_host']}:{$settings['smtp_port']})";

            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address($fromEmail, $fromName))
                ->to($request->test_email)
                ->subject('Prueba de Conexión - Gridbase Bills')
                ->text("¡Felicidades! Tu configuración de correo está funcionando perfectamente en Gridbase Bills.\n\nMétodo: {$method}");

            $mailer->send($email);

            return response()->json(['success' => true, 'message' => "Correo de prueba enviado con éxito via {$method}"]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'error' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }

    public function diagnoseSmtp()
    {
        $hosts = ['localhost', '127.0.0.1', 'mail.gridbase.com.do', gethostname()];
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

        return response()->json([
            'success' => true,
            'hostname' => gethostname(),
            'results' => $results,
            'recommendation' => collect($results)->first(fn($r) => $r['status'] === 'OPEN')
        ]);
    }
}
