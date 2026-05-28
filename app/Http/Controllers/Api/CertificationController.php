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

        $testCases = $this->loadTestCases();
        $testCase = collect($testCases)->firstWhere('ENCF', $encf);

        if (!$testCase) {
            return response()->json([
                'success' => false,
                'error' => "Test case with eNCF {$encf} not found in test data.",
            ], 404);
        }

        $result = $this->executeTestCase($testCase);

        return response()->json($result);
    }

    /**
     * Run ALL certification test cases.
     * POST /api/dgii/certification/run-all
     */
    public function runAll(Request $request)
    {
        $testCases = $this->loadTestCases();
        $results = [];

        foreach ($testCases as $testCase) {
            $results[] = $this->executeTestCase($testCase);
        }

        $passed = collect($results)->where('success', true)->count();
        $failed = collect($results)->where('success', false)->count();

        return response()->json([
            'summary' => [
                'total' => count($results),
                'passed' => $passed,
                'failed' => $failed,
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
        $testCases = $this->loadTestCases();
        $cases = [];

        foreach ($testCases as $tc) {
            $items = [];
            for ($i = 1; $i <= 20; $i++) {
                $name = $tc["NombreItem[$i]"] ?? '#e';
                if ($name !== '#e') $items[] = $name;
            }

            $cases[] = [
                'encf' => $tc['ENCF'],
                'tipo' => $tc['TipoeCF'],
                'razon_social_comprador' => $tc['RazonSocialComprador'] ?? null,
                'monto_total' => $tc['MontoTotal'] ?? null,
                'fecha_emision' => $tc['FechaEmision'] ?? null,
                'items_count' => count($items),
                'items' => $items,
            ];
        }

        return response()->json(['cases' => $cases]);
    }

    // ─── Internal ──────────────────────────────────────

    private function executeTestCase(array $testCase): array
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
            Log::info("[Certification] Building XML for {$encf} (Tipo {$tipoECF})");
            $rawXml = $builder->buildFromTestCase($testCase);

            // 1b. Structural pre-check (DGII XSDs have internal type errors, so we validate structure manually)
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
                ];
            }
            Log::info("[Certification] Structural check passed for {$encf}");

            // 2. Sign the XML
            $p12Path = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
            $p12Password = $settings['dgii_certificate_password'] ?? '';

            Log::info("[Certification] Signing XML for {$encf}");
            $signedXml = $signatureService->signXml($rawXml, $p12Path, $p12Password);

            // Save signed XML for archiving/debugging
            $fileName = "certification_ecf/{$encf}.xml";
            Storage::put($fileName, $signedXml);

            // 3. Get auth token
            $token = $authService->getValidToken($settings);
            $env = $settings['dgii_env'] ?? 'testing';

            // 4. Determine if RFCE (FC<250k) or regular
            $isFcLessThan250k = (int)$tipoECF === 32 && ($testCase['MontoTotal'] ?? 0) < 250000;

            // 5. Submit to DGII
            Log::info("[Certification] Submitting {$encf} to DGII ({$env})");
            $result = $apiService->submitInvoice($signedXml, $token, $env, $isFcLessThan250k);

            Log::info("[Certification] Result for {$encf}: " . json_encode($result));

            return [
                'encf' => $encf,
                'tipo' => $tipoECF,
                'success' => $result['success'],
                'status' => $result['status'] ?? null,
                'track_id' => $result['track_id'] ?? null,
                'errors' => $result['errors'] ?? null,
                'xml_path' => $fileName,
            ];

        } catch (\Exception $e) {
            Log::error("[Certification] Error for {$encf}: " . $e->getMessage());

            return [
                'encf' => $encf,
                'tipo' => $tipoECF,
                'success' => false,
                'status' => 'error',
                'track_id' => null,
                'errors' => $e->getMessage(),
                'xml_path' => null,
            ];
        }
    }

    private function loadTestCases(): array
    {
        $path = base_path('dgii_test_ecf.json');
        if (!file_exists($path)) {
            throw new \RuntimeException("Test cases file not found: dgii_test_ecf.json");
        }

        $data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in test cases: " . json_last_error_msg());
        }

        return $data;
    }
}
