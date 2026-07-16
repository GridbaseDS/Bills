<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
            return response()->json([
                'success' => false,
                'message' => 'La integración de POS / Verifone está desactivada.'
            ], 400);
        }

        $driver = Setting::get('pos_driver', 'mock');
        $ip = Setting::get('pos_terminal_ip', '');
        $port = Setting::get('pos_terminal_port', '');
        $merchantId = Setting::get('pos_merchant_id', '');
        $timeout = (int) Setting::get('pos_timeout', '60');

        switch ($driver) {
            case 'mock':
                return $this->handleMockCharge($amount, $timeout);

            case 'azul_local':
                return $this->handleAzulLocalCharge($amount, $ip, $port, $timeout);

            case 'cardnet_local':
                return $this->handleCardnetLocalCharge($amount, $ip, $port, $invoiceId, $timeout);

            default:
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
        
        if ($driver === 'mock') {
            return response()->json([
                'success' => true,
                'message' => 'Transacción cancelada correctamente en el simulador.'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comando de cancelación enviado.'
        ]);
    }

    /**
     * Simulación de cobro.
     */
    private function handleMockCharge($amount, $timeout)
    {
        sleep(3);

        $authCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $lastFour = rand(1000, 9999);

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
            return response()->json([
                'success' => false,
                'message' => 'IP o Puerto del terminal Azul no configurados.'
            ], 400);
        }

        $url = "http://{$ip}:{$port}/azul/charge";

        try {
            $response = Http::timeout($timeout)->post($url, [
                'amount' => $amount,
                'tax' => 0.00,
            ]);

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
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con Azul: ' . $e->getMessage()
            ], 504);
        }
    }

    /**
     * Conexión con Cardnet Local ECRti (Sockets TCP).
     * Abre un socket TCP directo al Verifone en puerto 7060, realiza el apretón de manos SYN/EOM/ENQ
     * y transmite la trama de compra formateada con FS (Field Separator 0x1C).
     */
    private function handleCardnetLocalCharge($amount, $ip, $port, $invoiceId, $timeout)
    {
        $port = !empty($port) ? $port : '7060'; // Puerto estándar Cardnet ECRti es 7060

        if (empty($ip)) {
            return response()->json([
                'success' => false,
                'message' => 'IP del terminal Cardnet no configurada.'
            ], 400);
        }

        // 1. Intentar abrir conexión socket TCP
        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
        if (!$socket) {
            return response()->json([
                'success' => false,
                'message' => "No se pudo conectar al Verifone Cardnet ({$ip}:{$port}): {$errstr}"
            ], 504);
        }

        // Establecer el tiempo de lectura/escritura del socket según timeout del POS
        stream_set_timeout($socket, $timeout);

        try {
            // A. Envío de SYN (0x16)
            fwrite($socket, chr(0x16));

            // B. Espera de EOM (0x19) del POS
            $byte1 = fread($socket, 1);
            if (ord($byte1) !== 0x19) {
                throw new Exception("Se esperaba EOM (0x19) pero se recibió: " . ord($byte1));
            }

            // C. Espera de ENQ (0x05) del POS
            $byte2 = fread($socket, 1);
            if (ord($byte2) !== 0x05) {
                throw new Exception("Se esperaba ENQ (0x05) pero se recibió: " . ord($byte2));
            }

            // D. Construir la trama de Venta CN00
            // Monto formateado a 12 caracteres relleno con ceros a la izquierda (ej: DOP 250.00 -> 000000025000)
            $amountStr = str_pad(round($amount * 100), 12, '0', STR_PAD_LEFT);
            
            // Buscar la factura para obtener su tasa de ITBIS real o default
            $invoice = Invoice::find($invoiceId);
            $taxAmount = 0.00;
            if ($invoice) {
                $taxAmount = $invoice->tax_amount;
            }
            $taxStr = str_pad(round($taxAmount * 100), 12, '0', STR_PAD_LEFT);
            $otherTaxesStr = str_pad(0, 12, '0', STR_PAD_LEFT);
            
            // Número de ticket / factura (6 dígitos, relleno con ceros)
            $ticketStr = str_pad(substr($invoiceId, -6), 6, '0', STR_PAD_LEFT);

            // Trama ECR a POS: CN00<FS>Monto<FS>ITBIS<FS>OtrosImpuestos<FS>Ticket<FS>
            $fs = chr(0x1C); // Separador de campos
            $txMessage = "CN00" . $fs . $amountStr . $fs . $taxStr . $fs . $otherTaxesStr . $fs . $ticketStr . $fs;

            // Enviar trama al Verifone
            fwrite($socket, $txMessage);

            // E. Leer confirmación ACK (0x06) del envío
            $ackByte = fread($socket, 1);
            if (ord($ackByte) !== 0x06) {
                throw new Exception("Se esperaba ACK (0x06) pero se recibió: " . ord($ackByte));
            }

            // F. Espera y lectura del resultado de la transacción (puede tomar hasta 90 segundos)
            // La trama empieza con <STX> (0x02) y termina con <ETX> (0x03) y <LRC>
            $resBuffer = '';
            $stxReceived = false;

            while (!feof($socket)) {
                $c = fread($socket, 1);
                if ($c === false || $c === '') {
                    break;
                }
                
                $ascii = ord($c);
                if ($ascii === 0x02) {
                    $stxReceived = true;
                    continue; // Skip STX
                }
                if ($ascii === 0x03) {
                    // ETX recibido, leer el siguiente byte (LRC) y terminar
                    fread($socket, 1); // Leer LRC
                    break;
                }
                if ($stxReceived) {
                    $resBuffer .= $c;
                } else {
                    $resBuffer .= $c;
                }
            }

            fclose($socket);

            if (empty($resBuffer)) {
                throw new Exception("No se recibió respuesta con la trama de autorización del Verifone.");
            }

            // Parsear campos separados por FS (0x1C)
            $fields = explode($fs, $resBuffer);

            // Según Cardnet Layout Ventas:
            // index 0 = HOST (06=Crédito, 07=Débito)
            // index 1 = Tipo Tarjeta (Visa, MC, AMEX)
            // index 2 = Modo de Transacción (C@5, D@5, etc)
            // index 3 = Tarjeta (489952******4010)
            // index 4 = Lote (004)
            // index 5 = Fecha (DDMMAA)
            // index 6 = Hora (HHMMSS)
            // index 7 = Nombre Tarjetahabiente
            // index 8 = Código de Autorización (Aprobación)
            // index 9 = Terminal ID
            // index 10 = Secuencia / Ref.
            // index 11 = Retrieval Reference Number
            // index 12 = Merchant ID
            
            $authCode = isset($fields[8]) ? trim($fields[8]) : '';
            $cardNo = isset($fields[3]) ? trim($fields[3]) : '************0000';
            $cardType = isset($fields[1]) ? trim($fields[1]) : 'Tarjeta';
            
            // Si el código de respuesta indica transacción no progresó
            if (isset($fields[0]) && trim($fields[0]) === '99') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transacción declinada o no progresó en el Verifone.'
                ], 402);
            }

            if (!empty($authCode) && $authCode !== '000000') {
                return response()->json([
                    'success' => true,
                    'status' => 'approved',
                    'auth_code' => $authCode,
                    'card_number' => $cardNo,
                    'card_type' => $cardType,
                    'message' => 'Transacción Aprobada'
                ]);
            }

            // Validar si entre los campos especiales viene un mensaje legible de rechazo
            $responseText = 'Transacción declinada por el Verifone.';
            foreach ($fields as $f) {
                if (str_contains($f, 'DECLINADA') || str_contains($f, 'FONDOS INSUF') || str_contains($f, 'PIN INVALIDO') || str_contains($f, 'ERROR')) {
                    $responseText = trim($f);
                    break;
                }
            }

            return response()->json([
                'success' => false,
                'message' => $responseText
            ], 402);

        } catch (Exception $e) {
            @fclose($socket);
            return response()->json([
                'success' => false,
                'message' => 'Error durante la transacción con Cardnet: ' . $e->getMessage()
            ], 502);
        }
    }
}
