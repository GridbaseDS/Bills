<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dgii\CertificationXmlBuilder;
use App\Services\Dgii\XmlSignatureService;
use App\Services\Dgii\DgiiAuthService;
use App\Services\Dgii\DgiiApiService;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CertificationController extends Controller
{
    /**
     * Run a single certification test case.
     * POST /api/dgii/certification/run-single
     * Body: { "encf": "E310000000001" }
     */
    public function runSingle(Request $request)
    {
        $request->validate(['encf' => 'required|string']);
        $encf = $request->input('encf');

        // Check if this is an RFCE request (suffix added by frontend)
        $isRfce = str_contains($encf, '(RFCE)');
        $cleanEncf = trim(str_replace('(RFCE)', '', $encf));

        if ($isRfce) {
            $rfceCases = $this->loadRfceTestCases();
            $testCase = collect($rfceCases)->firstWhere('ENCF', $cleanEncf);
            if ($testCase) {
                $result = $this->executeRfceTestCase($testCase);
                return response()->json($result);
            }
            return response()->json([
                'success' => false,
                'error' => "RFCE test case with eNCF {$cleanEncf} not found.",
            ], 404);
        }

        $testCases = $this->loadEcfTestCases();
        $testCase = collect($testCases)->firstWhere('ENCF', $cleanEncf);

        if (!$testCase) {
            // Fallback: check RFCE cases
            $rfceCases = $this->loadRfceTestCases();
            $testCase = collect($rfceCases)->firstWhere('ENCF', $cleanEncf);
            if ($testCase) {
                $result = $this->executeRfceTestCase($testCase);
                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'error' => "Test case with eNCF {$cleanEncf} not found in test data.",
            ], 404);
        }

        $result = $this->executeEcfTestCase($testCase);
        return response()->json($result);
    }

    /**
     * Run ALL certification test cases in correct 4-phase order.
     * POST /api/dgii/certification/run-all
     *
     * Phase 1: Base types (31, 32≥250k, 41, 43, 44, 45, 46, 47) → ecf.dgii.gov.do
     * Phase 2: Notes (33, 34) → ecf.dgii.gov.do
     * Phase 3: RFCE summaries → fc.dgii.gov.do
     * Phase 4: FC<250k ECFs (32<250k) → ecf.dgii.gov.do
     */
    public function runAll(Request $request)
    {
        $allEcf = $this->loadEcfTestCases();
        $rfceCases = $this->loadRfceTestCases();

        // Classify ECF test cases into phases
        $phase1 = []; // Base types
        $phase2 = []; // Notes 33/34
        $phase4 = []; // FC < 250k

        foreach ($allEcf as $tc) {
            $tipo = (int)$tc['TipoeCF'];
            $monto = (float)($tc['MontoTotal'] ?? 0);

            if (in_array($tipo, [33, 34])) {
                $phase2[] = $tc;
            } elseif ($tipo === 32 && $monto < 250000) {
                $phase4[] = $tc;
            } else {
                $phase1[] = $tc;
            }
        }

        $results = [];

        // Phase 1: Base types → ecf.dgii.gov.do
        Log::info("[Certification] === PHASE 1: Base types (" . count($phase1) . " cases) ===");
        foreach ($phase1 as $tc) {
            $results[] = $this->executeEcfTestCase($tc);
        }

        // Phase 2: Notes 33/34 → ecf.dgii.gov.do
        Log::info("[Certification] === PHASE 2: Notes 33/34 (" . count($phase2) . " cases) ===");
        foreach ($phase2 as $tc) {
            $results[] = $this->executeEcfTestCase($tc);
        }

        // Phase 3: RFCE summaries → fc.dgii.gov.do
        Log::info("[Certification] === PHASE 3: RFCE summaries (" . count($rfceCases) . " cases) ===");
        foreach ($rfceCases as $tc) {
            $results[] = $this->executeRfceTestCase($tc);
        }

        // Phase 4: FC < 250k ECFs → fc.dgii.gov.do (B2C channel, NOT ecf.dgii.gov.do)
        Log::info("[Certification] === PHASE 4: FC<250k ECFs (" . count($phase4) . " cases) ===");
        foreach ($phase4 as $tc) {
            $results[] = $this->executeEcfTestCase($tc, true);
        }

        $passed = collect($results)->where('success', true)->count();
        $failed = collect($results)->where('success', false)->count();

        return response()->json([
            'summary' => [
                'total' => count($results),
                'passed' => $passed,
                'failed' => $failed,
                'phases' => [
                    'phase1_base' => count($phase1),
                    'phase2_notes' => count($phase2),
                    'phase3_rfce' => count($rfceCases),
                    'phase4_fc250k' => count($phase4),
                ],
            ],
            'results' => $results,
        ]);
    }

    /**
     * List all available test cases without executing.
     * GET /api/dgii/certification/list
     */
    public function listCases()
    {
        $ecfCases = $this->loadEcfTestCases();
        $rfceCases = $this->loadRfceTestCases();

        // Classify ECF cases into phases
        $phase1 = []; // Base: 31, 32≥250k, 41, 43, 44, 45, 46, 47
        $phase2 = []; // Notes: 33, 34
        $phase4 = []; // FC<250k individual ECF

        // Type ordering within Phase 1
        $typeOrder = [31 => 1, 32 => 2, 41 => 3, 43 => 4, 44 => 5, 45 => 6, 46 => 7, 47 => 8];

        foreach ($ecfCases as $tc) {
            $tipo = (int)$tc['TipoeCF'];
            $monto = (float)($tc['MontoTotal'] ?? 0);

            $items = [];
            for ($i = 1; $i <= 20; $i++) {
                $name = $tc["NombreItem[$i]"] ?? '#e';
                if ($name !== '#e') $items[] = $name;
            }

            $base = [
                'encf' => $tc['ENCF'],
                'tipo' => $tc['TipoeCF'],
                'razon_social_comprador' => $tc['RazonSocialComprador'] ?? null,
                'monto_total' => $tc['MontoTotal'] ?? null,
                'fecha_emision' => $tc['FechaEmision'] ?? null,
                'items_count' => count($items),
                'items' => $items,
                'format' => 'ECF',
            ];

            if (in_array($tipo, [33, 34])) {
                $base['phase'] = 'Fase 2 — Notas (33/34)';
                $base['phase_order'] = 2;
                $base['type_order'] = $tipo === 33 ? 1 : 2;
                $phase2[] = $base;
            } elseif ($tipo === 32 && $monto < 250000) {
                $base['phase'] = 'Fase 4 — FC<250k Individual';
                $base['phase_order'] = 4;
                $base['type_order'] = 0;
                $phase4[] = $base;
            } else {
                $base['phase'] = 'Fase 1 — Base';
                $base['phase_order'] = 1;
                $base['type_order'] = $typeOrder[$tipo] ?? 99;
                $phase1[] = $base;
            }
        }

        // Sort Phase 1 by type order (31→32→41→43→44→45→46→47)
        usort($phase1, fn($a, $b) => $a['type_order'] <=> $b['type_order']);

        // Sort Phase 2 by type (33 then 34)
        usort($phase2, fn($a, $b) => $a['type_order'] <=> $b['type_order']);

        // Phase 3: RFCE summaries
        $phase3 = [];
        foreach ($rfceCases as $tc) {
            $phase3[] = [
                'encf' => $tc['ENCF'] . ' (RFCE)',
                'tipo' => '32-RFCE',
                'razon_social_comprador' => $tc['RazonSocialComprador'] ?? null,
                'monto_total' => $tc['MontoTotal'] ?? null,
                'fecha_emision' => $tc['FechaEmision'] ?? null,
                'items_count' => 0,
                'items' => [],
                'phase' => 'Fase 3 — RFCE Resumen',
                'phase_order' => 3,
                'type_order' => 0,
                'format' => 'RFCE',
            ];
        }

        // Merge in strict order: Phase 1 → Phase 2 → Phase 3 → Phase 4
        $cases = array_merge($phase1, $phase2, $phase3, $phase4);

        return response()->json(['cases' => array_values($cases)]);
    }

    // ─── ECF Execution ────────────────────────────────

    private function executeEcfTestCase(array $testCase, bool $isRfce = false): array
    {
        $encf = $testCase['ENCF'];
        $tipoECF = $testCase['TipoeCF'];

        try {
            $settings = Setting::getAll();
            $builder = new CertificationXmlBuilder();
            $signatureService = app(XmlSignatureService::class);
            $authService = app(DgiiAuthService::class);
            $apiService = app(DgiiApiService::class);

            // 1. Build raw XML from test data
            Log::info("[Certification] Building ECF XML for {$encf} (Tipo {$tipoECF})");
            $rawXml = $builder->buildFromTestCase($testCase);

            // 1b. Structural pre-check
            $validationDom = new \DOMDocument();
            $validationDom->loadXML($rawXml);
            $missing = [];
            foreach (['TipoeCF', 'eNCF', 'RNCEmisor', 'FechaEmision', 'MontoTotal', 'FechaHoraFirma'] as $req) {
                if ($validationDom->getElementsByTagName($req)->length === 0) {
                    $missing[] = $req;
                }
            }
            if (!empty($missing)) {
                $errorMsg = 'Elementos faltantes: ' . implode(', ', $missing);
                Log::error("[Certification] Structural check failed for {$encf}: {$errorMsg}");
                return [
                    'encf' => $encf,
                    'tipo' => $tipoECF,
                    'success' => false,
                    'status' => 'invalid_structure',
                    'track_id' => null,
                    'errors' => $errorMsg,
                    'xml_path' => null,
                    'format' => 'ECF',
                ];
            }

            // 2. Sign the XML
            $p12Path = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
            $p12Password = $settings['dgii_certificate_password'] ?? '';
            $signedXml = $signatureService->signXml($rawXml, $p12Path, $p12Password);

            // Save signed XML
            $fileName = "certification_ecf/{$encf}.xml";
            Storage::put($fileName, $signedXml);

            // 3. Submit to ecf.dgii.gov.do
            $token = $authService->getValidToken($settings);
            $env = $settings['dgii_env'] ?? 'testing';

            $endpoint = $isRfce ? 'fc.dgii.gov.do' : 'ecf.dgii.gov.do';
            Log::info("[Certification] Submitting ECF {$encf} to {$endpoint}");
            $result = $apiService->submitInvoice($signedXml, $token, $env, $isRfce);

            Log::info("[Certification] ECF Result for {$encf}: " . json_encode($result));

            return [
                'encf' => $encf,
                'tipo' => $tipoECF,
                'success' => $result['success'],
                'status' => $result['status'] ?? null,
                'track_id' => $result['track_id'] ?? null,
                'errors' => $result['errors'] ?? null,
                'xml_path' => $fileName,
                'format' => 'ECF',
            ];

        } catch (\Exception $e) {
            Log::error("[Certification] ECF Error for {$encf}: " . $e->getMessage());
            return [
                'encf' => $encf,
                'tipo' => $tipoECF,
                'success' => false,
                'status' => 'error',
                'track_id' => null,
                'errors' => $e->getMessage(),
                'xml_path' => null,
                'format' => 'ECF',
            ];
        }
    }

    // ─── RFCE Execution ───────────────────────────────

    private function executeRfceTestCase(array $testCase): array
    {
        $encf = $testCase['ENCF'];

        try {
            $settings = Setting::getAll();
            $signatureService = app(XmlSignatureService::class);
            $authService = app(DgiiAuthService::class);
            $apiService = app(DgiiApiService::class);

            // 1. Build RFCE XML
            Log::info("[Certification] Building RFCE XML for {$encf}");
            $rawXml = $this->buildRfceXml($testCase);

            // 2. Sign the XML
            $p12Path = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
            $p12Password = $settings['dgii_certificate_password'] ?? '';
            $signedXml = $signatureService->signXml($rawXml, $p12Path, $p12Password);

            // Save signed XML
            $fileName = "certification_rfce/{$encf}_rfce.xml";
            Storage::put($fileName, $signedXml);

            // 3. Submit to fc.dgii.gov.do (RFCE endpoint)
            $token = $authService->getValidToken($settings);
            $env = $settings['dgii_env'] ?? 'testing';

            Log::info("[Certification] Submitting RFCE {$encf} to fc.dgii.gov.do");
            $result = $apiService->submitInvoice($signedXml, $token, $env, true);

            Log::info("[Certification] RFCE Result for {$encf}: " . json_encode($result));

            return [
                'encf' => $encf . ' (RFCE)',
                'tipo' => '32-RFCE',
                'success' => $result['success'],
                'status' => $result['status'] ?? null,
                'track_id' => $result['track_id'] ?? null,
                'errors' => $result['errors'] ?? null,
                'xml_path' => $fileName,
                'format' => 'RFCE',
            ];

        } catch (\Exception $e) {
            Log::error("[Certification] RFCE Error for {$encf}: " . $e->getMessage());
            return [
                'encf' => $encf . ' (RFCE)',
                'tipo' => '32-RFCE',
                'success' => false,
                'status' => 'error',
                'track_id' => null,
                'errors' => $e->getMessage(),
                'xml_path' => null,
                'format' => 'RFCE',
            ];
        }
    }

    /**
     * Build RFCE XML from test case data.
     * RFCE is a simpler format: <RFCE> with Encabezado only (no DetallesItems).
     */
    private function buildRfceXml(array $tc): string
    {
        $v = function (string $key) use ($tc): ?string {
            $val = $tc[$key] ?? null;
            if ($val === null || $val === '#e') return null;
            return (string)$val;
        };

        $fmt = function ($val): string {
            return number_format((float)$val, 2, '.', '');
        };

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rfce = $dom->createElement('RFCE');
        $dom->appendChild($rfce);

        $enc = $dom->createElement('Encabezado');
        $rfce->appendChild($enc);

        $enc->appendChild($dom->createElement('Version', '1.0'));

        // IdDoc
        $idDoc = $dom->createElement('IdDoc');
        $enc->appendChild($idDoc);
        $idDoc->appendChild($dom->createElement('TipoeCF', $v('TipoeCF')));
        $idDoc->appendChild($dom->createElement('eNCF', $v('ENCF')));

        $tipoIng = $v('TipoIngresos');
        if ($tipoIng !== null) $idDoc->appendChild($dom->createElement('TipoIngresos', str_pad($tipoIng, 2, '0', STR_PAD_LEFT)));

        $tipoPago = $v('TipoPago');
        if ($tipoPago !== null) $idDoc->appendChild($dom->createElement('TipoPago', $tipoPago));

        // TablaFormasPago
        $formas = [];
        for ($i = 1; $i <= 7; $i++) {
            $fp = $v("FormaPago[$i]");
            $mp = $v("MontoPago[$i]");
            if ($fp === null && $mp === null) continue;
            $forma = $dom->createElement('FormaDePago');
            if ($fp !== null) $forma->appendChild($dom->createElement('FormaPago', $fp));
            if ($mp !== null) $forma->appendChild($dom->createElement('MontoPago', $fmt($mp)));
            $formas[] = $forma;
        }
        if (!empty($formas)) {
            $tabla = $dom->createElement('TablaFormasPago');
            foreach ($formas as $f) $tabla->appendChild($f);
            $idDoc->appendChild($tabla);
        }

        // Emisor
        $emisor = $dom->createElement('Emisor');
        $enc->appendChild($emisor);
        $emisor->appendChild($dom->createElement('RNCEmisor', $v('RNCEmisor')));
        $emisor->appendChild($dom->createElement('RazonSocialEmisor', htmlspecialchars($v('RazonSocialEmisor'), ENT_XML1 | ENT_QUOTES, 'UTF-8')));
        $emisor->appendChild($dom->createElement('FechaEmision', $v('FechaEmision')));

        // Comprador
        $comp = $dom->createElement('Comprador');
        $enc->appendChild($comp);

        $rncComp = $v('RNCComprador');
        $idExt = $v('IdentificadorExtranjero');
        if ($rncComp !== null) {
            $comp->appendChild($dom->createElement('RNCComprador', $rncComp));
        } elseif ($idExt !== null) {
            $comp->appendChild($dom->createElement('IdentificadorExtranjero', $idExt));
        }
        $comp->appendChild($dom->createElement('RazonSocialComprador', htmlspecialchars($v('RazonSocialComprador'), ENT_XML1 | ENT_QUOTES, 'UTF-8')));

        // Totales
        $totales = $dom->createElement('Totales');
        $enc->appendChild($totales);

        $moneyFields = [
            'MontoGravadoTotal', 'MontoGravadoI1', 'MontoGravadoI2', 'MontoGravadoI3',
            'MontoExento', 'TotalITBIS', 'TotalITBIS1', 'TotalITBIS2', 'TotalITBIS3',
            'MontoImpuestoAdicional',
        ];
        foreach ($moneyFields as $f) {
            $val = $v($f);
            if ($val !== null) $totales->appendChild($dom->createElement($f, $fmt($val)));
        }

        // ImpuestosAdicionales
        $impItems = [];
        for ($i = 1; $i <= 4; $i++) {
            $tipo = $v("TipoImpuesto[$i]");
            if ($tipo === null) continue;
            $imp = $dom->createElement('ImpuestoAdicional');
            $imp->appendChild($dom->createElement('TipoImpuesto', $tipo));
            $especifico = $v("MontoImpuestoSelectivoConsumoEspecifico[$i]");
            if ($especifico !== null) $imp->appendChild($dom->createElement('MontoImpuestoSelectivoConsumoEspecifico', $fmt($especifico)));
            $advalorem = $v("MontoImpuestoSelectivoConsumoAdvalorem[$i]");
            if ($advalorem !== null) $imp->appendChild($dom->createElement('MontoImpuestoSelectivoConsumoAdvalorem', $fmt($advalorem)));
            $otros = $v("OtrosImpuestosAdicionales[$i]");
            if ($otros !== null) $imp->appendChild($dom->createElement('OtrosImpuestosAdicionales', $fmt($otros)));
            $impItems[] = $imp;
        }
        if (!empty($impItems)) {
            $impWrapper = $dom->createElement('ImpuestosAdicionales');
            foreach ($impItems as $imp) $impWrapper->appendChild($imp);
            $totales->appendChild($impWrapper);
        }

        $montoTotal = $v('MontoTotal');
        if ($montoTotal !== null) $totales->appendChild($dom->createElement('MontoTotal', $fmt($montoTotal)));

        $montoNF = $v('MontoNoFacturable');
        if ($montoNF !== null) $totales->appendChild($dom->createElement('MontoNoFacturable', $fmt($montoNF)));

        $montoPeriodo = $v('MontoPeriodo');
        if ($montoPeriodo !== null) $totales->appendChild($dom->createElement('MontoPeriodo', $fmt($montoPeriodo)));

        // FechaHoraFirma
        $rfce->appendChild($dom->createElement('FechaHoraFirma', date('d-m-Y H:i:s')));

        return $dom->saveXML();
    }

    // ─── Data Loaders ─────────────────────────────────

    private function loadEcfTestCases(): array
    {
        $path = base_path('dgii_test_ecf.json');
        if (!file_exists($path)) {
            throw new \RuntimeException("Test cases file not found: dgii_test_ecf.json");
        }

        // JSON was generated from DGII XLSX with all values as exact strings.
        // No regex quoting or number conversion needed.
        $data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in ECF test cases: " . json_last_error_msg());
        }

        return $data;
    }

    private function loadRfceTestCases(): array
    {
        $path = base_path('dgii_test_rfce.json');
        if (!file_exists($path)) {
            Log::warning("[Certification] RFCE test data not found: dgii_test_rfce.json");
            return [];
        }

        $data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in RFCE test cases: " . json_last_error_msg());
        }

        return $data;
    }
}
