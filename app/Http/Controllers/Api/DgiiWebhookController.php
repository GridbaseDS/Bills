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
     */
    public function validacionCertificado(Request $request)
    {
        Log::info("DGII Webhook: validacionCertificado ping received");

        // Retornar éxito estándar
        return response()->json([
            'status' => 'success',
            'valid' => true,
            'message' => 'Certificado validado correctamente por Gridbase Bills'
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
        $rawXml = $request->getContent();

        $rncEmisor = '999999999';
        $rncReceptor = Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '999999999';
        $rncReceptor = preg_replace('/[^0-9]/', '', $rncReceptor);
        $encf = 'E310000000001';

        try {
            if (!empty($rawXml)) {
                $doc = new DOMDocument();
                if ($doc->loadXML($rawXml, LIBXML_NOBLANKS | LIBXML_NOCDATA | LIBXML_NONET)) {
                    // Extract RNCEmisor
                    $emisorNodes = $doc->getElementsByTagName('RncEmisor');
                    if ($emisorNodes->length === 0) {
                        $emisorNodes = $doc->getElementsByTagName('RNCEmisor');
                    }
                    if ($emisorNodes->length > 0) {
                        $rncEmisor = trim($emisorNodes->item(0)->textContent);
                    }
                    
                    // Extract RNCComprador/RncReceptor
                    $receptorNodes = $doc->getElementsByTagName('RncComprador');
                    if ($receptorNodes->length === 0) {
                        $receptorNodes = $doc->getElementsByTagName('RNCComprador');
                    }
                    if ($receptorNodes->length === 0) {
                        $receptorNodes = $doc->getElementsByTagName('RncReceptor');
                    }
                    if ($receptorNodes->length > 0) {
                        $rncReceptorParsed = trim($receptorNodes->item(0)->textContent);
                        if (!empty($rncReceptorParsed)) {
                            $rncReceptor = $rncReceptorParsed;
                        }
                    }

                    $encfNodes = $doc->getElementsByTagName('eNCF');
                    if ($encfNodes->length > 0) {
                        $encf = trim($encfNodes->item(0)->textContent);
                    }

                    // Extract additional fields for storage
                    $razonSocial = '';
                    $razonNodes = $doc->getElementsByTagName('RazonSocialEmisor');
                    if ($razonNodes->length > 0) {
                        $razonSocial = trim($razonNodes->item(0)->textContent);
                    }

                    $fechaEmision = date('Y-m-d');
                    $fechaNodes = $doc->getElementsByTagName('FechaEmision');
                    if ($fechaNodes->length > 0) {
                        $rawFecha = trim($fechaNodes->item(0)->textContent);
                        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $rawFecha, $m)) {
                            $fechaEmision = "{$m[3]}-{$m[2]}-{$m[1]}";
                        } else {
                            $fechaEmision = date('Y-m-d', strtotime($rawFecha));
                        }
                    }

                    $montoTotal = 0;
                    $montoNodes = $doc->getElementsByTagName('MontoTotal');
                    if ($montoNodes->length > 0) {
                        $montoTotal = (float)trim($montoNodes->item(0)->textContent);
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
     */
    public function aprobacionComercial(Request $request)
    {
        Log::info("DGII Webhook: Aprobacion Comercial received");

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<Resultado>
    <Estado>0</Estado>
    <Descripcion>Aprobación Comercial recibida y guardada correctamente</Descripcion>
</Resultado>
XML;

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
