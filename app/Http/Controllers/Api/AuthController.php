<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserBiometric;
use App\Services\Auth\WebAuthnService;

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

    // ═══════════════════════════════════════════════════════════════════════
    // WebAuthn (Biometric Face ID / Touch ID / Fingerprint) API
    // ═══════════════════════════════════════════════════════════════════════

    public function webauthnRegisterOptions(Request $request, WebAuthnService $webAuthn)
    {
        $user = $request->user();
        $challenge = $webAuthn->generateChallenge();

        session(['webauthn_register_challenge' => $challenge]);

        // Exclude credentials user has already registered
        $excludeCredentials = $user->biometrics()->pluck('credential_id')->map(function ($id) {
            return [
                'type' => 'public-key',
                'id' => $id,
            ];
        })->toArray();

        return response()->json([
            'challenge' => $challenge,
            'rp' => [
                'name' => 'GridBase Bills',
                'id' => $request->getHost(),
            ],
            'user' => [
                'id' => $webAuthn->base64UrlEncode((string)$user->id),
                'name' => $user->email,
                'displayName' => $user->name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],   // ES256 (ECDSA P-256)
                ['type' => 'public-key', 'alg' => -257], // RS256 (RSA)
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'userVerification' => 'preferred',
                'residentKey' => 'preferred',
            ],
            'timeout' => 60000,
            'excludeCredentials' => $excludeCredentials,
        ]);
    }

    public function webauthnRegister(Request $request, WebAuthnService $webAuthn)
    {
        $request->validate([
            'attestationObject' => 'required|string',
            'clientDataJSON' => 'required|string',
            'device_token' => 'required|string',
            'authenticator_name' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $expectedChallenge = session('webauthn_register_challenge');

        if (!$expectedChallenge) {
            return response()->json(['error' => 'Desafío biométrico no válido o expirado.'], 400);
        }

        session()->forget('webauthn_register_challenge');

        $deviceToken = $request->device_token;
        if ($deviceToken === 'auto' || empty($deviceToken)) {
            $deviceToken = bin2hex(random_bytes(32));
            $userAgent = $request->header('User-Agent') ?: 'Dispositivo';
            $deviceName = 'Dispositivo';
            if (stripos($userAgent, 'Android') !== false) {
                $deviceName = 'Android Móvil';
            } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
                $deviceName = 'iOS Móvil';
            } elseif (stripos($userAgent, 'Windows') !== false) {
                $deviceName = 'Windows PC';
            } elseif (stripos($userAgent, 'Macintosh') !== false) {
                $deviceName = 'Mac PC';
            }

            $user->devices()->create([
                'device_token' => $deviceToken,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ]);
        } else {
            $device = \App\Models\UserDevice::where('device_token', $deviceToken)->where('user_id', $user->id)->first();
            if (!$device) {
                $userAgent = $request->header('User-Agent') ?: 'Dispositivo';
                $deviceName = 'Dispositivo';
                if (stripos($userAgent, 'Android') !== false) {
                    $deviceName = 'Android Móvil';
                } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
                    $deviceName = 'iOS Móvil';
                } elseif (stripos($userAgent, 'Windows') !== false) {
                    $deviceName = 'Windows PC';
                } elseif (stripos($userAgent, 'Macintosh') !== false) {
                    $deviceName = 'Mac PC';
                }

                $user->devices()->create([
                    'device_token' => $deviceToken,
                    'device_name' => $deviceName,
                    'last_used_at' => now(),
                ]);
            }
        }

        try {
            $parsed = $webAuthn->parseAttestationObject($request->attestationObject);

            // Save credential
            $biometric = UserBiometric::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'credential_id' => $parsed['credential_id'],
                ],
                [
                    'device_token' => $deviceToken,
                    'public_key' => $parsed['public_key'],
                    'sign_count' => $parsed['sign_count'],
                    'authenticator_name' => $request->authenticator_name ?: 'Dispositivo Biométrico',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Sensor biométrico (Face ID / Touch ID) registrado con éxito.',
                'device_token' => $deviceToken,
                'biometric' => $biometric,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar biometría: ' . $e->getMessage()], 422);
        }
    }

    public function webauthnLoginOptions(Request $request, WebAuthnService $webAuthn)
    {
        $request->validate([
            'email' => 'required|email',
            'device_token' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Find credentials for user on this device
        $credentials = UserBiometric::where('user_id', $user->id)
            ->where('device_token', $request->device_token)
            ->get();

        if ($credentials->isEmpty()) {
            return response()->json(['error' => 'No hay sensores biométricos registrados en este dispositivo.'], 404);
        }

        $challenge = $webAuthn->generateChallenge();
        session([
            'webauthn_login_challenge' => $challenge,
            'webauthn_login_email' => $user->email,
        ]);

        $allowCredentials = $credentials->map(function ($c) {
            return [
                'type' => 'public-key',
                'id' => $c->credential_id,
            ];
        })->toArray();

        return response()->json([
            'challenge' => $challenge,
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'preferred',
            'timeout' => 60000,
        ]);
    }

    public function webauthnLogin(Request $request, WebAuthnService $webAuthn)
    {
        $request->validate([
            'credential_id' => 'required|string',
            'authenticatorData' => 'required|string',
            'clientDataJSON' => 'required|string',
            'signature' => 'required|string',
            'device_token' => 'required|string',
        ]);

        $expectedChallenge = session('webauthn_login_challenge');
        $email = session('webauthn_login_email');

        if (!$expectedChallenge || !$email) {
            return response()->json(['error' => 'Sesión de autenticación biométrica expirada.'], 400);
        }

        $biometric = UserBiometric::where('credential_id', $request->credential_id)
            ->where('device_token', $request->device_token)
            ->first();

        if (!$biometric) {
            return response()->json(['error' => 'Biometría no registrada o dispositivo no autorizado.'], 401);
        }

        $user = $biometric->user;

        if (!$user || $user->email !== $email || !$user->is_active) {
            return response()->json(['error' => 'Usuario inactivo o no válido.'], 401);
        }

        try {
            $isValid = $webAuthn->verifyAssertion(
                $request->authenticatorData,
                $request->clientDataJSON,
                $request->signature,
                $biometric->public_key,
                $expectedChallenge,
                $request->getSchemeAndHttpHost()
            );

            if (!$isValid) {
                return response()->json(['error' => 'Firma biométrica no válida.'], 401);
            }

            session()->forget(['webauthn_login_challenge', 'webauthn_login_email']);

            // Update user and device
            $user->last_login = now();
            $user->save();

            if ($device = $biometric->device) {
                $device->last_used_at = now();
                $device->save();
            }

            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al verificar biometría: ' . $e->getMessage()], 401);
        }
    }

    public function getBiometrics(Request $request)
    {
        $biometrics = $request->user()->biometrics()->with('device')->orderBy('created_at', 'desc')->get();
        return response()->json($biometrics);
    }

    public function deleteBiometric(Request $request, $id)
    {
        $biometric = $request->user()->biometrics()->find($id);
        if (!$biometric) {
            return response()->json(['error' => 'Credencial biométrica no encontrada'], 404);
        }
        $biometric->delete();
        return response()->json(['success' => true]);
    }
}
