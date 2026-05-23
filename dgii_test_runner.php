<?php
/**
 * DGII Paso 4 - Automated Test Submission Script
 * Creates real invoices and processes them through the full EcfManagerService flow.
 * Usage: php dgii_test_runner.php [test_number]
 *   - No args: runs all tests sequentially  
 *   - With number: runs only that specific test
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Setting;
use App\Services\Dgii\EcfManagerService;
use Illuminate\Support\Facades\Log;

// Get the test RNC client (must have RNC matching our company for self-testing)
$companyRnc = Setting::where('setting_key', 'company_tax_id')->value('setting_value');
$client = Client::first();
if (!$client) {
    echo "ERROR: No clients found. Create at least one client first.\n";
    exit(1);
}
echo "Using client: {$client->company_name} (RNC: {$client->tax_id})\n";
echo "Company RNC: $companyRnc\n\n";

// Track accepted NCFs for use in type 33/34 references
$acceptedNcfs = [];

// Define all 25 tests
$tests = [
    // Type 31 - Crédito Fiscal (4)
    ['type' => 31, 'amount' => 50000, 'tax' => 18, 'payment' => 1, 'desc' => 'Credito Fiscal - Contado 50k'],
    ['type' => 31, 'amount' => 150000, 'tax' => 18, 'payment' => 2, 'desc' => 'Credito Fiscal - Credito 150k'],
    ['type' => 31, 'amount' => 80000, 'tax' => 18, 'payment' => 1, 'desc' => 'Credito Fiscal - Contado 80k'],
    ['type' => 31, 'amount' => 200000, 'tax' => 18, 'payment' => 2, 'desc' => 'Credito Fiscal - Credito 200k'],

    // Type 32 >= 250k (2)
    ['type' => 32, 'amount' => 300000, 'tax' => 18, 'payment' => 1, 'desc' => 'Consumo >= 250k - Contado 300k'],
    ['type' => 32, 'amount' => 500000, 'tax' => 18, 'payment' => 1, 'desc' => 'Consumo >= 250k - Contado 500k'],

    // Type 33 - Nota de Débito (1) - references type 31 #1
    ['type' => 33, 'amount' => 10000, 'tax' => 18, 'payment' => 1, 'desc' => 'Nota Debito - Ref tipo 31', 'ref_test' => 0, 'mod_code' => 3],

    // Type 34 - Nota de Crédito (2) - references type 31 #2 and #3
    ['type' => 34, 'amount' => 5000, 'tax' => 18, 'payment' => 1, 'desc' => 'Nota Credito 1 - Ref tipo 31', 'ref_test' => 1, 'mod_code' => 1],
    ['type' => 34, 'amount' => 8000, 'tax' => 18, 'payment' => 1, 'desc' => 'Nota Credito 2 - Ref tipo 31', 'ref_test' => 2, 'mod_code' => 2],

    // Type 41 - Compras (2)
    ['type' => 41, 'amount' => 25000, 'tax' => 18, 'payment' => 1, 'desc' => 'Compras - Contado 25k'],
    ['type' => 41, 'amount' => 40000, 'tax' => 18, 'payment' => 1, 'desc' => 'Compras - Contado 40k'],

    // Type 43 - Gastos Menores (2)
    ['type' => 43, 'amount' => 5000, 'tax' => 18, 'payment' => 1, 'desc' => 'Gastos Menores - 5k'],
    ['type' => 43, 'amount' => 3000, 'tax' => 18, 'payment' => 1, 'desc' => 'Gastos Menores - 3k'],

    // Type 44 - Regímenes Especiales (2)
    ['type' => 44, 'amount' => 60000, 'tax' => 18, 'payment' => 1, 'desc' => 'Reg. Especiales - Contado 60k'],
    ['type' => 44, 'amount' => 45000, 'tax' => 18, 'payment' => 2, 'desc' => 'Reg. Especiales - Credito 45k'],

    // Type 45 - Gubernamental (2)
    ['type' => 45, 'amount' => 100000, 'tax' => 18, 'payment' => 1, 'desc' => 'Gubernamental - Contado 100k'],
    ['type' => 45, 'amount' => 75000, 'tax' => 18, 'payment' => 2, 'desc' => 'Gubernamental - Credito 75k'],

    // Type 46 - Exportaciones (2) - Exento ITBIS
    ['type' => 46, 'amount' => 200000, 'tax' => 0, 'payment' => 1, 'desc' => 'Exportaciones - 200k Exento'],
    ['type' => 46, 'amount' => 150000, 'tax' => 0, 'payment' => 1, 'desc' => 'Exportaciones - 150k Exento'],

    // Type 47 - Pagos al Exterior (2) - Exento ITBIS
    ['type' => 47, 'amount' => 50000, 'tax' => 0, 'payment' => 1, 'desc' => 'Pagos Exterior - 50k'],
    ['type' => 47, 'amount' => 30000, 'tax' => 0, 'payment' => 1, 'desc' => 'Pagos Exterior - 30k'],

    // Type 32 < 250k RFCE (4)
    ['type' => 32, 'amount' => 15000, 'tax' => 18, 'payment' => 1, 'desc' => 'Consumo RFCE < 250k - 15k', 'rfce' => true],
    ['type' => 32, 'amount' => 80000, 'tax' => 18, 'payment' => 1, 'desc' => 'Consumo RFCE < 250k - 80k', 'rfce' => true],
    ['type' => 32, 'amount' => 120000, 'tax' => 18, 'payment' => 1, 'desc' => 'Consumo RFCE < 250k - 120k', 'rfce' => true],
    ['type' => 32, 'amount' => 50000, 'tax' => 18, 'payment' => 1, 'desc' => 'Consumo RFCE < 250k - 50k', 'rfce' => true],
];

$specificTest = isset($argv[1]) ? (int)$argv[1] : null;
$ecfManager = app(EcfManagerService::class);

$results = [];

foreach ($tests as $idx => $test) {
    $testNum = $idx + 1;
    
    if ($specificTest !== null && $testNum !== $specificTest) {
        continue;
    }
    
    echo "=== Test $testNum/25: [{$test['type']}] {$test['desc']} ===\n";
    
    try {
        // Create invoice
        $subtotal = $test['amount'];
        $taxRate = $test['tax'];
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;
        
        $prefix = Setting::where('setting_key', 'invoice_prefix')->value('setting_value') ?? 'FAC-';
        $nextNum = (int)(Setting::where('setting_key', 'invoice_next_number')->value('setting_value') ?? 1);
        $invoiceNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        Setting::where('setting_key', 'invoice_next_number')->update(['setting_value' => $nextNum + 1]);
        
        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'client_id' => $client->id,
            'status' => 'sent',
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => $test['payment'] == 2 ? now()->addDays(30)->format('Y-m-d') : now()->format('Y-m-d'),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'discount_amount' => 0,
            'total' => $total,
            'amount_paid' => 0,
            'currency' => 'DOP',
            'notes' => "Test DGII Paso 4 - {$test['desc']}",
            'is_ecf' => 1,
            'ecf_type' => $test['type'],
            'tipo_ingresos' => '01',
        ];
        
        // For types 33/34, add modification reference
        if (in_array($test['type'], [33, 34]) && isset($test['ref_test'])) {
            $refIdx = $test['ref_test'];
            if (isset($acceptedNcfs[$refIdx])) {
                $invoiceData['modified_ncf'] = $acceptedNcfs[$refIdx];
            } else {
                $invoiceData['modified_ncf'] = 'E310000000700'; // fallback
            }
            $invoiceData['modification_code'] = $test['mod_code'] ?? 1;
        }
        
        $invoice = Invoice::create($invoiceData);
        
        // Create item
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Servicio de prueba - {$test['desc']}",
            'quantity' => 1,
            'unit_price' => $subtotal,
            'amount' => $subtotal,
            'sort_order' => 0,
        ]);
        
        $invoice->load('items', 'client');
        
        echo "  Invoice #{$invoiceNumber} created (ID: {$invoice->id})\n";
        
        // Process through EcfManager
        $ecfManager->processInvoice($invoice);
        $invoice->refresh();
        
        echo "  eNCF: {$invoice->encf}\n";
        echo "  DGII Status: {$invoice->dgii_status}\n";
        echo "  Track ID: {$invoice->dgii_track_id}\n";
        
        if ($invoice->dgii_error_messages) {
            echo "  ERRORS: {$invoice->dgii_error_messages}\n";
        }
        
        if ($invoice->dgii_status === 'accepted' || $invoice->dgii_track_id) {
            echo "  >> SENT SUCCESSFULLY\n";
            $acceptedNcfs[$idx] = $invoice->encf;
        } else {
            echo "  >> FAILED\n";
        }
        
        $results[$testNum] = [
            'type' => $test['type'],
            'desc' => $test['desc'],
            'encf' => $invoice->encf,
            'status' => $invoice->dgii_status,
            'track_id' => $invoice->dgii_track_id,
            'error' => $invoice->dgii_error_messages,
        ];
        
    } catch (\Exception $e) {
        echo "  EXCEPTION: {$e->getMessage()}\n";
        $results[$testNum] = [
            'type' => $test['type'],
            'desc' => $test['desc'],
            'encf' => null,
            'status' => 'exception',
            'track_id' => null,
            'error' => $e->getMessage(),
        ];
    }
    
    echo "\n";
    
    // Small delay between submissions
    if ($specificTest === null) {
        sleep(1);
    }
}

// Summary
echo "\n=== SUMMARY ===\n";
echo str_pad('Test', 5) . str_pad('Type', 6) . str_pad('eNCF', 16) . str_pad('Status', 12) . "Description\n";
echo str_repeat('-', 80) . "\n";
foreach ($results as $num => $r) {
    $statusIcon = ($r['status'] === 'accepted' || $r['track_id']) ? '[OK]' : '[!!]';
    echo str_pad("#$num", 5) . str_pad($r['type'], 6) . str_pad($r['encf'] ?? 'N/A', 16) . str_pad($statusIcon, 12) . $r['desc'] . "\n";
    if ($r['error']) {
        echo "     >> ERROR: {$r['error']}\n";
    }
}
