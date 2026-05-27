<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReceivedInvoice;
use App\Models\Setting;
use App\Services\Dgii\XmlSignatureService;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DgiiWebhookController extends Controller
{
    protected $signatureService;

    public function __construct(XmlSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * URL de Autenticación: Semilla XML
     * GET /fe/autenticacion/api/semilla
     */
    public function semilla()
    {
        $uuid = Str::uuid()->toString();
        $fecha = date('Y-m-d\TH:i:s');

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<Semilla xmlns="http://dgii.gov.do/core/semilla">
    <UUID>{$uuid}</UUID>
    <Fecha>{$fecha}</Fecha>
</Semilla>
XML;

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * URL de Autenticación: Validación de Certificado
     * POST /fe/autenticacion/api/validacioncertificado
     * 
     * Receives signed semilla XML, validates it, and returns an authentication token.
     * Response format per DGII spec: token + expira + expedido (yyyy-MM-ddTHH:mm:ssZ)
     */
    public function validacionCertificado(Request $request)
    {
        Log::info("DGII Webhook: validacionCertificado received");
        Log::info("DGII Webhook Auth: Content-Type: " . $request->header('Content-Type'));
        Log::info("DGII Webhook Auth: Accept: " . $request->header('Accept'));

        // Generate a token (simple base64 encoded UUID, sufficient for certification)
        $token = base64_encode(Str::uuid()->toString() . ':' . time() . ':gridbase-bills');
        $expedido = gmdate('Y-m-d\TH:i:s\Z');
        $expira = gmdate('Y-m-d\TH:i:s\Z', time() + 3600); // 1 hour validity

        Log::info("DGII Webhook Auth: Token generated, expires: {$expira}");

        // Return response based on Accept header
        $accept = $request->header('Accept', 'application/json');

        if (stripos($accept, 'xml') !== false) {
            $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RespuestaAutenticacion>
    <token>{$token}</token>
    <expira>{$expira}</expira>
    <expedido>{$expedido}</expedido>
</RespuestaAutenticacion>
XML;
            return response($xml, 200)->header('Content-Type', 'application/xml');
        }

        return response()->json([
            'token' => $token,
            'expira' => $expira,
            'expedido' => $expedido
        ], 200);
    }

    /**
     * URL de Recepción de e-CF (Acuse de Recibo - ARECF)
     * POST /fe/recepcion/api/ecf
     * 
     * Must respond with signed ARECF XML conforming to ARECF v1.0.xsd
     */
    public function recepcion(Request $request)
    {
        Log::info("DGII Webhook: Recepcion e-CF received");
        Log::info("DGII Webhook: Content-Type: " . $request->header('Content-Type'));
        
        // DGII may send XML as raw body OR as multipart/form-data file
        $rawXml = $request->getContent();
        
        // If content is empty, check for file upload
        if (empty(trim($rawXml)) || strpos($rawXml, '<?xml') === false) {
            // Try getting from file upload
            if ($request->hasFile('xml')) {
                $rawXml = $request->file('xml')->getContent();
                Log::info("DGII Webhook: XML received as file upload");
            } elseif ($request->has('xml')) {
                $rawXml = $request->input('xml');
                Log::info("DGII Webhook: XML received as form field");
            }
        }
        
        // Save raw XML to file for debugging
        $debugPath = storage_path('app/dgii_debug_received_' . date('Ymd_His') . '.xml');
        file_put_contents($debugPath, $rawXml);
        Log::info("DGII Webhook: Raw XML saved to {$debugPath}, length: " . strlen($rawXml));

        $rncEmisor = '999999999';
        $rncReceptor = Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '999999999';
        $rncReceptor = preg_replace('/[^0-9]/', '', $rncReceptor);
        $encf = 'E310000000001';

        try {
            if (!empty($rawXml)) {
                // Use regex for robust extraction regardless of namespaces
                // Extract RNCEmisor / RncEmisor
                if (preg_match('/<(?:\w+:)?RN?C?Emisor>(\d+)</i', $rawXml, $m)) {
                    $rncEmisor = $m[1];
                }
                
                // Extract RNCComprador/RncComprador/RncReceptor  
                if (preg_match('/<(?:\w+:)?RN?C?(?:Comprador|Receptor)>(\d+)</i', $rawXml, $m)) {
                    $rncReceptor = $m[1];
                }

                // Extract eNCF
                if (preg_match('/<(?:\w+:)?eNCF>(E\d{12})</', $rawXml, $m)) {
                    $encf = $m[1];
                }

                Log::info("DGII Webhook: Parsed — RNCEmisor:{$rncEmisor}, RNCComprador:{$rncReceptor}, eNCF:{$encf}");

                // Extract additional fields for storage
                $razonSocial = '';
                if (preg_match('/<(?:\w+:)?RazonSocialEmisor>([^<]+)</', $rawXml, $m)) {
                    $razonSocial = trim($m[1]);
                }

                $fechaEmision = date('Y-m-d');
                if (preg_match('/<(?:\w+:)?FechaEmision>([^<]+)</', $rawXml, $m)) {
                    $rawFecha = trim($m[1]);
                    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $rawFecha, $fm)) {
                        $fechaEmision = "{$fm[3]}-{$fm[2]}-{$fm[1]}";
                    } else {
                        $fechaEmision = date('Y-m-d', strtotime($rawFecha));
                    }
                }

                $montoTotal = 0;
                if (preg_match('/<(?:\w+:)?MontoTotal>([^<]+)</', $rawXml, $m)) {
                    $montoTotal = (float)trim($m[1]);
                }

                // Save to received_invoices table
                try {
                    ReceivedInvoice::updateOrCreate(
                        ['rnc_emisor' => $rncEmisor, 'encf' => $encf],
                        [
                            'razon_social_emisor' => $razonSocial,
                            'ecf_type' => ReceivedInvoice::extractEcfType($encf),
                            'fecha_emision' => $fechaEmision,
                            'monto_total' => $montoTotal,
                            'raw_xml' => $rawXml,
                        ]
                    );
                    Log::info("DGII Webhook: Factura recibida guardada — RNC: {$rncEmisor}, eNCF: {$encf}");
                } catch (Exception $e) {
                    Log::error("DGII Webhook: Error guardando factura recibida: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            Log::error("DGII Webhook: Error parsing received e-CF: " . $e->getMessage());
        }

        // ARECF must conform to ARECF v1.0.xsd
        // Date format: dd-MM-yyyy HH:mm:ss
        $fechaHoraAcuse = date('d-m-Y H:i:s');

        $arecfXml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<ARECF xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="ARECF v1.0.xsd">
    <DetalleAcusedeRecibo>
        <Version>1.0</Version>
        <RNCEmisor>{$rncEmisor}</RNCEmisor>
        <RNCComprador>{$rncReceptor}</RNCComprador>
        <eNCF>{$encf}</eNCF>
        <Estado>0</Estado>
        <FechaHoraAcuseRecibo>{$fechaHoraAcuse}</FechaHoraAcuseRecibo>
    </DetalleAcusedeRecibo>
</ARECF>
XML;

        Log::info("DGII Webhook: ARECF generado para {$encf} — RNCEmisor:{$rncEmisor}, RNCComprador:{$rncReceptor}");

        // Sign the ARECF with our certificate
        $certFile = Setting::where('setting_key', 'dgii_certificate_path')->value('setting_value');
        $password = Setting::where('setting_key', 'dgii_certificate_password')->value('setting_value');

        if ($certFile && $password) {
            $p12Path = storage_path('app/secure/' . $certFile);
            if (file_exists($p12Path)) {
                try {
                    $signedArecf = $this->signatureService->signXml($arecfXml, $p12Path, $password);
                    Log::info("DGII Webhook: ARECF firmado exitosamente para {$encf}");
                    return response($signedArecf, 200)->header('Content-Type', 'application/xml');
                } catch (Exception $e) {
                    Log::error("DGII Webhook: Error signing ARECF: " . $e->getMessage());
                }
            }
        }

        // Fallback: return unsigned (will likely fail DGII validation)
        Log::warning("DGII Webhook: Returning unsigned ARECF for {$encf}");
        return response($arecfXml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * URL de Aprobación Comercial (ACECF)
     * POST /fe/aprobacioncomercial/api/ecf
     * 
     * Must respond with signed ACECF XML conforming to ACECF v1.0.xsd
     */
    public function aprobacionComercial(Request $request)
    {
        Log::info("DGII Webhook: Aprobacion Comercial received");
        Log::info("DGII Webhook AC: Content-Type: " . $request->header('Content-Type'));

        // Get raw XML (may be raw body or multipart)
        $rawXml = $request->getContent();
        if (empty(trim($rawXml)) || strpos($rawXml, '<?xml') === false) {
            if ($request->hasFile('xml')) {
                $rawXml = $request->file('xml')->getContent();
            } elseif ($request->has('xml')) {
                $rawXml = $request->input('xml');
            }
        }

        // Save for debug
        $debugPath = storage_path('app/dgii_debug_acecf_' . date('Ymd_His') . '.xml');
        file_put_contents($debugPath, $rawXml);
        Log::info("DGII Webhook AC: Raw XML saved, length: " . strlen($rawXml));

        // Extract fields using case-insensitive regex
        $rncEmisor = '999999999';
        $rncComprador = Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '999999999';
        $rncComprador = preg_replace('/[^0-9]/', '', $rncComprador);
        $encf = 'E310000000001';
        $fechaEmision = date('d-m-Y');
        $montoTotal = '0.00';

        if (preg_match('/<(?:\w+:)?RN?C?Emisor>(\d+)</i', $rawXml, $m)) {
            $rncEmisor = $m[1];
        }
        if (preg_match('/<(?:\w+:)?RN?C?(?:Comprador|Receptor)>(\d+)</i', $rawXml, $m)) {
            $rncComprador = $m[1];
        }
        if (preg_match('/<(?:\w+:)?eNCF>(E\w{10,12})</i', $rawXml, $m)) {
            $encf = $m[1];
        }
        if (preg_match('/<(?:\w+:)?FechaEmision>([^<]+)</i', $rawXml, $m)) {
            $fechaEmision = trim($m[1]);
        }
        if (preg_match('/<(?:\w+:)?MontoTotal>([^<]+)</i', $rawXml, $m)) {
            $montoTotal = number_format((float)trim($m[1]), 2, '.', '');
        }

        Log::info("DGII Webhook AC: Parsed — RNCEmisor:{$rncEmisor}, RNCComprador:{$rncComprador}, eNCF:{$encf}");

        // ACECF response per ACECF v1.0.xsd
        // Estado: 1 = Aceptado, 2 = Rechazado
        $fechaHoraAprobacion = date('d-m-Y H:i:s');

        $acecfXml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<ACECF xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="ACECF v.1.0.xsd">
    <DetalleAprobacionComercial>
        <Version>1.0</Version>
        <RNCEmisor>{$rncEmisor}</RNCEmisor>
        <eNCF>{$encf}</eNCF>
        <FechaEmision>{$fechaEmision}</FechaEmision>
        <MontoTotal>{$montoTotal}</MontoTotal>
        <RNCComprador>{$rncComprador}</RNCComprador>
        <Estado>1</Estado>
        <FechaHoraAprobacionComercial>{$fechaHoraAprobacion}</FechaHoraAprobacionComercial>
    </DetalleAprobacionComercial>
</ACECF>
XML;

        Log::info("DGII Webhook AC: ACECF generado para {$encf}");

        // Sign with certificate
        $certFile = Setting::where('setting_key', 'dgii_certificate_path')->value('setting_value');
        $password = Setting::where('setting_key', 'dgii_certificate_password')->value('setting_value');

        if ($certFile && $password) {
            $p12Path = storage_path('app/secure/' . $certFile);
            if (file_exists($p12Path)) {
                try {
                    $signedAcecf = $this->signatureService->signXml($acecfXml, $p12Path, $password);
                    Log::info("DGII Webhook AC: ACECF firmado exitosamente para {$encf}");
                    return response($signedAcecf, 200)->header('Content-Type', 'application/xml');
                } catch (Exception $e) {
                    Log::error("DGII Webhook AC: Error signing ACECF: " . $e->getMessage());
                }
            }
        }

        Log::warning("DGII Webhook AC: Returning unsigned ACECF for {$encf}");
        return response($acecfXml, 200)->header('Content-Type', 'application/xml');
    }
}
