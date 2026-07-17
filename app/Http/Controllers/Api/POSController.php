<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class POSController extends Controller
{
    /**
     * Inicia una transacción de cobro en el Verifone.
     */
    public function charge(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $amount = $request->input('amount');
        $invoiceId = $request->input('invoice_id');

        $enabled = Setting::get('pos_enabled', '0');
        if ($enabled !== '1') {
            Log::warning("POS: Intento de cargo pero la integración de POS está desactivada.");
            return response()->json([
                'success' => false,
                'message' => 'La integración de POS / Verifone está desactivada.'
            ], 400);
        }

        $driver = Setting::get('pos_driver', 'mock');
        $ip = Setting::get('pos_terminal_ip', '');
        $port = Setting::get('pos_terminal_port', '');
        $timeout = (int) Setting::get('pos_timeout', '60');

        Log::info("POS: Iniciando cargo de {$amount} para Factura ID {$invoiceId} usando driver '{$driver}'");

        switch ($driver) {
            case 'mock':
                return $this->handleMockCharge($amount, $timeout);

            case 'virtual_pos':
                return $this->handleVirtualPosCharge($amount, $invoiceId);

            case 'azul_local':
                return $this->handleAzulLocalCharge($amount, $ip, $port, $timeout);

            case 'cardnet_local':
                return $this->handleCardnetLocalCharge($amount, $ip, $port, $invoiceId, $timeout);

            case 'cardnet_android':
                return $this->handleCardnetAndroidCharge($amount, $ip, $port, $timeout);

            default:
                Log::error("POS: Driver de POS '{$driver}' no soportado.");
                return response()->json([
                    'success' => false,
                    'message' => 'Driver de POS no soportado.'
                ], 400);
        }
    }

    /**
     * Cancela una transacción en progreso.
     */
    public function cancel(Request $request)
    {
        $driver = Setting::get('pos_driver', 'mock');
        Log::info("POS: Recibida solicitud de cancelación para driver '{$driver}'");
        
        if ($driver === 'mock') {
            return response()->json([
                'success' => true,
                'message' => 'Transacción cancelada correctamente en el simulador.'
            ]);
        }

        if ($driver === 'virtual_pos') {
            // Cancelar cualquier transacción virtual en cache
            return response()->json([
                'success' => true,
                'message' => 'Transacción virtual cancelada.'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comando de cancelación enviado.'
        ]);
    }

    /**
     * Retorna el estado actual de una transacción en cache para el sondeo (polling).
     */
    public function status($invoiceId)
    {
        $data = Cache::get('pos_tx_' . $invoiceId);
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Actualiza el estado de la transacción (llamado desde el teléfono simulator).
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required',
            'status' => 'required|in:approved,declined',
            'auth_code' => 'nullable|string',
            'card_number' => 'nullable|string',
            'card_type' => 'nullable|string',
            'message' => 'nullable|string'
        ]);

        $invoiceId = $request->input('invoice_id');
        $status = $request->input('status');

        $cacheKey = 'pos_tx_' . $invoiceId;
        $currentTx = Cache::get($cacheKey);

        if (!$currentTx) {
            return response()->json([
                'success' => false,
                'message' => 'Transacción no encontrada o expirada.'
            ], 404);
        }

        $updatedTx = array_merge($currentTx, [
            'status' => $status,
            'auth_code' => $request->input('auth_code', '999999'),
            'card_number' => $request->input('card_number', '************0000'),
            'card_type' => $request->input('card_type', 'Tarjeta Simulado'),
            'message' => $request->input('message', ($status === 'approved' ? 'Aprobada' : 'Declinada'))
        ]);

        Cache::put($cacheKey, $updatedTx, 600); // Guardar por 10 minutos
        Log::info("POS (Virtual): Estado de transacción de Factura {$invoiceId} actualizado a '{$status}'");

        return response()->json([
            'success' => true,
            'message' => 'Transacción actualizada correctamente.'
        ]);
    }

    /**
     * Renderiza el simulador de Verifone en móviles.
     */
    public function simulatorView($invoiceId)
    {
        $invoice = Invoice::with('client')->findOrFail($invoiceId);
        $tx = Cache::get('pos_tx_' . $invoiceId);

        if (!$tx) {
            // Crear una transacción por si acceden directamente
            $tx = [
                'status' => 'pending',
                'amount' => $invoice->total,
                'invoice_id' => $invoiceId
            ];
            Cache::put('pos_tx_' . $invoiceId, $tx, 600);
        }

        return view('pos.simulator', [
            'invoice' => $invoice,
            'tx' => $tx
        ]);
    }

    /**
     * Inicializa cobro para el simulador virtual en móviles.
     */
    private function handleVirtualPosCharge($amount, $invoiceId)
    {
        $tx = [
            'status' => 'pending',
            'amount' => $amount,
            'invoice_id' => $invoiceId
        ];
        
        Cache::put('pos_tx_' . $invoiceId, $tx, 600); // 10 minutos de expiración
        Log::info("POS (Virtual): Cargo inicializado en cache de Factura {$invoiceId} por DOP {$amount}");

        // Obtener la URL absoluta del simulador móvil
        $simulatorUrl = route('pos.simulator', ['invoice_id' => $invoiceId]);

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'virtual_url' => $simulatorUrl,
            'message' => 'Transacción virtual inicializada. Escanee el código QR en su móvil para continuar.'
        ]);
    }

    /**
     * Simulación de cobro estática de 3 segundos.
     */
    private function handleMockCharge($amount, $timeout)
    {
        sleep(3);

        $authCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $lastFour = rand(1000, 9999);

        Log::info("POS (Mock): Cargo exitoso simulado. Autorización: {$authCode}");

        return response()->json([
            'success' => true,
            'status' => 'approved',
            'auth_code' => $authCode,
            'card_number' => "************{$lastFour}",
            'card_type' => 'Visa',
            'message' => 'Transacción Aprobada (Simulado)'
        ]);
    }

    /**
     * Conexión con Azul Local Bridge.
     */
    private function handleAzulLocalCharge($amount, $ip, $port, $timeout)
    {
        if (empty($ip) || empty($port)) {
            Log::error("POS (Azul): IP o Puerto no configurados.");
            return response()->json([
                'success' => false,
                'message' => 'IP o Puerto del terminal Azul no configurados.'
            ], 400);
        }

        $url = "http://{$ip}:{$port}/azul/charge";
        Log::info("POS (Azul): Conectando a bridge local en {$url}...");

        try {
            $response = Http::timeout($timeout)->post($url, [
                'amount' => $amount,
                'tax' => 0.00,
            ]);

            Log::debug("POS (Azul): Respuesta del bridge HTTP: Status " . $response->status() . " - Body: " . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'status' => $data['status'] ?? 'approved',
                    'auth_code' => $data['auth_code'] ?? '000000',
                    'card_number' => $data['card_number'] ?? '************0000',
                    'card_type' => $data['card_type'] ?? 'Tarjeta',
                    'message' => $data['message'] ?? 'Transacción Aprobada'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'El terminal Azul rechazó la transacción.'
            ], 502);

        } catch (Exception $e) {
            Log::error("POS (Azul): Error de conexión con Azul: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con Azul: ' . $e->getMessage()
            ], 504);
        }
    }

    /**
     * Conexión con Cardnet Local ECRti (Sockets TCP).
     */
    private function handleCardnetLocalCharge($amount, $ip, $port, $invoiceId, $timeout)
    {
        $port = !empty($port) ? $port : '7060';

        if (empty($ip)) {
            Log::error("POS (Cardnet): IP no configurada.");
            return response()->json([
                'success' => false,
                'message' => 'IP del terminal Cardnet no configurada.'
            ], 400);
        }

        Log::info("POS (Cardnet): Intentando abrir conexión socket TCP a {$ip}:{$port}...");
        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
        if (!$socket) {
            Log::error("POS (Cardnet): Fallo de conexión de socket: [{$errno}] {$errstr}");
            return response()->json([
                'success' => false,
                'message' => "No se pudo conectar al Verifone Cardnet ({$ip}:{$port}): {$errstr}"
            ], 504);
        }

        stream_set_timeout($socket, $timeout);

        try {
            // A. Envío de SYN (0x16)
            Log::debug("POS (Cardnet): Enviando SYN (0x16)...");
            fwrite($socket, chr(0x16));

            // B. Espera de EOM (0x19)
            $byte1 = fread($socket, 1);
            Log::debug("POS (Cardnet): Recibido byte 1: " . (ord($byte1) ?? 'NULL'));
            if (ord($byte1) !== 0x19) {
                throw new Exception("Se esperaba EOM (0x19) pero se recibió: " . ord($byte1));
            }

            // C. Espera de ENQ (0x05)
            $byte2 = fread($socket, 1);
            Log::debug("POS (Cardnet): Recibido byte 2: " . (ord($byte2) ?? 'NULL'));
            if (ord($byte2) !== 0x05) {
                throw new Exception("Se esperaba ENQ (0x05) pero se recibió: " . ord($byte2));
            }

            // D. Construir trama
            $amountStr = str_pad(round($amount * 100), 12, '0', STR_PAD_LEFT);
            
            $invoice = Invoice::find($invoiceId);
            $taxAmount = 0.00;
            if ($invoice) {
                $taxAmount = $invoice->tax_amount;
            }
            $taxStr = str_pad(round($taxAmount * 100), 12, '0', STR_PAD_LEFT);
            $otherTaxesStr = str_pad(0, 12, '0', STR_PAD_LEFT);
            $ticketStr = str_pad(substr($invoiceId, -6), 6, '0', STR_PAD_LEFT);

            $fs = chr(0x1C);
            $txMessage = "CN00" . $fs . $amountStr . $fs . $taxStr . $fs . $otherTaxesStr . $fs . $ticketStr . $fs;

            Log::debug("POS (Cardnet): Transmitiendo trama de venta: CN00|<amount>|<tax>|<other>|<ticket>|");
            fwrite($socket, $txMessage);

            // E. Espera de ACK (0x06)
            $ackByte = fread($socket, 1);
            Log::debug("POS (Cardnet): Recibido ACK byte: " . (ord($ackByte) ?? 'NULL'));
            if (ord($ackByte) !== 0x06) {
                throw new Exception("Se esperaba ACK (0x06) pero se recibió: " . ord($ackByte));
            }

            // F. Espera y lectura de resultado de la transacción
            $resBuffer = '';
            $stxReceived = false;

            Log::debug("POS (Cardnet): Esperando resultado del Verifone...");
            while (!feof($socket)) {
                $c = fread($socket, 1);
                if ($c === false || $c === '') {
                    break;
                }
                
                $ascii = ord($c);
                if ($ascii === 0x02) {
                    $stxReceived = true;
                    continue;
                }
                if ($ascii === 0x03) {
                    fread($socket, 1); // LRC
                    break;
                }
                if ($stxReceived) {
                    $resBuffer .= $c;
                } else {
                    $resBuffer .= $c;
                }
            }

            fclose($socket);
            Log::debug("POS (Cardnet): Respuesta cruda del Verifone: " . urlencode($resBuffer));

            if (empty($resBuffer)) {
                throw new Exception("No se recibió respuesta con la trama de autorización del Verifone.");
            }

            $fields = explode($fs, $resBuffer);
            
            $authCode = isset($fields[8]) ? trim($fields[8]) : '';
            $cardNo = isset($fields[3]) ? trim($fields[3]) : '************0000';
            $cardType = isset($fields[1]) ? trim($fields[1]) : 'Tarjeta';
            
            if (isset($fields[0]) && trim($fields[0]) === '99') {
                Log::warning("POS (Cardnet): Transacción no progresó en el terminal (Error 99)");
                return response()->json([
                    'success' => false,
                    'message' => 'Transacción declinada o no progresó en el Verifone.'
                ], 402);
            }

            if (!empty($authCode) && $authCode !== '000000') {
                Log::info("POS (Cardnet): APROBADA - Auth: {$authCode}, Tarjeta: {$cardNo}");
                return response()->json([
                    'success' => true,
                    'status' => 'approved',
                    'auth_code' => $authCode,
                    'card_number' => $cardNo,
                    'card_type' => $cardType,
                    'message' => 'Transacción Aprobada'
                ]);
            }

            $responseText = 'Transacción declinada por el Verifone.';
            foreach ($fields as $f) {
                if (str_contains($f, 'DECLINADA') || str_contains($f, 'FONDOS INSUF') || str_contains($f, 'PIN INVALIDO') || str_contains($f, 'ERROR')) {
                    $responseText = trim($f);
                    break;
                }
            }

            Log::warning("POS (Cardnet): DECLINADA - Detalle: {$responseText}");
            return response()->json([
                'success' => false,
                'message' => $responseText
            ], 402);

        } catch (Exception $e) {
            @fclose($socket);
            Log::error("POS (Cardnet): Error en transacción: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error durante la transacción con Cardnet: ' . $e->getMessage()
            ], 502);
        }
    }

    /**
     * Conexión con Cardnet Android REST Local.
     */
    private function handleCardnetAndroidCharge($amount, $ip, $port, $timeout)
    {
        $port = !empty($port) ? $port : '2001';

        if (empty($ip)) {
            Log::error("POS (Cardnet Android): IP no configurada.");
            return response()->json([
                'success' => false,
                'message' => 'IP del terminal Cardnet no configurada.'
            ], 400);
        }

        $amountToSend = (int) round($amount * 100);
        $url = "http://{$ip}:{$port}/tx_sale";
        Log::info("POS (Cardnet Android): Enviando POST a {$url} con monto {$amountToSend} (centavos)...");

        try {
            $response = Http::timeout($timeout)->post("{$url}?amount={$amountToSend}", [
                'amount' => $amountToSend
            ]);

            Log::debug("POS (Cardnet Android): Respuesta recibida. Status: " . $response->status() . " - Body: " . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                
                $authCode = $data['approbationNumber'] ?? '';
                $txnMessage = $data['txnMessage'] ?? 'Tarjeta Declinada / Error';
                $cardInfo = $data['cardInformation'] ?? [];
                
                $maskedPan = $cardInfo['maskedPAN'] ?? '************0000';
                $cardSubType = $cardInfo['cardSubType'] ?? 'Tarjeta';

                if (!empty($authCode) && $authCode !== '000000') {
                    Log::info("POS (Cardnet Android): APROBADA - Auth: {$authCode}, Tarjeta: {$maskedPan}");
                    return response()->json([
                        'success' => true,
                        'status' => 'approved',
                        'auth_code' => $authCode,
                        'card_number' => $maskedPan,
                        'card_type' => $cardSubType,
                        'message' => $txnMessage
                    ]);
                }

                Log::warning("POS (Cardnet Android): DECLINADA - Mensaje: {$txnMessage}");
                return response()->json([
                    'success' => false,
                    'message' => $txnMessage
                ], 402);
            }

            return response()->json([
                'success' => false,
                'message' => 'El terminal Cardnet Android respondió con error HTTP ' . $response->status()
            ], 502);

        } catch (Exception $e) {
            Log::error("POS (Cardnet Android): Error de conexión: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Fallo de comunicación con Cardnet Android: ' . $e->getMessage()
            ], 504);
        }
    }
}
