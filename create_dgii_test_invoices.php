<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use Carbon\Carbon;

// Find or create a client
$client = Client::firstOrCreate(
    ['email' => 'dgii_test@example.com'],
    [
        'name' => 'Cliente DGII Pruebas',
        'contact_name' => 'Contacto DGII',
        'rnc' => '101010101',
        'address' => 'Av. Mexico #1, Santo Domingo',
        'phone' => '809-555-5555'
    ]
);

$types = [
    ['type' => '31', 'amount' => 1000, 'label' => 'Crédito Fiscal'],
    ['type' => '32', 'amount' => 300000, 'label' => 'Consumo >= 250mil'],
    ['type' => '32', 'amount' => 5000, 'label' => 'Consumo < 250mil'],
    ['type' => '33', 'amount' => 1000, 'label' => 'Nota de Débito'],
    ['type' => '34', 'amount' => 1000, 'label' => 'Nota de Crédito'],
    ['type' => '41', 'amount' => 1000, 'label' => 'Compras'],
    ['type' => '43', 'amount' => 1000, 'label' => 'Gastos Menores'],
    ['type' => '44', 'amount' => 1000, 'label' => 'Regímenes Especiales'],
    ['type' => '45', 'amount' => 1000, 'label' => 'Gubernamental'],
    ['type' => '46', 'amount' => 1000, 'label' => 'Exportaciones'],
    ['type' => '47', 'amount' => 1000, 'label' => 'Pagos al Exterior'],
];

foreach ($types as $index => $t) {
    $ecf = $t['type'];
    $encf = 'E' . $ecf . '00000000000' . rand(10, 99);
    
    $invoice = new Invoice();
    $invoice->client_id = $client->id;
    $invoice->invoice_number = 'DGII-' . $ecf . '-' . rand(1000, 9999);
    $invoice->issue_date = Carbon::now()->format('Y-m-d');
    $invoice->due_date = Carbon::now()->addDays(30)->format('Y-m-d');
    $invoice->subtotal = $t['amount'];
    $invoice->tax_rate = 18;
    $invoice->tax_amount = $t['amount'] * 0.18;
    $invoice->total = $t['amount'] * 1.18;
    $invoice->status = 'paid';
    
    // ECF fields
    $invoice->is_ecf = true;
    $invoice->ecf_type = $ecf;
    $invoice->encf = $encf;
    $invoice->dgii_status = 'Aceptado';
    $invoice->signed_at = Carbon::now();
    $invoice->security_code = 'S/DQdu';
    
    $invoice->notes = 'Factura generada automáticamente para pruebas de certificación DGII.';
    $invoice->save();
    
    // Create items
    $item = new InvoiceItem();
    $item->invoice_id = $invoice->id;
    $item->description = 'Servicio de ' . $t['label'];
    $item->quantity = 1;
    $item->unit_price = $t['amount'];
    $item->amount = $t['amount'];
    $item->save();
    
    echo "Created Invoice {$invoice->invoice_number} - ECF: $ecf - ENCF: $encf - Total: {$invoice->total}\n";
}
