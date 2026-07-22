<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    private function base32Decode(string $b32): string {
        $b32 = strtoupper($b32);
        if (!preg_match('/^[A-Z2-7]+$/', $b32)) {
            return '';
        }
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buf = 0;
        $bufSize = 0;
        $res = '';
        for ($i = 0; $i < strlen($b32); $i++) {
            $c = $b32[$i];
            $val = strpos($chars, $c);
            $buf = ($buf << 5) | $val;
            $bufSize += 5;
            if ($bufSize >= 8) {
                $bufSize -= 8;
                $res .= chr(($buf >> $bufSize) & 0xFF);
            }
        }
        return $res;
    }

    private function verifyTotp(string $secret, string $code, int $discrepancy = 1): bool {
        $key = $this->base32Decode($secret);
        if (empty($key)) return false;
        $currentTime = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $timeWindow = $currentTime + $i;
            $timeBinary = pack('N*', 0) . pack('N*', $timeWindow);
            $hmac = hash_hmac('sha1', $timeBinary, $key, true);
            $offset = ord($hmac[19]) & 0x0F;
            $hashPart = substr($hmac, $offset, 4);
            $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
            $totp = str_pad(strval($value % 1000000), 6, '0', STR_PAD_LEFT);
            if (hash_equals($totp, $code)) {
                return true;
            }
        }
        return false;
    }

    private function generate2faSecret(): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        if (empty($user->two_factor_secret)) {
            // Optional 2FA: Direct login if 2FA is not enabled
            $user->last_login = now();
            $user->save();
            Auth::login($user);
            $request->session()->forget('pre_auth_user_id');

            return response()->json([
                'success' => true,
                'requires_2fa' => false,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'two_factor_enabled' => false,
                ]
            ]);
        }

        // Store pre-authenticated state in session for users with 2FA enabled
        $request->session()->put('pre_auth_user_id', $user->id);

        // Standard 2FA Verification Mode
        return response()->json([
            'requires_2fa' => true,
            'setup_mode' => false
        ]);
    }

    public function verify2fa(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = $request->session()->get('pre_auth_user_id');
        if (empty($userId)) {
            return response()->json(['error' => 'La sesión de pre-autenticación ha expirado.'], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado.'], 404);
        }

        $code = str_replace(' ', '', $request->code);

        if (empty($user->two_factor_secret)) {
            // Setup Verification from login flow if applicable
            $secret = $request->session()->get('temp_2fa_secret');
            if (empty($secret)) {
                return response()->json(['error' => 'No se ha podido iniciar la configuración del 2FA. Reintente.'], 422);
            }

            if ($this->verifyTotp($secret, $code)) {
                $user->two_factor_secret = $secret;
                $user->last_login = now();
                $user->save();
                Auth::login($user);

                $request->session()->forget(['pre_auth_user_id', 'temp_2fa_secret']);

                return response()->json([
                    'success' => true,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'two_factor_enabled' => true,
                    ]
                ]);
            }
        } else {
            // Standard Verification
            if ($this->verifyTotp($user->two_factor_secret, $code)) {
                $user->last_login = now();
                $user->save();
                Auth::login($user);

                $request->session()->forget('pre_auth_user_id');

                return response()->json([
                    'success' => true,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'two_factor_enabled' => true,
                    ]
                ]);
            }
        }

        return response()->json(['error' => 'Código de verificación incorrecto. Inténtalo de nuevo.'], 422);
    }

    public function get2faStatus(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'enabled' => !empty($user->two_factor_secret)
        ]);
    }

    public function init2faSetup(Request $request)
    {
        $user = $request->user();
        $temp_secret = $this->generate2faSecret();
        $request->session()->put('temp_2fa_secret', $temp_secret);

        $appName = config('app.name', 'Bills');
        $qrUri = 'otpauth://totp/' . rawurlencode($appName) . '%20(' . rawurlencode($user->email) . ')?secret=' . $temp_secret . '&issuer=' . rawurlencode($appName);

        return response()->json([
            'success' => true,
            'temp_secret' => $temp_secret,
            'qr_uri' => $qrUri
        ]);
    }

    public function enable2fa(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'temp_secret' => 'nullable|string'
        ]);

        $user = $request->user();
        $code = str_replace(' ', '', $request->code);
        $secret = $request->session()->get('temp_2fa_secret') ?? $request->temp_secret;

        if (empty($secret)) {
            return response()->json(['error' => 'No se ha iniciado la configuración del 2FA. Reintente.'], 422);
        }

        if ($this->verifyTotp($secret, $code)) {
            $user->two_factor_secret = $secret;
            $user->save();

            $request->session()->forget('temp_2fa_secret');

            return response()->json([
                'success' => true,
                'message' => 'Autenticación en dos pasos (2FA) activada exitosamente.'
            ]);
        }

        return response()->json(['error' => 'Código de verificación incorrecto.'], 422);
    }

    public function disable2fa(Request $request)
    {
        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return response()->json(['message' => 'La autenticación en dos pasos ya está desactivada.'], 200);
        }

        if ($request->has('password') && !empty($request->password)) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Contraseña incorrecta.'], 422);
            }
        }

        $user->two_factor_secret = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Autenticación en dos pasos (2FA) desactivada exitosamente.'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json(['success' => true]);
    }

    public function setupPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6'
        ]);

        $user = $request->user();
        $user->quick_pin = Hash::make($request->pin);
        $user->save();
        
        // Generate a new secure device token
        $deviceToken = bin2hex(random_bytes(32));
        
        // Parse User Agent to get a human-friendly name
        $userAgent = $request->header('User-Agent') ?: 'Dispositivo desconocido';
        $deviceName = 'Dispositivo';
        if (stripos($userAgent, 'Android') !== false) {
            $deviceName = 'Android Móvil';
        } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            $deviceName = 'iOS Móvil';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            $deviceName = 'Windows PC';
        } elseif (stripos($userAgent, 'Macintosh') !== false) {
            $deviceName = 'Mac PC';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $deviceName = 'Linux PC';
        }

        // Save device
        $user->devices()->create([
            'device_token' => $deviceToken,
            'device_name' => $deviceName,
            'last_used_at' => now(),
        ]);

        // Manage max 3 devices: delete oldest ones if count > 3
        $devices = $user->devices()->orderBy('last_used_at', 'desc')->get();
        if ($devices->count() > 3) {
            $devicesToDelete = $devices->slice(3);
            foreach ($devicesToDelete as $dev) {
                $dev->delete();
            }
        }

        return response()->json([
            'success' => true,
            'device_token' => $deviceToken
        ]);
    }

    public function pinLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'pin' => 'required|string|size:6',
            'device_token' => 'required|string'
        ]);

        // Look up device in the user_devices table
        $device = \App\Models\UserDevice::where('device_token', $request->device_token)->first();

        if (!$device) {
            return response()->json(['error' => 'PIN incorrecto o dispositivo no autorizado'], 401);
        }

        $user = $device->user;

        if (!$user || $user->email !== $request->email || empty($user->quick_pin) || !Hash::check($request->pin, $user->quick_pin)) {
            // Invalid email, token, or PIN
            return response()->json(['error' => 'PIN incorrecto o dispositivo no autorizado'], 401);
        }

        // Complete authentication
        $user->last_login = now();
        $user->save();

        // Update device usage time
        $device->last_used_at = now();
        $device->save();
        
        Auth::login($user); // Login without 'remember me' since we don't have remember_token column
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    public function getDevices(Request $request)
    {
        $devices = $request->user()->devices()->orderBy('last_used_at', 'desc')->get();
        return response()->json($devices);
    }

    public function deleteDevice(Request $request, $id)
    {
        $device = $request->user()->devices()->find($id);
        if (!$device) {
            return response()->json(['error' => 'Dispositivo no encontrado'], 404);
        }
        $device->delete();
        return response()->json(['success' => true]);
    }
}
