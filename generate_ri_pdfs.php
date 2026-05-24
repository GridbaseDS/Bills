<?php
/**
 * Generate 11 PDFs (one per e-CF type) for DGII Paso 5 - Representaciones Impresas
 * Usage: php generate_ri_pdfs.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Invoice;
use App\Models\Setting;

$settings = Setting::pluck('setting_value', 'setting_key')->toArray();
$outputDir = storage_path('app/representaciones_impresas');
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Types needed: 31, 32>=250k, 33, 34, 41, 43, 44, 45, 46, 47, 32<250k
$typeConfigs = [
    ['label' => 'tipo_31', 'type' => 31, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_32_mayor250k', 'type' => 32, 'min_total' => 250000, 'max_total' => null],
    ['label' => 'tipo_33', 'type' => 33, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_34', 'type' => 34, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_41', 'type' => 41, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_43', 'type' => 43, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_44', 'type' => 44, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_45', 'type' => 45, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_46', 'type' => 46, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_47', 'type' => 47, 'min_total' => null, 'max_total' => null],
    ['label' => 'tipo_32_menor250k', 'type' => 32, 'min_total' => null, 'max_total' => 249999.99],
];

$totalSize = 0;
$generated = [];

foreach ($typeConfigs as $config) {
    $query = Invoice::where('ecf_type', $config['type'])
        ->where('is_ecf', 1)
        ->whereNotNull('encf')
        ->whereNotNull('security_code')
        ->with(['client', 'items']);

    if ($config['min_total'] !== null) {
        $query->where('total', '>=', $config['min_total']);
    }
    if ($config['max_total'] !== null) {
        $query->where('total', '<', $config['max_total']);
    }

    $invoice = $query->latest()->first();

    if (!$invoice) {
        echo "  NO ENCONTRADA: {$config['label']}\n";
        continue;
    }

    // Set signed_at if missing (for older invoices)
    if (!$invoice->signed_at) {
        $invoice->update(['signed_at' => $invoice->created_at]);
    }

    echo "  {$config['label']}: {$invoice->encf} (Total: " . number_format($invoice->total, 2) . ")\n";

    // Generate PDF using existing controller logic
    $invoiceData = $invoice->toArray();
    $clientData = $invoice->client ? $invoice->client->toArray() : [];
    $itemsData = $invoice->items->toArray();
    $companyData = [
        'name' => $settings['company_name'] ?? '',
        'tax_id' => $settings['company_tax_id'] ?? '',
        'address' => $settings['company_address'] ?? '',
        'phone' => $settings['company_phone'] ?? '',
        'email' => $settings['company_email'] ?? '',
        'logo' => $settings['company_logo'] ?? '',
    ];

    $html = view('pdf.invoice', [
        'invoice' => $invoiceData,
        'company' => $companyData,
        'client' => $clientData,
        'items' => $itemsData,
        'settings' => $settings,
    ])->render();

    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled' => true,
        'isHtml5ParserEnabled' => true,
        'defaultFont' => 'DejaVu Sans',
    ]);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    $pdfContent = $dompdf->output();
    $filePath = "{$outputDir}/RI_{$config['label']}_{$invoice->encf}.pdf";
    file_put_contents($filePath, $pdfContent);

    $size = filesize($filePath);
    $totalSize += $size;

    $generated[] = [
        'label' => $config['label'],
        'encf' => $invoice->encf,
        'file' => basename($filePath),
        'size_kb' => round($size / 1024, 1),
    ];

    echo "    -> " . basename($filePath) . " (" . round($size / 1024, 1) . " KB)\n";
}

echo "\n=== RESUMEN ===\n";
echo "Total archivos: " . count($generated) . "\n";
echo "Tamano total: " . round($totalSize / 1024, 1) . " KB (" . round($totalSize / 1048576, 2) . " MB)\n";
echo "Limite: 10 MB\n";
echo "Directorio: {$outputDir}\n\n";

foreach ($generated as $g) {
    echo "  {$g['label']}: {$g['encf']} ({$g['size_kb']} KB)\n";
}

if ($totalSize > 10 * 1048576) {
    echo "\n  ADVERTENCIA: El total excede 10MB!\n";
} else {
    echo "\n  OK: Dentro del limite de 10MB\n";
}
