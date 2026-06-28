<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\ReceivedInvoice;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DgiiReportController extends Controller
{
    /**
     * Fetch records for the 607 Report (Ventas / Sales) for a given month.
     */
    public function report607(Request $request)
    {
        $year = $request->query('year', date('Y'));
        $month = str_pad($request->query('month', date('m')), 2, '0', STR_PAD_LEFT);
        
        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();
        
        // Fetch all invoices issued during this period
        // Exclude drafts as they are not legally issued
        $invoices = Invoice::with(['client', 'payments'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', '!=', 'draft')
            ->orderBy('issue_date', 'asc')
            ->get();
            
        $records = $invoices->map(function ($inv) {
            $taxId = $inv->client ? preg_replace('/[^0-9]/', '', $inv->client->tax_id) : '';
            
            // Determine Identification Type
            $typeId = '';
            if (!empty($taxId)) {
                $len = strlen($taxId);
                if ($len === 9) {
                    $typeId = '1'; // RNC
                } elseif ($len === 11) {
                    $typeId = '2'; // Cédula
                } else {
                    $typeId = '3'; // Pasaporte/Otro
                }
            } else {
                // If it is Consumo (Tipo 32) we can leave it empty
                if ($inv->ecf_type == 32) {
                    $typeId = '';
                } else {
                    $typeId = '3';
                }
            }
            
            // e-NCF / NCF
            $ncf = $inv->is_ecf ? ($inv->encf ?: $inv->invoice_number) : $inv->invoice_number;
            
            // Payment methods split
            $cash = 0;
            $bank = 0;
            $card = 0;
            $other = 0;
            
            foreach ($inv->payments as $pay) {
                switch ($pay->payment_method) {
                    case 'cash':
                        $cash += $pay->amount;
                        break;
                    case 'bank_transfer':
                    case 'paypal':
                        $bank += $pay->amount;
                        break;
                    case 'credit_card':
                        $card += $pay->amount;
                        break;
                    default:
                        $other += $pay->amount;
                        break;
                }
            }
            
            // Remaining balance is Credit
            $credit = max(0, $inv->total - $inv->amount_paid);
            
            return [
                'id' => $inv->id,
                'rnc_cliente' => $taxId,
                'tipo_identificacion' => $typeId,
                'ncf' => $ncf,
                'ncf_modificado' => $inv->modified_ncf ?? '',
                'tipo_ingreso' => $inv->tipo_ingresos ?? '01', // 01 = Ingresos por operaciones
                'fecha_comprobante' => Carbon::parse($inv->issue_date)->format('Ymd'),
                'fecha_pago' => $inv->paid_at ? Carbon::parse($inv->paid_at)->format('Ymd') : '',
                'monto_facturado' => round((float)$inv->subtotal, 2),
                'itbis_facturado' => round((float)$inv->tax_amount, 2),
                'itbis_retenido' => 0.00,
                'itbis_percibido' => 0.00,
                'retencion_isr' => 0.00,
                'isr_percibido' => 0.00,
                'isc' => 0.00,
                'otros_impuestos' => 0.00,
                'propina_legal' => 0.00,
                'efectivo' => round($cash, 2),
                'bancos' => round($bank, 2),
                'tarjeta' => round($card, 2),
                'credito' => round($credit, 2),
                'permuta' => 0.00,
                'otras_formas' => round($other, 2),
                'cliente_nombre' => $inv->client ? ($inv->client->company_name ?: $inv->client->contact_name) : 'Cliente General',
            ];
        });
        
        return response()->json([
            'success' => true,
            'period' => "{$year}{$month}",
            'data' => $records
        ]);
    }
    
    /**
     * Fetch records for the 606 Report (Compras / Expenses) for a given month.
     */
    public function report606(Request $request)
    {
        $year = $request->query('year', date('Y'));
        $month = str_pad($request->query('month', date('m')), 2, '0', STR_PAD_LEFT);
        
        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();
        
        // 1. Fetch received invoices (Facturas Recibidas)
        $receivedInvoices = ReceivedInvoice::whereBetween('fecha_emision', [$startDate, $endDate])
            ->orderBy('fecha_emision', 'asc')
            ->get();
            
        // 2. Fetch self-issued invoices of type 41 (Compras/Informal) or 43 (Gastos Menores)
        $selfIssued = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->whereIn('ecf_type', [41, 43])
            ->where('status', '!=', 'draft')
            ->get();
            
        $records = collect();
        
        // Process Received Invoices
        foreach ($receivedInvoices as $ri) {
            $taxId = preg_replace('/[^0-9]/', '', $ri->rnc_emisor);
            $len = strlen($taxId);
            $typeId = ($len === 9) ? '1' : (($len === 11) ? '2' : '3');
            
            // Try to parse XML if available to extract accurate ITBIS and Subtotal
            $subtotal = (float)$ri->monto_total;
            $itbis = 0.00;
            
            if (!empty($ri->raw_xml)) {
                try {
                    $xml = simplexml_load_string($ri->raw_xml);
                    if ($xml !== false) {
                        // Locate ITBIS inside Totales block
                        if (isset($xml->ECF->Totales->MontoITBIS)) {
                            $itbis = (float)$xml->ECF->Totales->MontoITBIS;
                        }
                        if (isset($xml->ECF->Totales->MontoSinImpuesto)) {
                            $subtotal = (float)$xml->ECF->Totales->MontoSinImpuesto;
                        } else {
                            $subtotal = (float)$ri->monto_total - $itbis;
                        }
                    }
                } catch (\Exception $e) {
                    // Fail silently, use fallback calculation
                }
            }
            
            // Fallback calculation if ITBIS is still 0 but type is standard Credit Fiscal (E31)
            if ($itbis === 0.00 && in_array($ri->ecf_type, ['E31', '31'])) {
                $subtotal = round((float)$ri->monto_total / 1.18, 2);
                $itbis = round((float)$ri->monto_total - $subtotal, 2);
            }
            
            $records->push([
                'id' => "ri_{$ri->id}",
                'rnc_proveedor' => $taxId,
                'tipo_identificacion' => $typeId,
                'tipo_bien_servicio' => '02', // Default: 02 - Gastos por Trabajos, Suministros y Servicios
                'ncf' => $ri->encf,
                'ncf_modificado' => '',
                'fecha_comprobante' => Carbon::parse($ri->fecha_emision)->format('Ymd'),
                'fecha_pago' => $ri->approved_at ? Carbon::parse($ri->approved_at)->format('Ymd') : Carbon::parse($ri->fecha_emision)->format('Ymd'),
                'monto_servicios' => round($subtotal, 2),
                'monto_bienes' => 0.00,
                'total_facturado' => round($subtotal, 2),
                'itbis_facturado' => round($itbis, 2),
                'itbis_retenido' => 0.00,
                'itbis_proporcional' => 0.00,
                'itbis_costo' => 0.00,
                'itbis_adelantar' => round($itbis, 2),
                'itbis_percibido' => 0.00,
                'tipo_retencion_isr' => '',
                'isr_retenido' => 0.00,
                'isr_percibido' => 0.00,
                'isc' => 0.00,
                'otros_impuestos' => 0.00,
                'propina_legal' => 0.00,
                'forma_pago' => '02', // Default: 02 - Cheques/Transferencias/Depositos
                'proveedor_nombre' => $ri->razon_social_emisor ?: 'Proveedor Recibido',
            ]);
        }
        
        // Process Self-issued Invoices (Informal Purchases or Minor Expenses)
        foreach ($selfIssued as $si) {
            // Self-issued means vendor is the client
            $taxId = $si->client ? preg_replace('/[^0-9]/', '', $si->client->tax_id) : '';
            $len = strlen($taxId);
            $typeId = ($len === 9) ? '1' : (($len === 11) ? '2' : '3');
            
            $ncf = $si->is_ecf ? ($si->encf ?: $si->invoice_number) : $si->invoice_number;
            
            $records->push([
                'id' => "si_{$si->id}",
                'rnc_proveedor' => $taxId,
                'tipo_identificacion' => $typeId,
                'tipo_bien_servicio' => $si->ecf_type == 43 ? '09' : '02', // 43 -> 09 Gastos de Representacion / 41 -> 02 Gastos por Trabajos...
                'ncf' => $ncf,
                'ncf_modificado' => $si->modified_ncf ?? '',
                'fecha_comprobante' => Carbon::parse($si->issue_date)->format('Ymd'),
                'fecha_pago' => $si->paid_at ? Carbon::parse($si->paid_at)->format('Ymd') : Carbon::parse($si->issue_date)->format('Ymd'),
                'monto_servicios' => round((float)$si->subtotal, 2),
                'monto_bienes' => 0.00,
                'total_facturado' => round((float)$si->subtotal, 2),
                'itbis_facturado' => round((float)$si->tax_amount, 2),
                'itbis_retenido' => 0.00,
                'itbis_proporcional' => 0.00,
                'itbis_costo' => 0.00,
                'itbis_adelantar' => round((float)$si->tax_amount, 2),
                'itbis_percibido' => 0.00,
                'tipo_retencion_isr' => '',
                'isr_retenido' => 0.00,
                'isr_percibido' => 0.00,
                'isc' => 0.00,
                'otros_impuestos' => 0.00,
                'propina_legal' => 0.00,
                'forma_pago' => '02',
                'proveedor_nombre' => $si->client ? ($si->client->company_name ?: $si->client->contact_name) : 'Proveedor Informal',
            ]);
        }

        // 3. Fetch manual expenses (Gastos / Control de egresos)
        $expenses = \App\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->orderBy('expense_date', 'asc')
            ->get();
            
        // Process Manual Expenses
        foreach ($expenses as $exp) {
            $taxId = preg_replace('/[^0-9]/', '', $exp->provider_tax_id ?? '');
            $len = strlen($taxId);
            $typeId = ($len === 9) ? '1' : (($len === 11) ? '2' : '3');
            
            $records->push([
                'id' => "exp_{$exp->id}",
                'rnc_proveedor' => $taxId,
                'tipo_identificacion' => $typeId,
                'tipo_bien_servicio' => $exp->expense_type ?? '02',
                'ncf' => $exp->ncf ?? '',
                'ncf_modificado' => '',
                'fecha_comprobante' => Carbon::parse($exp->expense_date)->format('Ymd'),
                'fecha_pago' => Carbon::parse($exp->expense_date)->format('Ymd'),
                'monto_servicios' => round((float)$exp->subtotal, 2),
                'monto_bienes' => 0.00,
                'total_facturado' => round((float)$exp->subtotal, 2),
                'itbis_facturado' => round((float)$exp->tax_amount, 2),
                'itbis_retenido' => 0.00,
                'itbis_proporcional' => 0.00,
                'itbis_costo' => 0.00,
                'itbis_adelantar' => round((float)$exp->tax_amount, 2),
                'itbis_percibido' => 0.00,
                'tipo_retencion_isr' => '',
                'isr_retenido' => 0.00,
                'isr_percibido' => 0.00,
                'isc' => 0.00,
                'otros_impuestos' => 0.00,
                'propina_legal' => 0.00,
                'forma_pago' => $exp->payment_method ?? '02',
                'proveedor_nombre' => $exp->provider_name ?: 'Proveedor de Gasto',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'period' => "{$year}{$month}",
            'data' => $records
        ]);
    }
    
    /**
     * Export the 607 Report records to a downloadable pipe-separated text file (.txt)
     */
    public function export607(Request $request)
    {
        $request->validate([
            'period' => 'required|string|size:6',
            'records' => 'required|array',
        ]);
        
        $period = $request->input('period');
        $records = $request->input('records');
        $companyTaxId = Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '131000000';
        $companyTaxId = preg_replace('/[^0-9]/', '', $companyTaxId);
        
        $totalAmount = 0;
        foreach ($records as $r) {
            $totalAmount += (float)($r['monto_facturado'] ?? 0);
        }
        
        // Header: 607|RNC|PERIODO|CANTIDAD_REGISTROS (4 campos exactos)
        $header = "607|{$companyTaxId}|{$period}|" . count($records);
        
        $lines = [$header];
        
        foreach ($records as $r) {
            $rnc = $r['rnc_cliente'] ?? '';
            $type = $r['tipo_identificacion'] ?? '';
            $ncf = $r['ncf'] ?? '';
            $ncfMod = $r['ncf_modificado'] ?? '';
            $incomeType = $r['tipo_income'] ?? $r['tipo_ingreso'] ?? '01';
            $dateComp = $r['fecha_comprobante'] ?? '';
            $datePay = $r['fecha_pago'] ?? '';
            
            $mFact = number_format((float)($r['monto_facturado'] ?? 0), 2, '.', '');
            $mItbis = number_format((float)($r['itbis_facturado'] ?? 0), 2, '.', '');
            $mItbisRet = number_format((float)($r['itbis_retenido'] ?? 0), 2, '.', '');
            $mItbisPer = number_format((float)($r['itbis_percibido'] ?? 0), 2, '.', '');
            $mIsrRet = number_format((float)($r['retencion_isr'] ?? 0), 2, '.', '');
            $mIsrPer = number_format((float)($r['isr_percibido'] ?? 0), 2, '.', '');
            $mIsc = number_format((float)($r['isc'] ?? 0), 2, '.', '');
            $mOtros = number_format((float)($r['otros_impuestos'] ?? 0), 2, '.', '');
            $mProp = number_format((float)($r['propina_legal'] ?? 0), 2, '.', '');
            
            // Formas de pago (7 campos)
            $mCash   = number_format((float)($r['efectivo'] ?? 0), 2, '.', '');      // 17. Efectivo
            $mBank   = number_format((float)($r['bancos'] ?? 0), 2, '.', '');        // 18. Cheque/Transferencia
            $mCard   = number_format((float)($r['tarjeta'] ?? 0), 2, '.', '');       // 19. Tarjeta Débito/Crédito
            $mCredit = number_format((float)($r['credito'] ?? 0), 2, '.', '');       // 20. Venta a Crédito
            $mBonos  = number_format((float)($r['bonos'] ?? 0), 2, '.', '');         // 21. Bonos o Certificados de Regalo
            $mPermuta = number_format((float)($r['permuta'] ?? 0), 2, '.', '');      // 22. Permuta
            $mOtras  = number_format((float)($r['otras_formas'] ?? 0), 2, '.', ''); // 23. Otras Formas de Venta
            
            // Build detail line (23 columns according to DGII 607 format)
            $detail = "{$rnc}|{$type}|{$ncf}|{$ncfMod}|{$incomeType}|{$dateComp}|{$datePay}|{$mFact}|{$mItbis}|{$mItbisRet}|{$mItbisPer}|{$mIsrRet}|{$mIsrPer}|{$mIsc}|{$mOtros}|{$mProp}|{$mCash}|{$mBank}|{$mCard}|{$mCredit}|{$mBonos}|{$mPermuta}|{$mOtras}";
            $lines[] = $detail;
        }
        
        $content = implode("\r\n", $lines);
        $filename = "DGII_607_{$companyTaxId}_{$period}.txt";
        
        return response($content, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
    
    /**
     * Export the 606 Report records to a downloadable pipe-separated text file (.txt)
     */
    public function export606(Request $request)
    {
        $request->validate([
            'period' => 'required|string|size:6',
            'records' => 'required|array',
        ]);
        
        $period = $request->input('period');
        $records = $request->input('records');
        $companyTaxId = Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '131000000';
        $companyTaxId = preg_replace('/[^0-9]/', '', $companyTaxId);
        
        // Header: 606|RNC|PERIODO|CANTIDAD_REGISTROS
        $header = "606|{$companyTaxId}|{$period}|" . count($records);
        
        $lines = [$header];
        
        foreach ($records as $r) {
            $rnc = $r['rnc_proveedor'] ?? '';
            $type = $r['tipo_identificacion'] ?? '';
            $serviceType = $r['tipo_bien_servicio'] ?? '02';
            $ncf = $r['ncf'] ?? '';
            $ncfMod = $r['ncf_modificado'] ?? '';
            $dateComp = $r['fecha_comprobante'] ?? '';
            $datePay = $r['fecha_pago'] ?? '';
            
            $mServ = number_format((float)($r['monto_servicios'] ?? 0), 2, '.', '');
            $mBien = number_format((float)($r['monto_bienes'] ?? 0), 2, '.', '');
            $mTotal = number_format((float)($r['total_facturado'] ?? 0), 2, '.', '');
            $mItbis = number_format((float)($r['itbis_facturado'] ?? 0), 2, '.', '');
            $mItbisRet = number_format((float)($r['itbis_retenido'] ?? 0), 2, '.', '');
            $mItbisProp = number_format((float)($r['itbis_proporcional'] ?? 0), 2, '.', '');
            $mItbisCost = number_format((float)($r['itbis_costo'] ?? 0), 2, '.', '');
            $mItbisAdel = number_format((float)($r['itbis_adelantar'] ?? 0), 2, '.', '');
            $mItbisPerc = number_format((float)($r['itbis_percibido'] ?? 0), 2, '.', '');
            
            $isrType = $r['tipo_retencion_isr'] ?? '';
            $mIsrRet = number_format((float)($r['isr_retenido'] ?? 0), 2, '.', '');
            $mIsrPerc = number_format((float)($r['isr_percibido'] ?? 0), 2, '.', '');
            $mIsc = number_format((float)($r['isc'] ?? 0), 2, '.', '');
            $mOtros = number_format((float)($r['otros_impuestos'] ?? 0), 2, '.', '');
            $mProp = number_format((float)($r['propina_legal'] ?? 0), 2, '.', '');
            $payMethod = $r['forma_pago'] ?? '02';
            
            // Build detail line (23 columns)
            $detail = "{$rnc}|{$type}|{$serviceType}|{$ncf}|{$ncfMod}|{$dateComp}|{$datePay}|{$mServ}|{$mBien}|{$mTotal}|{$mItbis}|{$mItbisRet}|{$mItbisProp}|{$mItbisCost}|{$mItbisAdel}|{$mItbisPerc}|{$isrType}|{$mIsrRet}|{$mIsrPerc}|{$mIsc}|{$mOtros}|{$mProp}|{$payMethod}";
            $lines[] = $detail;
        }
        
        $content = implode("\r\n", $lines);
        $filename = "DGII_606_{$companyTaxId}_{$period}.txt";
        
        return response($content, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
