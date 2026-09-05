<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\ReceivedInvoice;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Limpieza segura de datos demo transaccionales
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Payment::truncate();
        InvoiceItem::truncate();
        Invoice::truncate();
        Expense::truncate();
        ReceivedInvoice::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Obtener clientes base o crearlos si no existen
        $bhd = Client::firstOrCreate(
            ['tax_id' => '1-01-00789-1'],
            [
                'company_name' => 'Banco Múltiple BHD, S.A.',
                'contact_name' => 'Luis Molina Achécar',
                'email' => 'corporativo@bhd.com.do',
                'phone' => '809-243-3232',
                'address' => 'Av. 27 de Febrero esq. Winston Churchill',
                'city' => 'Santo Domingo',
                'country' => 'DO',
                'currency' => 'DOP',
                'is_active' => true,
            ]
        );

        $alejandro = Client::firstOrCreate(
            ['tax_id' => '001-1234567-8'],
            [
                'company_name' => 'Ing. Alejandro Ramírez Cedeño',
                'contact_name' => 'Alejandro Ramírez',
                'email' => 'aramirez@gmail.com',
                'phone' => '809-555-0199',
                'address' => 'Calle Las Damas #12, Zona Colonial',
                'city' => 'Santo Domingo',
                'country' => 'DO',
                'currency' => 'DOP',
                'is_active' => true,
            ]
        );

        $cnd = Client::firstOrCreate(
            ['tax_id' => '1-01-00123-4'],
            [
                'company_name' => 'Cervecería Nacional Dominicana',
                'contact_name' => 'Fabián Suárez',
                'email' => 'compras@cnd.com.do',
                'phone' => '809-487-3000',
                'address' => 'Autopista 30 de Mayo Km 6 1/2',
                'city' => 'Santo Domingo',
                'country' => 'DO',
                'currency' => 'DOP',
                'is_active' => true,
            ]
        );

        $embajador = Client::firstOrCreate(
            ['tax_id' => '1-01-54321-9'],
            [
                'company_name' => 'Hotel El Embajador Royal Hideaway',
                'contact_name' => 'El Hassan Zouaoui',
                'email' => 'finanzas@elembajador.com',
                'phone' => '809-221-2131',
                'address' => 'Av. Sarasota #65, Bella Vista',
                'city' => 'Santo Domingo',
                'country' => 'DO',
                'currency' => 'USD',
                'is_active' => true,
            ]
        );

        $ramos = Client::firstOrCreate(
            ['tax_id' => '1-01-00567-8'],
            [
                'company_name' => 'Grupo Ramos, S.A.',
                'contact_name' => 'Mercedes Ramos',
                'email' => 'cuentasxpagar@gruporamos.com',
                'phone' => '809-472-4444',
                'address' => 'Av. Winston Churchill esq. Ángel Severo Cabral',
                'city' => 'Santo Domingo',
                'country' => 'DO',
                'currency' => 'DOP',
                'is_active' => true,
            ]
        );

        $carol = Client::firstOrCreate(
            ['tax_id' => '1-30-87654-3'],
            [
                'company_name' => 'Farmacias Carol, S.A.S.',
                'contact_name' => 'Julio César Curiel',
                'email' => 'administracion@farmaciascarol.com',
                'phone' => '809-563-2222',
                'address' => 'Av. Abraham Lincoln #804',
                'city' => 'Santo Domingo',
                'country' => 'DO',
                'currency' => 'DOP',
                'is_active' => true,
            ]
        );

        // ==========================================
        // 2. FACTURAS AGOSTO 2026 (PERÍODO 2026-08)
        // ==========================================

        // Factura 1: BHD - Crédito Fiscal E31 Pagada vía Transferencia
        $inv1 = Invoice::create([
            'invoice_number' => 'GBS-1001',
            'client_id' => $bhd->id,
            'status' => 'paid',
            'issue_date' => '2026-08-05',
            'due_date' => '2026-09-05',
            'subtotal' => 100000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 18000.00,
            'total' => 118000.00,
            'amount_paid' => 118000.00,
            'currency' => 'DOP',
            'exchange_rate' => 1.0,
            'is_ecf' => 1,
            'ecf_type' => 31,
            'encf' => 'E310000000101',
            'tipo_ingresos' => '01',
            'dgii_status' => 'accepted',
            'paid_at' => '2026-08-05 14:30:00',
            'notes' => 'Servicios de Consultoría de Software y Cloud',
            'created_by' => 1,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'description' => 'Servicios de Consultoría de Software y Cloud Enterprise',
            'quantity' => 1,
            'unit_price' => 100000.00,
            'amount' => 100000.00,
            'sort_order' => 1,
        ]);
        Payment::create([
            'invoice_id' => $inv1->id,
            'amount' => 118000.00,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-08-05',
            'reference' => 'TRANSF-BHD-884910',
            'notes' => 'Pago total mediante transferencia bancaria',
        ]);

        // Factura 2: Alejandro - Consumo E32 Pagada en Efectivo
        $inv2 = Invoice::create([
            'invoice_number' => 'GBS-1002',
            'client_id' => $alejandro->id,
            'status' => 'paid',
            'issue_date' => '2026-08-10',
            'due_date' => '2026-08-10',
            'subtotal' => 10000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 1800.00,
            'total' => 11800.00,
            'amount_paid' => 11800.00,
            'currency' => 'DOP',
            'exchange_rate' => 1.0,
            'is_ecf' => 1,
            'ecf_type' => 32,
            'encf' => 'E320000000102',
            'tipo_ingresos' => '01',
            'dgii_status' => 'accepted',
            'paid_at' => '2026-08-10 11:15:00',
            'notes' => 'Mantenimiento Técnico de Redes',
            'created_by' => 1,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv2->id,
            'description' => 'Mantenimiento Técnico de Redes e Infraestructura',
            'quantity' => 1,
            'unit_price' => 10000.00,
            'amount' => 10000.00,
            'sort_order' => 1,
        ]);
        Payment::create([
            'invoice_id' => $inv2->id,
            'amount' => 11800.00,
            'payment_method' => 'cash',
            'payment_date' => '2026-08-10',
            'reference' => 'REC-EFECT-0021',
            'notes' => 'Pago recibido en efectivo en caja',
        ]);

        // Factura 3: BHD - Nota de Crédito E34 sobre Factura 1 (GBS-1001)
        $inv3 = Invoice::create([
            'invoice_number' => 'GBS-1003',
            'client_id' => $bhd->id,
            'status' => 'paid',
            'issue_date' => '2026-08-15',
            'due_date' => '2026-08-15',
            'subtotal' => 20000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 3600.00,
            'total' => 23600.00,
            'amount_paid' => 23600.00,
            'currency' => 'DOP',
            'exchange_rate' => 1.0,
            'is_ecf' => 1,
            'ecf_type' => 34,
            'encf' => 'E340000000103',
            'tipo_ingresos' => '01',
            'modified_ncf' => 'E310000000101',
            'modification_code' => '01',
            'modification_reason' => 'Ajuste de honorarios por horas de soporte no consumidas',
            'nota_credito_indicator' => 1,
            'dgii_status' => 'accepted',
            'paid_at' => '2026-08-15 16:00:00',
            'notes' => 'Nota de Crédito aplicada a Factura E310000000101',
            'created_by' => 1,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv3->id,
            'description' => 'Ajuste de consultoría no ejecutada',
            'quantity' => 1,
            'unit_price' => 20000.00,
            'amount' => 20000.00,
            'sort_order' => 1,
        ]);
        Payment::create([
            'invoice_id' => $inv3->id,
            'amount' => 23600.00,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-08-15',
            'reference' => 'NC-REF-0103',
            'notes' => 'Liquidación nota de crédito',
        ]);

        // Factura 4: Cervecería Nacional - Factura Vencida con Saldo Pendiente
        // Fecha de vencimiento: 2026-08-25 (Hoy es 2026-09-05 -> ¡Vencida!)
        $inv4 = Invoice::create([
            'invoice_number' => 'GBS-1004',
            'client_id' => $cnd->id,
            'status' => 'partial',
            'issue_date' => '2026-08-10',
            'due_date' => '2026-08-25',
            'subtotal' => 50000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 9000.00,
            'total' => 59000.00,
            'amount_paid' => 20000.00,
            'currency' => 'DOP',
            'exchange_rate' => 1.0,
            'is_ecf' => 1,
            'ecf_type' => 31,
            'encf' => 'E310000000104',
            'tipo_ingresos' => '02',
            'dgii_status' => 'accepted',
            'notes' => 'Implementación Módulo de Seguridad Perimetral',
            'created_by' => 1,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv4->id,
            'description' => 'Implementación Módulo de Seguridad Perimetral y Firewalls',
            'quantity' => 1,
            'unit_price' => 50000.00,
            'amount' => 50000.00,
            'sort_order' => 1,
        ]);
        Payment::create([
            'invoice_id' => $inv4->id,
            'amount' => 20000.00,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-08-12',
            'reference' => 'TRANSF-CND-PARTIAL',
            'notes' => 'Anticipo inicial 20,000 DOP',
        ]);

        // Factura 5: Hotel El Embajador - Factura en Moneda Extranjera USD (Tasa 60.00)
        $inv5 = Invoice::create([
            'invoice_number' => 'GBS-1005',
            'client_id' => $embajador->id,
            'status' => 'paid',
            'issue_date' => '2026-08-20',
            'due_date' => '2026-09-20',
            'subtotal' => 1000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 180.00,
            'total' => 1180.00,
            'amount_paid' => 1180.00,
            'currency' => 'USD',
            'exchange_rate' => 60.0000,
            'is_ecf' => 1,
            'ecf_type' => 31,
            'encf' => 'E310000000105',
            'tipo_ingresos' => '01',
            'dgii_status' => 'accepted',
            'paid_at' => '2026-08-20 17:00:00',
            'notes' => 'Licencia Anual Plataforma Gridbase Cloud Enterprise (USD)',
            'created_by' => 1,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv5->id,
            'description' => 'Licencia Anual Plataforma Gridbase Cloud Enterprise',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'amount' => 1000.00,
            'sort_order' => 1,
        ]);
        Payment::create([
            'invoice_id' => $inv5->id,
            'amount' => 1180.00,
            'payment_method' => 'credit_card',
            'payment_date' => '2026-08-20',
            'reference' => 'VISA-USD-990182',
            'notes' => 'Cobro procesado en USD con tarjeta corporativa',
        ]);

        // Factura 6: Grupo Ramos - Factura Cancelada con NCF Oficial (Formato 608)
        Invoice::create([
            'invoice_number' => 'GBS-1006',
            'client_id' => $ramos->id,
            'status' => 'cancelled',
            'anulation_type' => '04',
            'cancellation_reason' => 'Error en monto convenido en orden de compra',
            'cancelled_at' => '2026-08-22 18:00:00',
            'issue_date' => '2026-08-22',
            'due_date' => '2026-09-22',
            'subtotal' => 30000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 5400.00,
            'total' => 35400.00,
            'amount_paid' => 0.00,
            'currency' => 'DOP',
            'exchange_rate' => 1.0,
            'is_ecf' => 1,
            'ecf_type' => 31,
            'encf' => 'E310000000106',
            'tipo_ingresos' => '01',
            'dgii_status' => 'cancelled',
            'notes' => 'Comprobante anulado formalmente',
            'created_by' => 1,
        ]);

        // Factura 7: Farmacias Carol - Borrador (No debe entrar a 607, 608 ni ITBIS)
        Invoice::create([
            'invoice_number' => 'GBS-1007',
            'client_id' => $carol->id,
            'status' => 'draft',
            'issue_date' => '2026-08-25',
            'due_date' => '2026-09-25',
            'subtotal' => 15000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 2700.00,
            'total' => 17700.00,
            'amount_paid' => 0.00,
            'currency' => 'DOP',
            'exchange_rate' => 1.0,
            'is_ecf' => 0,
            'ecf_type' => null,
            'encf' => null,
            'notes' => 'Cotización en preparación, pendiente de aprobación',
            'created_by' => 1,
        ]);

        // ==========================================
        // 3. COMPRAS Y GASTOS AGOSTO 2026 (606)
        // ==========================================

        // Compra 1: e-CF Recibido Claro Dominicana
        $xmlClaro = '<?xml version="1.0" encoding="utf-8"?><ECF><Encabezado><IdDoc><TipoeCF>31</TipoeCF><eNCF>E31000049281</eNCF></IdDoc></Encabezado><Totales><MontoGravadoTotal>10000.00</MontoGravadoTotal><TotalITBIS>1800.00</TotalITBIS><MontoTotal>11800.00</MontoTotal></Totales></ECF>';
        ReceivedInvoice::create([
            'rnc_emisor' => '101007891',
            'razon_social_emisor' => 'Claro Dominicana Telecomunicaciones, S.A.',
            'encf' => 'E31000049281',
            'ecf_type' => '31',
            'fecha_emision' => '2026-08-04',
            'monto_total' => 11800.00,
            'raw_xml' => $xmlClaro,
            'approval_status' => 'approved',
            'approved_at' => '2026-08-04 15:00:00',
        ]);

        // Compra 2: e-CF Recibido Edesur Dominicana
        $xmlEdesur = '<?xml version="1.0" encoding="utf-8"?><ECF><Encabezado><IdDoc><TipoeCF>31</TipoeCF><eNCF>E31000088921</eNCF></IdDoc></Encabezado><Totales><MontoGravadoTotal>20000.00</MontoGravadoTotal><TotalITBIS>3600.00</TotalITBIS><MontoTotal>23600.00</MontoTotal></Totales></ECF>';
        ReceivedInvoice::create([
            'rnc_emisor' => '101023456',
            'razon_social_emisor' => 'Edesur Dominicana, S.A.',
            'encf' => 'E31000088921',
            'ecf_type' => '31',
            'fecha_emision' => '2026-08-12',
            'monto_total' => 23600.00,
            'raw_xml' => $xmlEdesur,
            'approval_status' => 'approved',
            'approved_at' => '2026-08-12 16:30:00',
        ]);

        // Gasto Manual 1: Papelería CCC (Comprobante B01 tradicional)
        Expense::create([
            'provider_name' => 'Papelería & Suministros CCC, SRL',
            'provider_tax_id' => '1-30-87654-3',
            'ncf' => 'B01000012093',
            'expense_date' => '2026-08-18',
            'subtotal' => 5000.00,
            'tax_amount' => 900.00,
            'total' => 5900.00,
            'expense_type' => '02', // Gastos de operaciones
            'payment_method' => 'transfer',
            'notes' => 'Suministros de oficina y cartuchos de tóner',
            'created_by' => 1,
        ]);

        // Gasto Manual 2: ¡DUPLICADO A PROPÓSITO CON MISMO NCF DE CLARO PARA VALIDAR DEDUPLICACIÓN EN 606!
        Expense::create([
            'provider_name' => 'Claro Dominicana Telecomunicaciones, S.A.',
            'provider_tax_id' => '1-01-00789-1',
            'ncf' => 'E31000049281', // Mismo NCF de Compra 1
            'expense_date' => '2026-08-04',
            'subtotal' => 10000.00,
            'tax_amount' => 1800.00,
            'total' => 11800.00,
            'expense_type' => '01',
            'payment_method' => 'transfer',
            'notes' => 'Registro manual duplicado de Claro (debe ser filtrado en 606)',
            'created_by' => 1,
        ]);

        // ==========================================
        // 4. FACTURAS SEPTIEMBRE 2026 (MES ACTUAL)
        // ==========================================

        // Factura Sep 1: BHD - Cobrada en Septiembre
        $invSep1 = Invoice::create([
            'invoice_number' => 'GBS-2001',
            'client_id' => $bhd->id,
            'status' => 'paid',
            'issue_date' => '2026-09-02',
            'due_date' => '2026-10-02',
            'subtotal' => 80000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 14400.00,
            'total' => 94400.00,
            'amount_paid' => 94400.00,
            'currency' => 'DOP',
            'exchange_rate' => 1.0,
            'is_ecf' => 1,
            'ecf_type' => 31,
            'encf' => 'E310000000201',
            'tipo_ingresos' => '01',
            'dgii_status' => 'accepted',
            'paid_at' => '2026-09-02 10:00:00',
            'notes' => 'Soporte Cloud Mensual Enterprise Septiembre 2026',
            'created_by' => 1,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invSep1->id,
            'description' => 'Servicio de Soporte y Mantenimiento Enterprise - Septiembre',
            'quantity' => 1,
            'unit_price' => 80000.00,
            'amount' => 80000.00,
            'sort_order' => 1,
        ]);
        Payment::create([
            'invoice_id' => $invSep1->id,
            'amount' => 94400.00,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-09-02',
            'reference' => 'TRANSF-BHD-SEP-01',
            'notes' => 'Pago total mediante transferencia BHD',
        ]);

        // Factura Sep 2: BHD - Nota de Crédito en Septiembre
        $invSep2 = Invoice::create([
            'invoice_number' => 'GBS-2002',
            'client_id' => $bhd->id,
            'status' => 'paid',
            'issue_date' => '2026-09-03',
            'due_date' => '2026-09-03',
            'subtotal' => 10000.00,
            'tax_rate' => 18.00,
            'tax_amount' => 1800.00,
            'total' => 11800.00,
            'amount_paid' => 11800.00,
            'currency' => 'DOP',
            'exchange_rate' => 1.0,
            'is_ecf' => 1,
            'ecf_type' => 34,
            'encf' => 'E340000000202',
            'tipo_ingresos' => '01',
            'modified_ncf' => 'E310000000201',
            'modification_code' => '01',
            'modification_reason' => 'Descuento especial por pronto pago acordado',
            'nota_credito_indicator' => 1,
            'dgii_status' => 'accepted',
            'paid_at' => '2026-09-03 12:00:00',
            'notes' => 'Nota de Crédito por pronto pago aplicada a E310000000201',
            'created_by' => 1,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invSep2->id,
            'description' => 'Descuento comercial por pronto pago',
            'quantity' => 1,
            'unit_price' => 10000.00,
            'amount' => 10000.00,
            'sort_order' => 1,
        ]);
        Payment::create([
            'invoice_id' => $invSep2->id,
            'amount' => 11800.00,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-09-03',
            'reference' => 'NC-SEP-REF-02',
            'notes' => 'Aplicación de crédito comercial',
        ]);
    }
}
