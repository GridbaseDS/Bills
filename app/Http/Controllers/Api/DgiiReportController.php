<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\ReceivedInvoice;
use App\Models\Setting;
use App\Services\DgiiExcelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DgiiReportController extends Controller
{
    /**
     * Fetch records for the 607 Report (Ventas / Sales) for a given month.
     */
    public function report607(Request $request)
    {
        $year = $request->query('year');
        $month = $request->query('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);
        
        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();
        
        // Fetch all invoices issued during this period
        // Exclude drafts and cancelled invoices
        // Includes sales e-CFs (31, 32, 33, 34, 44, 45) and traditional sales
        $invoices = Invoice::with(['client', 'payments'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where(function ($q) {
                $q->whereNull('ecf_type')
                  ->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->orderBy('issue_date', 'asc')
            ->get();
            
        $records = $invoices->map(function ($inv) {
            $taxId = $inv->client ? preg_replace('/[^0-9]/', '', $inv->client->tax_id) : '';
            
            // Determine Identification Type (cannot be empty per DGII prevalidator)
            $typeId = '3'; // Default: Pasaporte/Otro
            if (!empty($taxId)) {
                $len = strlen($taxId);
                if ($len === 9) {
                    $typeId = '1'; // RNC
                } elseif ($len === 11) {
                    $typeId = '2'; // Cédula
                }
            }
            
            // e-NCF / NCF
            $ncf = $inv->is_ecf ? ($inv->encf ?: $inv->invoice_number) : $inv->invoice_number;

            // Currency conversion: All DGII reports must be presented in DOP at official exchange rate
            $rate = ($inv->currency && $inv->currency !== 'DOP') ? (float)($inv->exchange_rate ?? 1.0) : 1.0;
            if ($rate <= 0) {
                $rate = 1.0;
            }
            
            // Payment methods split
            $cash = 0;
            $bank = 0;
            $card = 0;
            $other = 0;
            
            foreach ($inv->payments as $pay) {
                switch ($pay->payment_method) {
                    case 'cash':
                        $cash += (float)$pay->amount;
                        break;
                    case 'bank_transfer':
                    case 'paypal':
                        $bank += (float)$pay->amount;
                        break;
                    case 'credit_card':
                        $card += (float)$pay->amount;
                        break;
                    default:
                        $other += (float)$pay->amount;
                        break;
                }
            }
            
            // Remaining balance is Credit
            $credit = max(0, (float)$inv->total - (float)$inv->amount_paid);
            
            return [
                'id' => $inv->id,
                'rnc_cliente' => $taxId,
                'tipo_identificacion' => $typeId,
                'ncf' => $ncf,
                'ncf_modificado' => $inv->modified_ncf ?? '',
                'tipo_ingreso' => $inv->tipo_ingresos ?? '01',
                'fecha_comprobante' => Carbon::parse($inv->issue_date)->format('Ymd'),
                'fecha_pago' => $inv->paid_at ? Carbon::parse($inv->paid_at)->format('Ymd') : '',
                'monto_facturado' => round((float)($inv->subtotal - ($inv->discount_amount ?? 0)) * $rate, 2),
                'itbis_facturado' => round((float)$inv->tax_amount * $rate, 2),
                'itbis_retenido' => 0.00,
                'itbis_percibido' => '',  // Must be EMPTY per DGII prevalidator (not 0.00)
                'retencion_isr' => 0.00,
                'isr_percibido' => '',    // Must be EMPTY per DGII prevalidator (not 0.00)
                'isc' => 0.00,
                'otros_impuestos' => 0.00,
                'propina_legal' => 0.00,
                'efectivo' => round($cash * $rate, 2),
                'bancos' => round($bank * $rate, 2),
                'tarjeta' => round($card * $rate, 2),
                'credito' => round($credit * $rate, 2),
                'bonos' => 0.00,
                'permuta' => 0.00,
                'otras_formas' => round($other * $rate, 2),
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
        $year = $request->query('year');
        $month = $request->query('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);
        
        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();
        
        // 1. Fetch received invoices (Facturas Recibidas)
        $receivedInvoices = ReceivedInvoice::whereBetween('fecha_emision', [$startDate, $endDate])
            ->orderBy('fecha_emision', 'asc')
            ->get();
            
        // 2. Fetch self-issued invoices of type 41 (Compras/Informal) or 43 (Gastos Menores)
        $selfIssued = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->whereIn('ecf_type', [41, 43])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get();
            
        $records = collect();
        $seenNcfs = [];
        
        // Process Received Invoices
        foreach ($receivedInvoices as $ri) {
            $taxId = preg_replace('/[^0-9]/', '', $ri->rnc_emisor);
            $len = strlen($taxId);
            $typeId = ($len === 9) ? '1' : (($len === 11) ? '2' : '3');
            
            $encfClean = strtoupper(trim($ri->encf ?? ''));
            if (!empty($encfClean)) {
                $seenNcfs[$encfClean] = true;
            }
            
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
            $ncfClean = strtoupper(trim($ncf));
            if (!empty($ncfClean)) {
                $seenNcfs[$ncfClean] = true;
            }
            
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
            $ncf = trim($exp->ncf ?? '');
            $ncfClean = strtoupper($ncf);

            // Deduplication: if this NCF was already registered electronically via ReceivedInvoice or SelfIssued, skip it
            if (!empty($ncfClean) && isset($seenNcfs[$ncfClean])) {
                continue;
            }
            if (!empty($ncfClean)) {
                $seenNcfs[$ncfClean] = true;
            }

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
        $companyTaxId = Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785';
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
            // ITBIS percibido must be EMPTY when not applicable (DGII prevalidator rule)
            $mItbisPer = (!empty($r['itbis_percibido']) && $r['itbis_percibido'] !== '' && (float)$r['itbis_percibido'] > 0) 
                ? number_format((float)$r['itbis_percibido'], 2, '.', '') : '';
            $mIsrRet = number_format((float)($r['retencion_isr'] ?? 0), 2, '.', '');
            // ISR percibido must be EMPTY when not applicable (DGII prevalidator rule)
            $mIsrPer = (!empty($r['isr_percibido']) && $r['isr_percibido'] !== '' && (float)$r['isr_percibido'] > 0) 
                ? number_format((float)$r['isr_percibido'], 2, '.', '') : '';
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
        $companyTaxId = Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785';
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

    /**
     * Fetch records for the 608 Report (Comprobantes Anulados) for a given month.
     */
    public function report608(Request $request)
    {
        $year = $request->query('year');
        $month = $request->query('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);

        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();

        // Invoices with status cancelled within the period that have an official DGII NCF
        $invoices = Invoice::with('client')
            ->where('status', 'cancelled')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('issue_date', [$startDate, $endDate])
                  ->orWhereBetween('cancelled_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })
            ->orderBy('issue_date', 'asc')
            ->get()
            ->filter(function ($inv) {
                $ncf = $inv->is_ecf ? ($inv->encf ?: $inv->invoice_number) : $inv->invoice_number;
                return !empty($ncf) && preg_match('/^[BE][0-9]{10,12}$/i', $ncf);
            })
            ->values();

        $records = $invoices->map(function ($inv) {
            $ncf = $inv->is_ecf ? ($inv->encf ?: $inv->invoice_number) : $inv->invoice_number;
            $anulationCode = str_pad($inv->anulation_type ?: '05', 2, '0', STR_PAD_LEFT);
            $reasonDesc = DgiiExcelService::ANULATION_TYPES[$anulationCode] ?? ($inv->cancellation_reason ?: 'Corrección de la Información');

            return [
                'id' => $inv->id,
                'ncf' => $ncf,
                'fecha_comprobante' => Carbon::parse($inv->issue_date)->format('Ymd'),
                'tipo_anulacion' => $anulationCode,
                'motivo_descripcion' => $reasonDesc,
                'fecha_anulacion' => $inv->cancelled_at ? Carbon::parse($inv->cancelled_at)->format('Ymd') : Carbon::parse($inv->updated_at)->format('Ymd'),
                'monto' => round((float)$inv->total, 2),
                'cliente_nombre' => $inv->client ? ($inv->client->company_name ?: $inv->client->contact_name) : 'General',
            ];
        });

        return response()->json([
            'success' => true,
            'period' => "{$year}{$month}",
            'data' => $records
        ]);
    }

    /**
     * Export the 608 Report records to a downloadable pipe-separated text file (.txt)
     */
    public function export608(Request $request)
    {
        $request->validate([
            'period' => 'required|string|size:6',
            'records' => 'required|array',
        ]);

        $period = $request->input('period');
        $records = $request->input('records');
        $companyTaxId = Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785';
        $companyTaxId = preg_replace('/[^0-9]/', '', $companyTaxId);

        // Header: 608|RNC|PERIODO|CANTIDAD_REGISTROS
        $header = "608|{$companyTaxId}|{$period}|" . count($records);
        $lines = [$header];

        foreach ($records as $r) {
            $ncf = $r['ncf'] ?? '';
            $dateComp = $r['fecha_comprobante'] ?? '';
            $anulationType = str_pad($r['tipo_anulacion'] ?? '05', 2, '0', STR_PAD_LEFT);

            // Detail line per DGII 608 standard: NCF|FECHA|TIPO_ANULACION (3 columns)
            $lines[] = "{$ncf}|{$dateComp}|{$anulationType}";
        }

        $content = implode("\r\n", $lines);
        $filename = "DGII_608_{$companyTaxId}_{$period}.txt";

        return response($content, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export Formato 607 to official DGII Excel Template (.xls)
     */
    public function export607Excel(Request $request, DgiiExcelService $excelService)
    {
        $request->validate([
            'period' => 'required|string|size:6',
            'records' => 'required|array',
        ]);

        $period = $request->input('period');
        $records = $request->input('records');
        $companyTaxId = $request->input('rnc') ?: (Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $companyTaxId = preg_replace('/[^0-9]/', '', $companyTaxId);

        $spreadsheet = $excelService->generate607Excel($companyTaxId, $period, $records);
        $filename = "DGII_607_{$companyTaxId}_{$period}.xls";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Formato 606 to official DGII Excel Template (.xls)
     */
    public function export606Excel(Request $request, DgiiExcelService $excelService)
    {
        $request->validate([
            'period' => 'required|string|size:6',
            'records' => 'required|array',
        ]);

        $period = $request->input('period');
        $records = $request->input('records');
        $companyTaxId = $request->input('rnc') ?: (Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $companyTaxId = preg_replace('/[^0-9]/', '', $companyTaxId);

        $spreadsheet = $excelService->generate606Excel($companyTaxId, $period, $records);
        $filename = "DGII_606_{$companyTaxId}_{$period}.xls";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Formato 608 to official DGII Excel Template (.xls)
     */
    public function export608Excel(Request $request, DgiiExcelService $excelService)
    {
        $request->validate([
            'period' => 'required|string|size:6',
            'records' => 'required|array',
        ]);

        $period = $request->input('period');
        $records = $request->input('records');
        $companyTaxId = $request->input('rnc') ?: (Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $companyTaxId = preg_replace('/[^0-9]/', '', $companyTaxId);

        $spreadsheet = $excelService->generate608Excel($companyTaxId, $period, $records);
        $filename = "DGII_608_{$companyTaxId}_{$period}.xls";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Pre-validate DGII format data (606, 607, 608) using native DGII validation engine
     */
    public function prevalidate(Request $request, string $type, \App\Services\DgiiPrevalidatorService $validator)
    {
        $request->validate([
            'period' => 'required|string|size:6',
            'records' => 'required|array',
        ]);

        $period = $request->input('period');
        $records = $request->input('records');
        $companyTaxId = $request->input('rnc') ?: (Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $companyTaxId = preg_replace('/[^0-9]/', '', $companyTaxId);

        $result = $validator->validateData($type, $companyTaxId, $period, $records);

        return response()->json($result);
    }

    /**
     * Get IT-1 and Anexo A summary calculation for the period
     */
    public function getIt1Summary(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->query('year');
        $month = $request->query('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);

        $summary = $declarationService->calculateIt1Data($year, $month);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Export Formulario Oficial IT-1 and Anexo A prefilled in official DGII Excel (.xls)
     */
    public function exportIt1Excel(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->input('year') ?: $request->query('year');
        $month = $request->input('month') ?: $request->query('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);

        $spreadsheet = $declarationService->generateIt1Excel($year, $month);
        $periodRaw = "{$year}{$month}";
        $companyTaxId = preg_replace('/[^0-9]/', '', Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $filename = "DGII_IT1_{$companyTaxId}_{$periodRaw}.xls";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Get IR-2 and Anexo B-1/J/A-1 summary calculation for the fiscal year
     */
    public function getIr2Summary(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->query('year') ?: $request->input('year');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
        }
        $year = $year ?: date('Y');

        $summary = $declarationService->calculateIr2Data((string)$year);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Export Formulario Oficial IR-2 and Annexes prefilled in official DGII Excel (.xls)
     */
    public function exportIr2Excel(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->input('year') ?: $request->query('year');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
        }
        $year = $year ?: date('Y');

        $spreadsheet = $declarationService->generateIr2Excel((string)$year);
        $companyTaxId = preg_replace('/[^0-9]/', '', Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $filename = "DGII_IR2_{$companyTaxId}_{$year}.xls";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Get ITC-01 (IST Telecomunicaciones) summary calculation for the period
     */
    public function getItcSummary(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->query('year') ?: $request->input('year');
        $month = $request->query('month') ?: $request->input('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);

        $summary = $declarationService->calculateItcData((string)$year, (string)$month);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Export Formulario Oficial ITC-01 prefilled in official DGII Excel (.xls)
     */
    public function exportItcExcel(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->input('year') ?: $request->query('year');
        $month = $request->input('month') ?: $request->query('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);

        $spreadsheet = $declarationService->generateItcExcel((string)$year, (string)$month);
        $periodRaw = "{$year}{$month}";
        $companyTaxId = preg_replace('/[^0-9]/', '', Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $filename = "DGII_ITC01_{$companyTaxId}_{$periodRaw}.xls";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Get DSS-07 (Impuesto Sobre Seguros) summary calculation for the period
     */
    public function getDssSummary(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->query('year') ?: $request->input('year');
        $month = $request->query('month') ?: $request->input('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);

        $summary = $declarationService->calculateDssData((string)$year, (string)$month);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Export Formulario Oficial DSS-07 prefilled in official DGII Excel (.xls)
     */
    public function exportDssExcel(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->input('year') ?: $request->query('year');
        $month = $request->input('month') ?: $request->query('month');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
            $month = substr($p, 4, 2);
        }
        $year = $year ?: date('Y');
        $month = str_pad($month ?: date('m'), 2, '0', STR_PAD_LEFT);

        $spreadsheet = $declarationService->generateDssExcel((string)$year, (string)$month);
        $periodRaw = "{$year}{$month}";
        $companyTaxId = preg_replace('/[^0-9]/', '', Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $filename = "DGII_DSS07_{$companyTaxId}_{$periodRaw}.xls";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Get Formulario DAF summary calculation for the fiscal year
     */
    public function getDafSummary(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->query('year') ?: $request->input('year');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
        }
        $year = $year ?: date('Y');

        $summary = $declarationService->calculateDafData((string)$year);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Export Formulario Oficial DAF prefilled in official DGII Excel (.xls)
     */
    public function exportDafExcel(Request $request, ?\App\Services\DgiiDeclarationService $declarationService = null)
    {
        $declarationService = $declarationService ?? app(\App\Services\DgiiDeclarationService::class);
        $year = $request->input('year') ?: $request->query('year');
        if ($request->filled('period')) {
            $p = str_replace('-', '', $request->input('period'));
            $year = substr($p, 0, 4);
        }
        $year = $year ?: date('Y');

        $spreadsheet = $declarationService->generateDafExcel((string)$year);
        $companyTaxId = preg_replace('/[^0-9]/', '', Setting::where('setting_key', 'company_tax_id')->value('setting_value') ?? '132456785');
        $filename = "DGII_DAF_{$companyTaxId}_{$year}.xls";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

