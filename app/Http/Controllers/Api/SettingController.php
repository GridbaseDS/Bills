<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::getAll());
    }

    public function updateMultiple(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::where('setting_key', $key)->update(['setting_value' => $value]);
        }
        return response()->json(['success' => true]);
    }

    public function testSmtp(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
            'host' => 'required',
            'port' => 'required',
            'username' => 'required',
            'password' => 'required',
        ]);

        try {
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $request->host,
                $request->port,
                $request->encryption === 'tls' || $request->encryption === 'ssl'
            );

            if ($request->username) {
                $transport->setUsername($request->username);
                $transport->setPassword($request->password);
            }

            $mailer = new \Symfony\Component\Mailer\Mailer($transport);

            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address(
                    !empty($request->from_email) ? $request->from_email : 'noreply@gridbase.com.do', 
                    !empty($request->from_name) ? $request->from_name : 'Gridbase Bills'
                ))
                ->to($request->test_email)
                ->subject('Prueba de Conexión SMTP - Gridbase Bills')
                ->text("¡Felicidades! Tu configuración SMTP está funcionando perfectamente en Gridbase Bills.\n\nHost: {$request->host}\nPuerto: {$request->port}");

            $mailer->send($email);

            return response()->json(['success' => true, 'message' => 'Correo de prueba enviado con éxito']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'error' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }
}
