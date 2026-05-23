<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Dgii\DgiiAuthService;
use App\Services\Dgii\XmlSignatureService;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class RunDgiiAprobacionesCommand extends Command
{
    protected $signature = 'dgii:run-aprobaciones {file? : Path to the XLSX file with test data}';
    protected $description = 'Sends DGII Aprobaciones Comerciales (ACECF) from the certification test dataset';

    public function handle(DgiiAuthService $authService, XmlSignatureService $signatureService)
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║  DGII Aprobaciones Comerciales — Pruebas de Certificación   ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');

        // ── 1. Load test data ──
        $filePath = $this->argument('file');
        if (!$filePath) {
            // Try default location
            $filePath = storage_path('app/dgii_tests/aprobaciones_comerciales.json');
            if (!file_exists($filePath)) {
                $this->error('No data file specified. Use: php artisan dgii:run-aprobaciones path/to/file.xlsx');
                $this->info('Or place a JSON array at: storage/app/dgii_tests/aprobaciones_comerciales.json');
                return 1;
            }
        }

        // Handle XLSX or JSON
        $testData = $this->loadTestData($filePath);
        if (empty($testData)) {
            $this->error('No test data found in file.');
            return 1;
        }

        $count = count($testData);
        $this->info("Found {$count} Aprobaciones Comerciales to send.");
        $this->newLine();

        // ── 2. Load settings & authenticate ──
        $settings = Setting::getAll();
        $rncComprador = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
        $env = $settings['dgii_env'] ?? 'testing';

        $this->info("[1/3] Authenticating with DGII...");
        try {
            Cache::forget("dgii_bearer_token_{$rncComprador}_{$env}");
            $token = $authService->getValidToken($settings);
            $this->info("  ✅ Token obtained.");
        } catch (Exception $e) {
            $this->error("  ❌ Auth failed: " . $e->getMessage());
            return 1;
        }

        // ── 3. Certificate ──
        $certPath = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
        $certPass = $settings['dgii_certificate_password'] ?? '';
        if (!file_exists($certPath)) {
            $this->error("Certificate not found at: {$certPath}");
            return 1;
        }
        $this->info("  ✅ Certificate loaded.");

        // ── 4. Endpoint ──
        $baseUrl = $env === 'production'
            ? 'https://ecf.dgii.gov.do/ecf'
            : 'https://ecf.dgii.gov.do/CerteCF';
        $endpoint = "{$baseUrl}/AprobacionComercial/api/AprobacionComercial";
        $this->info("  📡 Endpoint: {$endpoint}");
        $this->newLine();

        // ── 5. Process each Aprobación Comercial ──
        $this->info("[2/3] Generating, signing, and sending ACECFs...");
        $this->newLine();
        
        $success = 0;
        $failed = 0;

        foreach ($testData as $i => $row) {
            $num = $i + 1;
            $encf = $row['eNCF'] ?? $row['encf'] ?? 'unknown';
            $rncEmisor = $row['RNCEmisor'] ?? $row['rncEmisor'] ?? '';
            $fechaEmision = $row['FechaEmision'] ?? $row['fechaEmision'] ?? '';
            $montoTotal = $row['MontoTotal'] ?? $row['montoTotal'] ?? 0;
            $estado = $row['Estado'] ?? $row['estado'] ?? 1;
            $detalle = $row['DetalleMotivoRechazo'] ?? $row['detalleMotivoRechazo'] ?? '';
            $fechaAprobacion = $row['FechaHoraAprobacionComercial'] ?? $row['fechaHoraAprobacionComercial'] ?? '';
            $version = $row['Version'] ?? $row['version'] ?? '1.0';

            $this->info("  [{$num}/{$count}] e-NCF: {$encf}");
            $this->comment("    RNCEmisor: {$rncEmisor} | Monto: {$montoTotal} | Estado: {$estado}");

            // ── Build ACECF XML ──
            // Format MontoTotal: always 2 decimal places
            $montoFormatted = number_format((float)$montoTotal, 2, '.', '');

            // Format dates for XML (dd-mm-yyyy → dd-mm-yyyy, keep as-is)
            $fechaEmisionXml = $this->formatDate($fechaEmision);
            $fechaAprobacionXml = $this->formatDateTime($fechaAprobacion);

            $detalleTag = '';
            if (!empty($detalle)) {
                $detalleTag = "\n    <DetalleMotivoRechazo>" . htmlspecialchars($detalle) . "</DetalleMotivoRechazo>";
            }

            $acecfXml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<ACECF xmlns="urn:dgii.gov.do:ACECF" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <DetalleAprobacionComercial>
    <Version>{$version}</Version>
    <RNCEmisor>{$rncEmisor}</RNCEmisor>
    <eNCF>{$encf}</eNCF>
    <FechaEmision>{$fechaEmisionXml}</FechaEmision>
    <MontoTotal>{$montoFormatted}</MontoTotal>
    <RNCComprador>{$rncComprador}</RNCComprador>
    <Estado>{$estado}</Estado>{$detalleTag}
    <FechaHoraAprobacionComercial>{$fechaAprobacionXml}</FechaHoraAprobacionComercial>
  </DetalleAprobacionComercial>
</ACECF>
XML;

            // ── Sign ──
            try {
                $signedXml = $signatureService->signXml($acecfXml, $certPath, $certPass);
                $this->comment("    ✍️ Signed OK");
            } catch (Exception $e) {
                $this->error("    ❌ Sign failed: " . $e->getMessage());
                $failed++;
                continue;
            }

            // ── Build filename: {RNCComprador}{eNCF}.xml ──
            $sendFilename = "{$rncComprador}{$encf}.xml";

            // ── Send ──
            try {
                $response = Http::withoutVerifying()
                    ->timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ])
                    ->attach('xml', $signedXml, $sendFilename, ['Content-Type' => 'text/xml'])
                    ->post($endpoint);

                $statusCode = $response->status();
                $body = $response->body();

                if ($response->successful()) {
                    $this->info("    ✅ HTTP {$statusCode} — Enviado exitosamente");
                    $this->comment("    📋 Respuesta: " . substr($body, 0, 200));
                    $success++;
                } else {
                    $this->error("    ❌ HTTP {$statusCode} — Rechazado");
                    $this->error("    📋 Respuesta: {$body}");
                    $failed++;
                }
            } catch (Exception $e) {
                $this->error("    ❌ Connection error: " . $e->getMessage());
                $failed++;
            }

            $this->newLine();

            // Small delay to avoid rate limiting
            usleep(500000); // 500ms
        }

        // ── Summary ──
        $this->newLine();
        $this->info('[3/3] ══════════ RESUMEN ══════════');
        $this->info("  Total:    {$count}");
        $this->info("  ✅ Éxito:  {$success}");
        if ($failed > 0) {
            $this->error("  ❌ Fallos:  {$failed}");
        } else {
            $this->info("  ❌ Fallos:  0");
        }

        if ($success === $count) {
            $this->newLine();
            $this->info('🎉 ¡TODAS LAS APROBACIONES COMERCIALES ENVIADAS EXITOSAMENTE!');
        }

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Load test data from XLSX or JSON file.
     */
    private function loadTestData(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'json') {
            $raw = file_get_contents($filePath);
            return json_decode($raw, true) ?? [];
        }

        if ($ext === 'xlsx') {
            return $this->parseXlsx($filePath);
        }

        $this->error("Unsupported file format: .{$ext}. Use .xlsx or .json");
        return [];
    }

    /**
     * Parse XLSX using PhpSpreadsheet or fallback to Python bridge.
     */
    private function parseXlsx(string $filePath): array
    {
        // Try PhpSpreadsheet
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();
            
            if (empty($data)) return [];
            
            $headers = array_shift($data);
            $result = [];
            foreach ($data as $row) {
                if (empty(array_filter($row))) continue;
                $record = [];
                foreach ($headers as $i => $h) {
                    $record[trim($h)] = $row[$i] ?? null;
                }
                $result[] = $record;
            }
            return $result;
        }

        // Fallback: Python bridge
        $this->warn("PhpSpreadsheet not available, using Python bridge...");
        $pythonScript = <<<'PY'
import openpyxl, json, sys
wb = openpyxl.load_workbook(sys.argv[1])
ws = wb.active
data, headers = [], []
for i, row in enumerate(ws.iter_rows(values_only=True)):
    if i == 0:
        headers = [str(c).strip() if c else f'col_{j}' for j, c in enumerate(row)]
        continue
    if all(c is None for c in row): continue
    record = {}
    for j, val in enumerate(row):
        key = headers[j] if j < len(headers) else f'col_{j}'
        if hasattr(val, 'isoformat'): val = val.strftime('%d-%m-%Y %H:%M:%S') if hasattr(val, 'hour') else val.strftime('%d-%m-%Y')
        record[key] = val
    data.append(record)
print(json.dumps(data, ensure_ascii=False, default=str))
PY;

        $tmpPy = tempnam(sys_get_temp_dir(), 'dgii_xlsx_') . '.py';
        file_put_contents($tmpPy, $pythonScript);
        $escaped = escapeshellarg($filePath);
        $output = shell_exec("python \"{$tmpPy}\" {$escaped} 2>&1");
        @unlink($tmpPy);

        $parsed = json_decode($output, true);
        return is_array($parsed) ? $parsed : [];
    }

    /**
     * Format a date string to dd-mm-yyyy.
     */
    private function formatDate(string $dateStr): string
    {
        // Already in dd-mm-yyyy format
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateStr)) {
            return $dateStr;
        }
        // yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return date('d-m-Y', strtotime($dateStr));
        }
        return $dateStr;
    }

    /**
     * Format a datetime string to dd-mm-yyyy HH:mm:ss.
     */
    private function formatDateTime(string $dateStr): string
    {
        // Already correct
        if (preg_match('/^\d{2}-\d{2}-\d{4}\s\d{2}:\d{2}:\d{2}$/', $dateStr)) {
            return $dateStr;
        }
        // yyyy-mm-dd HH:mm:ss
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateStr)) {
            return date('d-m-Y H:i:s', strtotime($dateStr));
        }
        return $dateStr;
    }
}
