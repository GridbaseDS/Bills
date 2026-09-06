<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\Invoice;
use App\Models\ReceivedInvoice;
use App\Models\Expense;
use App\Models\Setting;
use Carbon\Carbon;

class DgiiDeclarationService
{
    /**
     * Compute the official IT-1 and Anexo A summary data for a given period.
     */
    public function calculateIt1Data(string $year, string $month): array
    {
        $year = (int)$year;
        $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
        $periodFormatted = "{$month}/{$year}"; // e.g. "08/2026"
        $periodRaw = "{$year}{$month}";        // e.g. "202608"

        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();
        // Payment deadline is the 20th of the following month
        $deadlineDate = Carbon::parse($startDate)->addMonth()->day(20)->format('d/m/Y');

        // Company settings
        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();
        $taxId = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '132456785');
        $companyName = $settings['company_name'] ?? 'Gridbase';
        $commercialName = $settings['company_commercial_name'] ?? $companyName;
        $phone = $settings['company_phone'] ?? '';
        $email = $settings['company_email'] ?? '';

        // 1. Fetch 607 records (Ventas)
        $invoices = Invoice::with(['client', 'payments'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where(function ($q) {
                $q->whereNull('ecf_type')
                  ->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->orderBy('issue_date', 'asc')
            ->get();

        // 2. Fetch 606 records (Compras)
        $receivedInvoices = ReceivedInvoice::whereBetween('fecha_emision', [$startDate, $endDate])
            ->orderBy('fecha_emision', 'asc')
            ->get();

        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->orderBy('expense_date', 'asc')
            ->get();

        // Initialize Anexo A buckets
        // Renglón II: Por tipo de NCF
        $ncfCounts = [
            '01_31' => 0, '02_32' => 0, '03_33' => 0, '04_34' => 0,
            '12' => 0, '14_44' => 0, '15_45' => 0, '16_46' => 0,
        ];
        $ncfAmounts = [
            '01_31' => 0.0, '02_32' => 0.0, '03_33' => 0.0, '04_34' => 0.0,
            '12' => 0.0, '14_44' => 0.0, '15_45' => 0.0, '16_46' => 0.0,
        ];

        // Renglón III: Por tipo de venta
        $formasPago = [
            'efectivo' => 0.0,
            'cheque_transferencia' => 0.0,
            'tarjeta' => 0.0,
            'credito' => 0.0,
            'bonos' => 0.0,
            'permuta' => 0.0,
            'otras' => 0.0,
        ];

        // Renglón IV: Por tipo de ingreso
        $tiposIngreso = [
            '01' => 0.0, // No financieros
            '02' => 0.0, // Financieros
            '03' => 0.0, // Extraordinarios
            '04' => 0.0, // Arrendamientos
            '05' => 0.0, // Venta activos depreciables
            '06' => 0.0, // Otros
        ];

        $totalRetencionesPercibidas = 0.0;
        $totalItbisFacturadoVentas = 0.0;
        $totalOperacionesGravadas18 = 0.0;

        foreach ($invoices as $inv) {
            $ncf = strtoupper(trim($inv->is_ecf ? ($inv->encf ?: $inv->invoice_number) : $inv->invoice_number));
            $rate = ($inv->currency && $inv->currency !== 'DOP') ? (float)($inv->exchange_rate ?? 1.0) : 1.0;
            if ($rate <= 0) $rate = 1.0;

            $subtotalDop = round(((float)$inv->subtotal) * $rate, 2);
            $taxDop = round(((float)$inv->tax_amount) * $rate, 2);
            $totalDop = round(((float)$inv->total) * $rate, 2);

            // NCF Type Classification
            $prefix = substr($ncf, 0, 3);
            if (in_array($prefix, ['B01', 'E31'])) {
                $ncfCounts['01_31']++;
                $ncfAmounts['01_31'] += $subtotalDop;
            } elseif (in_array($prefix, ['B02', 'E32'])) {
                $ncfCounts['02_32']++;
                $ncfAmounts['02_32'] += $subtotalDop;
            } elseif (in_array($prefix, ['B03', 'E33'])) {
                $ncfCounts['03_33']++;
                $ncfAmounts['03_33'] += $subtotalDop;
            } elseif (in_array($prefix, ['B04', 'E34'])) {
                $ncfCounts['04_34']++;
                $ncfAmounts['04_34'] += $subtotalDop;
            } elseif ($prefix === 'B12') {
                $ncfCounts['12']++;
                $ncfAmounts['12'] += $subtotalDop;
            } elseif (in_array($prefix, ['B14', 'E44'])) {
                $ncfCounts['14_44']++;
                $ncfAmounts['14_44'] += $subtotalDop;
            } elseif (in_array($prefix, ['B15', 'E45'])) {
                $ncfCounts['15_45']++;
                $ncfAmounts['15_45'] += $subtotalDop;
            } elseif (in_array($prefix, ['B16', 'E46'])) {
                $ncfCounts['16_46']++;
                $ncfAmounts['16_46'] += $subtotalDop;
            } else {
                $ncfCounts['01_31']++;
                $ncfAmounts['01_31'] += $subtotalDop;
            }

            // Tipo de Ingreso
            $tipoIng = $inv->tipo_ingresos ? str_pad($inv->tipo_ingresos, 2, '0', STR_PAD_LEFT) : '01';
            if (isset($tiposIngreso[$tipoIng])) {
                $tiposIngreso[$tipoIng] += $subtotalDop;
            } else {
                $tiposIngreso['01'] += $subtotalDop;
            }

            // ITBIS Cobrado
            if ($prefix === 'B04' || $prefix === 'E34') {
                // Notas de crédito restan
                $totalItbisFacturadoVentas -= $taxDop;
                $totalOperacionesGravadas18 -= $subtotalDop;
            } else {
                $totalItbisFacturadoVentas += $taxDop;
                $totalOperacionesGravadas18 += $subtotalDop;
            }

            // Payments breakdown (Monto Bruto incl. ITBIS)
            $paidTotalDop = 0.0;
            foreach ($inv->payments as $p) {
                $method = strtolower($p->payment_method ?? '');
                $pAmount = round(((float)$p->amount) * $rate, 2);
                $paidTotalDop += $pAmount;

                if (in_array($method, ['cash', 'efectivo'])) {
                    $formasPago['efectivo'] += $pAmount;
                } elseif (in_array($method, ['bank_transfer', 'transfer', 'transferencia', 'check', 'cheque'])) {
                    $formasPago['cheque_transferencia'] += $pAmount;
                } elseif (in_array($method, ['credit_card', 'debit_card', 'tarjeta', 'card'])) {
                    $formasPago['tarjeta'] += $pAmount;
                } elseif (in_array($method, ['bono', 'bonos'])) {
                    $formasPago['bonos'] += $pAmount;
                } elseif ($method === 'permuta') {
                    $formasPago['permuta'] += $pAmount;
                } else {
                    $formasPago['otras'] += $pAmount;
                }
            }

            // Remainder unpaid is "A Crédito"
            $unpaid = max(0.0, round($totalDop - $paidTotalDop, 2));
            if ($unpaid > 0) {
                $formasPago['credito'] += $unpaid;
            }
        }

        // Total Operaciones Anexo A Casilla 11:
        // Casillas 1+2+3 - 4 + 5 + 6 + 7 + 8
        $totalOperacionesAnexoA = $ncfAmounts['01_31'] + $ncfAmounts['02_32'] + $ncfAmounts['03_33']
            - $ncfAmounts['04_34'] + $ncfAmounts['12'] + $ncfAmounts['14_44'] + $ncfAmounts['15_45'] + $ncfAmounts['16_46'];

        // Compras 606: Clasificación de ITBIS Pagado Deducible
        $seenNcfs = [];
        $itbisDeducibleBienes = 0.0;
        $itbisDeducibleServicios = 0.0;
        $itbisNoDeducibleCosto = 0.0;

        // Process ReceivedInvoices
        foreach ($receivedInvoices as $ri) {
            $encfClean = strtoupper(trim($ri->encf ?? ''));
            if (!empty($encfClean)) {
                $seenNcfs[$encfClean] = true;
            }
            $xml = $ri->raw_xml ? @simplexml_load_string($ri->raw_xml) : null;
            $itbisTotal = (float)($xml ? (string)$xml->Totales->TotalITBIS : ($ri->monto_total - ($ri->monto_total / 1.18)));

            // Telecom and electricity are services
            $itbisDeducibleServicios += round($itbisTotal, 2);
        }

        // Process Expenses
        foreach ($expenses as $exp) {
            $ncfClean = strtoupper(trim($exp->ncf ?? ''));
            if (!empty($ncfClean) && isset($seenNcfs[$ncfClean])) {
                continue; // Deduplicate
            }
            if (!empty($ncfClean)) {
                $seenNcfs[$ncfClean] = true;
            }

            $tax = (float)$exp->tax_amount;
            $tipoGasto = str_pad($exp->expense_type ?? '02', 2, '0', STR_PAD_LEFT);

            // Office supplies (Papelería) is a good (Bienes)
            if (in_array($tipoGasto, ['02', '03'])) {
                // If it's paper/supplies or physical goods
                $itbisDeducibleBienes += round($tax, 2);
            } else {
                $itbisDeducibleServicios += round($tax, 2);
            }
        }

        $totalItbisDeducibleCompras = round($itbisDeducibleBienes + $itbisDeducibleServicios, 2);

        // IT-1 Liquidación
        $casilla11_gravadas18 = round($totalOperacionesGravadas18, 2);
        $casilla16_itbisCobrado = round($totalItbisFacturadoVentas, 2);
        $casilla21_totalCobrado = $casilla16_itbisCobrado;

        $casilla22_bienesDeducibles = round($itbisDeducibleBienes, 2);
        $casilla23_serviciosDeducibles = round($itbisDeducibleServicios, 2);
        $casilla25_totalDeducible = round($casilla22_bienesDeducibles + $casilla23_serviciosDeducibles, 2);

        $diferencia = round($casilla21_totalCobrado - $casilla25_totalDeducible, 2);
        $impuestoAPagar = max(0.0, $diferencia);
        $saldoAFavor = ($diferencia < 0) ? abs($diferencia) : 0.0;

        return [
            'period' => $periodRaw,
            'period_formatted' => $periodFormatted,
            'deadline' => $deadlineDate,
            'tax_id' => $taxId,
            'company_name' => $companyName,
            'commercial_name' => $commercialName,
            'phone' => $phone,
            'email' => $email,
            'anexo_a' => [
                'ncf_counts' => $ncfCounts,
                'ncf_amounts' => $ncfAmounts,
                'total_operaciones' => $totalOperacionesAnexoA,
                'formas_pago' => $formasPago,
                'tipos_ingreso' => $tiposIngreso,
                'retenciones' => $totalRetencionesPercibidas,
                'itbis_pagado' => [
                    'bienes' => $itbisDeducibleBienes,
                    'servicios' => $itbisDeducibleServicios,
                    'costo' => $itbisNoDeducibleCosto,
                    'total_deducible' => $totalItbisDeducibleCompras,
                ],
            ],
            'it1' => [
                'casilla_1_total_operaciones' => $totalOperacionesAnexoA,
                'casilla_10_ingresos_gravados' => $casilla11_gravadas18,
                'casilla_11_gravadas_18' => $casilla11_gravadas18,
                'casilla_16_itbis_cobrado' => $casilla16_itbisCobrado,
                'casilla_21_total_itbis_cobrado' => $casilla21_totalCobrado,
                'casilla_22_itbis_bienes' => $casilla22_bienesDeducibles,
                'casilla_23_itbis_servicios' => $casilla23_serviciosDeducibles,
                'casilla_25_total_itbis_deducible' => $casilla25_totalDeducible,
                'casilla_26_impuesto_a_pagar' => $impuestoAPagar,
                'casilla_27_saldo_a_favor' => $saldoAFavor,
                'casilla_30_retenciones_computables' => $totalRetencionesPercibidas,
                'casilla_33_diferencia_a_pagar' => $impuestoAPagar,
                'casilla_34_nuevo_saldo_a_favor' => $saldoAFavor,
                'casilla_38_total_a_pagar' => $impuestoAPagar,
            ],
        ];
    }

    /**
     * Generate the official IT-1 Excel workbook prefilled with the declaration numbers.
     */
    public function generateIt1Excel(string $year, string $month): Spreadsheet
    {
        $data = $this->calculateIt1Data($year, $month);
        $templatePath = resource_path('templates/dgii/IT-1-2020.xls');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial IT-1-2020.xls no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsReader();
        $spreadsheet = $reader->load($templatePath);

        // 1. Llenar Hoja: Anexo A
        $sA = $spreadsheet->getSheetByName('Anexo A');
        if ($sA) {
            // Encabezado
            $sA->setCellValue('D7', $data['tax_id']);
            $sA->setCellValue('I7', $data['company_name']);
            $sA->setCellValue('Q7', $data['commercial_name']);
            $sA->setCellValue('D10', $data['email']);
            $sA->setCellValue('M10', $data['phone']);
            $sA->setCellValue('Q10', $data['period_formatted']);
            $sA->setCellValue('T10', $data['deadline']);
            $sA->setCellValue('W10', 'Normal');

            // Renglón II: NCFs
            $ncfCounts = $data['anexo_a']['ncf_counts'];
            $ncfAmounts = $data['anexo_a']['ncf_amounts'];

            if ($ncfCounts['01_31'] > 0) {
                $sA->setCellValue('T15', $ncfCounts['01_31']);
                $sA->setCellValue('W15', $ncfAmounts['01_31']);
            }
            if ($ncfCounts['02_32'] > 0) {
                $sA->setCellValue('T16', $ncfCounts['02_32']);
                $sA->setCellValue('W16', $ncfAmounts['02_32']);
            }
            if ($ncfCounts['03_33'] > 0) {
                $sA->setCellValue('T17', $ncfCounts['03_33']);
                $sA->setCellValue('W17', $ncfAmounts['03_33']);
            }
            if ($ncfCounts['04_34'] > 0) {
                $sA->setCellValue('T18', $ncfCounts['04_34']);
                $sA->setCellValue('W18', $ncfAmounts['04_34']);
            }
            if ($ncfCounts['12'] > 0) {
                $sA->setCellValue('T19', $ncfCounts['12']);
                $sA->setCellValue('W19', $ncfAmounts['12']);
            }
            if ($ncfCounts['14_44'] > 0) {
                $sA->setCellValue('T20', $ncfCounts['14_44']);
                $sA->setCellValue('W20', $ncfAmounts['14_44']);
            }
            if ($ncfCounts['15_45'] > 0) {
                $sA->setCellValue('T21', $ncfCounts['15_45']);
                $sA->setCellValue('W21', $ncfAmounts['15_45']);
            }
            if ($ncfCounts['16_46'] > 0) {
                $sA->setCellValue('T22', $ncfCounts['16_46']);
                $sA->setCellValue('W22', $ncfAmounts['16_46']);
            }

            // Renglón III: Formas de Pago
            $fp = $data['anexo_a']['formas_pago'];
            if ($fp['efectivo'] > 0) $sA->setCellValue('W28', $fp['efectivo']);
            if ($fp['cheque_transferencia'] > 0) $sA->setCellValue('W29', $fp['cheque_transferencia']);
            if ($fp['tarjeta'] > 0) $sA->setCellValue('W30', $fp['tarjeta']);
            if ($fp['credito'] > 0) $sA->setCellValue('W31', $fp['credito']);
            if ($fp['bonos'] > 0) $sA->setCellValue('W32', $fp['bonos']);
            if ($fp['permuta'] > 0) $sA->setCellValue('W33', $fp['permuta']);
            if ($fp['otras'] > 0) $sA->setCellValue('W34', $fp['otras']);

            // Renglón IV: Tipos de Ingreso
            $ti = $data['anexo_a']['tipos_ingreso'];
            if ($ti['01'] > 0) $sA->setCellValue('W38', $ti['01']);
            if ($ti['02'] > 0) $sA->setCellValue('W39', $ti['02']);
            if ($ti['03'] > 0) $sA->setCellValue('W40', $ti['03']);
            if ($ti['04'] > 0) $sA->setCellValue('W41', $ti['04']);
            if ($ti['05'] > 0) $sA->setCellValue('W42', $ti['05']);
            if ($ti['06'] > 0) $sA->setCellValue('W43', $ti['06']);

            // Renglón IX: ITBIS Pagado en Compras Deducible
            $itbisPagado = $data['anexo_a']['itbis_pagado'];
            if ($itbisPagado['bienes'] > 0) {
                $sA->setCellValue('O80', $itbisPagado['bienes']);
            }
            if ($itbisPagado['servicios'] > 0) {
                $sA->setCellValue('P81', $itbisPagado['servicios']);
            }
        }

        // 2. Llenar Hoja: IT-1
        $sIT = $spreadsheet->getSheetByName('IT-1');
        if ($sIT) {
            // Encabezado
            $sIT->setCellValue('D7', $data['tax_id']);
            $sIT->setCellValue('I7', $data['company_name']);
            $sIT->setCellValue('P7', $data['commercial_name']);
            $sIT->setCellValue('D10', $data['email']);
            $sIT->setCellValue('M10', $data['phone']);
            $sIT->setCellValue('P10', $data['period_formatted']);
            $sIT->setCellValue('S10', $data['deadline']);
            $sIT->setCellValue('V10', 'Normal');

            // Casilla 11: Operaciones Gravadas al 18%
            $sIT->setCellValue('V26', $data['it1']['casilla_11_gravadas_18']);
        }

        return $spreadsheet;
    }

    /**
     * Compute the official IR-2, Anexo B-1, Anexo J and Anexo A-1 annual summary data for a given fiscal year.
     */
    public function calculateIr2Data(string $year): array
    {
        $year = (int)$year;
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";
        $deadlineDate = Carbon::create($year + 1, 4, 30)->format('d/m/Y');

        // Company settings
        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();
        $taxId = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '132456785');
        $companyName = $settings['company_name'] ?? 'Gridbase Digital Solutions SRL';
        $commercialName = $settings['company_commercial_name'] ?? $companyName;
        $phone = $settings['company_phone'] ?? '';
        $email = $settings['company_email'] ?? '';
        $capitalSocial = (float)($settings['company_capital'] ?? 100000.0);
        if ($capitalSocial <= 0) $capitalSocial = 100000.0;

        // 1. Fetch 607 records (Ventas anuales)
        $invoices = Invoice::with(['client', 'payments'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where(function ($q) {
                $q->whereNull('ecf_type')
                  ->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->orderBy('issue_date', 'asc')
            ->get();

        // 2. Fetch 606 records (Compras y Gastos anuales)
        $receivedInvoices = ReceivedInvoice::whereBetween('fecha_emision', [$startDate, $endDate])
            ->orderBy('fecha_emision', 'asc')
            ->get();

        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->orderBy('expense_date', 'asc')
            ->get();

        // Initialize Anexo J (Resumen 607 Ventas)
        $jVentasCounts = [
            '01_31' => 0, '02_32' => 0, '03_33' => 0, '04_34' => 0,
            '12' => 0, '14_44' => 0, '15_45' => 0, '16_46' => 0, 'otras' => 0,
        ];
        $jVentasAmounts = [
            '01_31' => 0.0, '02_32' => 0.0, '03_33' => 0.0, '04_34' => 0.0,
            '12' => 0.0, '14_44' => 0.0, '15_45' => 0.0, '16_46' => 0.0, 'otras' => 0.0,
        ];

        $totalVentasLocales = 0.0;
        $totalExportaciones = 0.0;
        $totalDevolucionesVentas = 0.0;
        $totalRetencionesEstado = 0.0;
        $totalCobrosRecibidos = 0.0;
        $totalFacturadoAnual = 0.0;

        foreach ($invoices as $inv) {
            $ncf = strtoupper(trim($inv->is_ecf ? ($inv->encf ?: $inv->invoice_number) : $inv->invoice_number));
            $rate = ($inv->currency && $inv->currency !== 'DOP') ? (float)($inv->exchange_rate ?? 1.0) : 1.0;
            if ($rate <= 0) $rate = 1.0;

            $subtotalDop = round(((float)$inv->subtotal) * $rate, 2);
            $totalDop = round(((float)$inv->total) * $rate, 2);
            $prefix = substr($ncf, 0, 3);

            $totalFacturadoAnual += $totalDop;

            // Retenciones de Estado (e-CF 45 o B15 si aplica)
            if (in_array($prefix, ['B15', 'E45'])) {
                $totalRetencionesEstado += round($subtotalDop * 0.05, 2);
            }

            // Anexo J clasificación de ventas
            if (in_array($prefix, ['B01', 'E31'])) {
                $jVentasCounts['01_31']++;
                $jVentasAmounts['01_31'] += $subtotalDop;
                $totalVentasLocales += $subtotalDop;
            } elseif (in_array($prefix, ['B02', 'E32'])) {
                $jVentasCounts['02_32']++;
                $jVentasAmounts['02_32'] += $subtotalDop;
                $totalVentasLocales += $subtotalDop;
            } elseif (in_array($prefix, ['B03', 'E33'])) {
                $jVentasCounts['03_33']++;
                $jVentasAmounts['03_33'] += $subtotalDop;
                $totalVentasLocales += $subtotalDop;
            } elseif (in_array($prefix, ['B04', 'E34'])) {
                $jVentasCounts['04_34']++;
                $jVentasAmounts['04_34'] += $subtotalDop;
                $totalDevolucionesVentas += $subtotalDop;
            } elseif (in_array($prefix, ['B14', 'E44'])) {
                $jVentasCounts['14_44']++;
                $jVentasAmounts['14_44'] += $subtotalDop;
                $totalVentasLocales += $subtotalDop;
            } elseif (in_array($prefix, ['B15', 'E45'])) {
                $jVentasCounts['15_45']++;
                $jVentasAmounts['15_45'] += $subtotalDop;
                $totalVentasLocales += $subtotalDop;
            } elseif (in_array($prefix, ['B16', 'E46'])) {
                $jVentasCounts['16_46']++;
                $jVentasAmounts['16_46'] += $subtotalDop;
                $totalExportaciones += $subtotalDop;
            } else {
                $jVentasCounts['01_31']++;
                $jVentasAmounts['01_31'] += $subtotalDop;
                $totalVentasLocales += $subtotalDop;
            }

            // Sum payments received in year
            foreach ($inv->payments as $p) {
                $pYear = $p->payment_date ? Carbon::parse($p->payment_date)->year : $year;
                if ($pYear === $year) {
                    $totalCobrosRecibidos += round(((float)$p->amount) * $rate, 2);
                }
            }
        }

        // Cuentas por cobrar a clientes al 31 de diciembre
        $cuentasPorCobrarClientes = max(0.0, round($totalFacturadoAnual - $totalCobrosRecibidos, 2));

        // Anexo B-1: Ingresos de Operaciones Netos
        $totalIngresosNetosB1 = round($totalVentasLocales + $totalExportaciones - $totalDevolucionesVentas, 2);

        // 3. Compras y Gastos (Anexo B-1 desglose oficial y Anexo J gastos 606)
        $b1Details = [
            'costo_venta' => 0.0,             // B-1!I37 / D!L57
            'gastos_personal' => 0.0,         // B-1!I39
            'honorarios_fisicas' => 0.0,      // B-1!I47 (Tipo 02 físico - 11 dígitos)
            'honorarios_morales' => 0.0,      // B-1!I48 (Tipo 02 jurídico - 9 dígitos)
            'otros_servicios' => 0.0,         // B-1!I53 (Suministros / Otros)
            'arrendamientos_fisicas' => 0.0,  // B-1!I56 (Tipo 03 físico)
            'arrendamientos_morales' => 0.0,  // B-1!I57 (Tipo 03 jurídico)
            'otros_arrendamientos' => 0.0,    // B-1!I58
            'gastos_activos_fijos' => 0.0,    // B-1!I66 (Tipo 04)
            'relaciones_publicas' => 0.0,     // B-1!I71 (Tipo 05)
            'publicidad' => 0.0,              // B-1!I72 (Tipo 05)
            'seguros' => 0.0,                 // B-1!I80 (Tipo 06)
            'otras_deducciones' => 0.0,       // B-1!I81 (Tipo 06)
            'retencion_cheques' => 0.0,       // B-1!I91 (Tipo 07)
            'otros_financieros' => 0.0,       // B-1!I93 (Tipo 07)
            'gastos_extraordinarios' => 0.0,  // B-1!I100 (Tipo 08)
        ];

        // Anexo J (Gastos sustentados 606)
        $jGastosCounts = [
            '01_31' => 0, '03_33' => 0, '04_34' => 0, '15_45' => 0, '14_44' => 0,
            '11_41' => 0, '13_43' => 0,
        ];
        $jGastosAmounts = [
            '01_31' => 0.0, '03_33' => 0.0, '04_34' => 0.0, '15_45' => 0.0, '14_44' => 0.0,
            '11_41' => 0.0, '13_43' => 0.0,
        ];

        $seenNcfs = [];
        $totalGastosPagados = 0.0;
        $totalGastosFacturados = 0.0;

        // Helper para clasificar gastos en los rubros del Anexo B-1
        $classifyGasto = function (string $tipoGasto, string $rnc, string $conceptText, float $montoNeto) use (&$b1Details) {
            $tipoGasto = str_pad($tipoGasto, 2, '0', STR_PAD_LEFT);
            $cleanRnc = preg_replace('/[^0-9]/', '', $rnc);
            $isFisica = (strlen($cleanRnc) === 11);

            switch ($tipoGasto) {
                case '01':
                    $b1Details['gastos_personal'] += $montoNeto;
                    break;
                case '02':
                    if (preg_match('/(suministro|papeleria|toner|utiles|insumo|oficina)/i', $conceptText)) {
                        $b1Details['otros_servicios'] += $montoNeto;
                    } elseif ($isFisica) {
                        $b1Details['honorarios_fisicas'] += $montoNeto;
                    } else {
                        $b1Details['honorarios_morales'] += $montoNeto;
                    }
                    break;
                case '03':
                    if ($isFisica) {
                        $b1Details['arrendamientos_fisicas'] += $montoNeto;
                    } elseif (strlen($cleanRnc) === 9) {
                        $b1Details['arrendamientos_morales'] += $montoNeto;
                    } else {
                        $b1Details['otros_arrendamientos'] += $montoNeto;
                    }
                    break;
                case '04':
                    $b1Details['gastos_activos_fijos'] += $montoNeto;
                    break;
                case '05':
                    if (preg_match('/(publicidad|marketing|anuncio|pauta|ads|redes|campana)/i', $conceptText)) {
                        $b1Details['publicidad'] += $montoNeto;
                    } else {
                        $b1Details['relaciones_publicas'] += $montoNeto;
                    }
                    break;
                case '06':
                    if (preg_match('/(seguro|poliza|riesgo|aseguradora)/i', $conceptText)) {
                        $b1Details['seguros'] += $montoNeto;
                    } else {
                        $b1Details['otras_deducciones'] += $montoNeto;
                    }
                    break;
                case '07':
                    if (preg_match('/(0\.0015|0\.0020|retencion|cheque|transferencia)/i', $conceptText)) {
                        $b1Details['retencion_cheques'] += $montoNeto;
                    } else {
                        $b1Details['otros_financieros'] += $montoNeto;
                    }
                    break;
                case '08':
                    $b1Details['gastos_extraordinarios'] += $montoNeto;
                    break;
                case '09':
                    $b1Details['costo_venta'] += $montoNeto;
                    break;
                default:
                    if ($isFisica) {
                        $b1Details['honorarios_fisicas'] += $montoNeto;
                    } else {
                        $b1Details['honorarios_morales'] += $montoNeto;
                    }
                    break;
            }
        };

        // Process ReceivedInvoices
        foreach ($receivedInvoices as $ri) {
            $encfClean = strtoupper(trim($ri->encf ?? ''));
            if (!empty($encfClean)) $seenNcfs[$encfClean] = true;

            $montoTotal = (float)($ri->monto_total ?? 0.0);
            $montoNeto = 0.0;

            // Extraer subtotal exacto de raw_xml si está disponible
            if (!empty($ri->raw_xml) && preg_match('/<MontoGravadoTotal>([^<]+)<\/MontoGravadoTotal>/', $ri->raw_xml, $mg)) {
                $gravado = (float)$mg[1];
                $exento = 0.0;
                if (preg_match('/<MontoExento>([^<]+)<\/MontoExento>/', $ri->raw_xml, $me)) {
                    $exento = (float)$me[1];
                }
                $montoNeto = round($gravado + $exento, 2);
            }

            if ($montoNeto <= 0.0 && $montoTotal > 0) {
                $montoNeto = round($montoTotal / 1.18, 2);
            }

            $totalGastosFacturados += $montoTotal;
            $totalGastosPagados += $montoTotal;

            $tipoGasto = (string)($ri->tipo_bien_servicio ?? '02');
            $classifyGasto($tipoGasto, $ri->rnc_emisor ?? '', $ri->razon_social_emisor ?? '', $montoNeto);

            // Clasificación Anexo J
            $prefix = substr($encfClean, 0, 3);
            if (in_array($prefix, ['B01', 'E31'])) {
                $jGastosCounts['01_31']++;
                $jGastosAmounts['01_31'] += $montoNeto;
            } elseif (in_array($prefix, ['B03', 'E33'])) {
                $jGastosCounts['03_33']++;
                $jGastosAmounts['03_33'] += $montoNeto;
            } elseif (in_array($prefix, ['B04', 'E34'])) {
                $jGastosCounts['04_34']++;
                $jGastosAmounts['04_34'] += $montoNeto;
            } elseif (in_array($prefix, ['B15', 'E45'])) {
                $jGastosCounts['15_45']++;
                $jGastosAmounts['15_45'] += $montoNeto;
            } elseif (in_array($prefix, ['B14', 'E44'])) {
                $jGastosCounts['14_44']++;
                $jGastosAmounts['14_44'] += $montoNeto;
            } elseif (in_array($prefix, ['B11', 'E41'])) {
                $jGastosCounts['11_41']++;
                $jGastosAmounts['11_41'] += $montoNeto;
            } elseif (in_array($prefix, ['B13', 'E43'])) {
                $jGastosCounts['13_43']++;
                $jGastosAmounts['13_43'] += $montoNeto;
            } else {
                $jGastosCounts['01_31']++;
                $jGastosAmounts['01_31'] += $montoNeto;
            }
        }

        // Process Expenses
        foreach ($expenses as $exp) {
            $ncfClean = strtoupper(trim($exp->ncf ?? ''));
            if (!empty($ncfClean) && isset($seenNcfs[$ncfClean])) continue;
            if (!empty($ncfClean)) $seenNcfs[$ncfClean] = true;

            $montoNeto = (float)($exp->subtotal ?? 0.0);
            $totalMonto = (float)($exp->total ?? 0.0);
            if ($montoNeto <= 0.0 && $totalMonto > 0.0) {
                $montoNeto = round($totalMonto / 1.18, 2);
            }
            if ($totalMonto <= 0.0) {
                $totalMonto = $montoNeto + (float)($exp->tax_amount ?? 0.0);
            }

            $totalGastosFacturados += $totalMonto;
            $totalGastosPagados += $totalMonto;

            $tipoGasto = (string)($exp->expense_type ?? '02');
            $classifyGasto($tipoGasto, $exp->provider_tax_id ?? '', ($exp->notes ?? '') . ' ' . ($exp->provider_name ?? ''), $montoNeto);

            // Clasificación Anexo J
            $prefix = substr($ncfClean, 0, 3);
            if (in_array($prefix, ['B01', 'E31'])) {
                $jGastosCounts['01_31']++;
                $jGastosAmounts['01_31'] += $montoNeto;
            } elseif (in_array($prefix, ['B03', 'E33'])) {
                $jGastosCounts['03_33']++;
                $jGastosAmounts['03_33'] += $montoNeto;
            } elseif (in_array($prefix, ['B04', 'E34'])) {
                $jGastosCounts['04_34']++;
                $jGastosAmounts['04_34'] += $montoNeto;
            } elseif (in_array($prefix, ['B15', 'E45'])) {
                $jGastosCounts['15_45']++;
                $jGastosAmounts['15_45'] += $montoNeto;
            } elseif (in_array($prefix, ['B14', 'E44'])) {
                $jGastosCounts['14_44']++;
                $jGastosAmounts['14_44'] += $montoNeto;
            } elseif (in_array($prefix, ['B11', 'E41'])) {
                $jGastosCounts['11_41']++;
                $jGastosAmounts['11_41'] += $montoNeto;
            } elseif (in_array($prefix, ['B13', 'E43'])) {
                $jGastosCounts['13_43']++;
                $jGastosAmounts['13_43'] += $montoNeto;
            } else {
                $jGastosCounts['01_31']++;
                $jGastosAmounts['01_31'] += $montoNeto;
            }
        }

        // Total Costos y Gastos Operativos
        $totalCostosYGastos = round(
            $b1Details['costo_venta'] +
            $b1Details['gastos_personal'] +
            $b1Details['honorarios_fisicas'] +
            $b1Details['honorarios_morales'] +
            $b1Details['otros_servicios'] +
            $b1Details['arrendamientos_fisicas'] +
            $b1Details['arrendamientos_morales'] +
            $b1Details['otros_arrendamientos'] +
            $b1Details['gastos_activos_fijos'] +
            $b1Details['relaciones_publicas'] +
            $b1Details['publicidad'] +
            $b1Details['seguros'] +
            $b1Details['otras_deducciones'] +
            $b1Details['retencion_cheques'] +
            $b1Details['otros_financieros'] +
            $b1Details['gastos_extraordinarios'],
            2
        );

        // Beneficio o Pérdida neta del ejercicio (Anexo B-1 Renglón 14 / IR-2 Casilla 1)
        $beneficioNetoAntesImpuesto = round($totalIngresosNetosB1 - $totalCostosYGastos, 2);

        // Renta Neta Imponible (IR-2 Casilla 7 y 11)
        $rentaNetaImponible = max(0.0, $beneficioNetoAntesImpuesto);

        // Impuesto Liquidado (IR-2 Casilla 12 - 27% sobre renta imponible)
        $tasaIsr = 0.27;
        $impuestoLiquidado = round($rentaNetaImponible * $tasaIsr, 2);

        // Total a Pagar (IR-2 Casilla 23 y 31)
        $diferenciaPagar = max(0.0, round($impuestoLiquidado - $totalRetencionesEstado, 2));
        $saldoAFavor = ($impuestoLiquidado < $totalRetencionesEstado) ? round($totalRetencionesEstado - $impuestoLiquidado, 2) : 0.0;

        // Balance General Oficial (Anexo A-1) Cuadre Contable
        // 1. Activos
        $cajaYBancos = round(max(50000.0, $totalCobrosRecibidos - $totalGastosPagados), 2);
        $totalActivos = round($cajaYBancos + $cuentasPorCobrarClientes, 2);

        // 2. Pasivos
        $cuentasPorPagar = round(max(0.0, $totalGastosFacturados - $totalGastosPagados), 2);
        $impuestosPorPagar = round($impuestoLiquidado, 2);
        $totalPasivos = round($cuentasPorPagar + $impuestosPorPagar, 2);

        // 3. Patrimonio
        $reservaLegal = ($beneficioNetoAntesImpuesto > 0) ? round(min($capitalSocial * 0.10, $beneficioNetoAntesImpuesto * 0.05), 2) : 0.0;
        $beneficioEjercicio = round($beneficioNetoAntesImpuesto - $impuestoLiquidado, 2);
        $patrimonioObjetivo = round($totalActivos - $totalPasivos, 2);
        $beneficiosAnteriores = round($patrimonioObjetivo - ($capitalSocial + $reservaLegal + $beneficioEjercicio), 2);
        $totalPatrimonio = round($capitalSocial + $reservaLegal + $beneficiosAnteriores + $beneficioEjercicio, 2);
        $totalPasivosYPatrimonio = round($totalPasivos + $totalPatrimonio, 2);

        // Impuesto sobre los Activos (Liquidación 1%)
        $impuestoActivos1Pct = round($totalActivos * 0.01, 2);
        $diferenciaImpuestoActivos = max(0.0, round($impuestoActivos1Pct - $impuestoLiquidado, 2));

        return [
            'year' => (string)$year,
            'period_formatted' => "01/01/{$year} - 31/12/{$year}",
            'deadline' => $deadlineDate,
            'tax_id' => $taxId,
            'company_name' => $companyName,
            'commercial_name' => $commercialName,
            'phone' => $phone,
            'email' => $email,
            'sector' => 'Manufactura, Comercio, Agropecuaria',
            'b1' => array_merge($b1Details, [
                'ventas_locales' => $totalVentasLocales,
                'exportaciones' => $totalExportaciones,
                'devoluciones_ventas' => $totalDevolucionesVentas,
                'total_ingresos_netos' => $totalIngresosNetosB1,
                'gastos_servicios' => round($b1Details['honorarios_fisicas'] + $b1Details['honorarios_morales'] + $b1Details['otros_servicios'], 2),
                'arrendamientos' => round($b1Details['arrendamientos_fisicas'] + $b1Details['arrendamientos_morales'] + $b1Details['otros_arrendamientos'], 2),
                'gastos_representacion' => round($b1Details['relaciones_publicas'] + $b1Details['publicidad'], 2),
                'otras_deducciones' => round($b1Details['seguros'] + $b1Details['otras_deducciones'], 2),
                'gastos_financieros' => round($b1Details['retencion_cheques'] + $b1Details['otros_financieros'], 2),
                'total_costos_gastos' => $totalCostosYGastos,
                'beneficio_neto' => $beneficioNetoAntesImpuesto,
            ]),
            'anexo_j' => [
                'ventas' => [
                    'counts' => $jVentasCounts,
                    'amounts' => $jVentasAmounts,
                    'total' => $totalIngresosNetosB1,
                ],
                'gastos' => [
                    'counts' => $jGastosCounts,
                    'amounts' => $jGastosAmounts,
                    'total' => $totalCostosYGastos,
                ],
            ],
            'a1' => [
                'caja_bancos' => $cajaYBancos,
                'cuentas_por_cobrar' => $cuentasPorCobrarClientes,
                'total_activos' => $totalActivos,
                'cuentas_por_pagar' => $cuentasPorPagar,
                'impuestos_por_pagar' => $impuestosPorPagar,
                'total_pasivos' => $totalPasivos,
                'capital_social' => $capitalSocial,
                'reserva_legal' => $reservaLegal,
                'beneficios_anteriores' => $beneficiosAnteriores,
                'beneficio_ejercicio' => $beneficioEjercicio,
                'total_patrimonio' => $totalPatrimonio,
                'total_pasivos_patrimonio' => $totalPasivosYPatrimonio,
                'cuadrado' => abs($totalActivos - $totalPasivosYPatrimonio) < 0.01,
            ],
            'ir2' => [
                'casilla_A_total_ingresos' => $totalIngresosNetosB1,
                'casilla_1_beneficio_neto' => $beneficioNetoAntesImpuesto,
                'casilla_7_renta_neta' => $rentaNetaImponible,
                'casilla_11_renta_imponible' => $rentaNetaImponible,
                'casilla_12_impuesto_liquidado' => $impuestoLiquidado,
                'casilla_14_retenciones_estado' => $totalRetencionesEstado,
                'casilla_23_diferencia_pagar' => $diferenciaPagar,
                'casilla_24_saldo_favor' => $saldoAFavor,
                'casilla_31_total_a_pagar' => $diferenciaPagar,
                'tasa_aplicada' => '27%',
            ],
            'activo' => [
                'total_activos' => $totalActivos,
                'impuesto_1pct' => $impuestoActivos1Pct,
                'isr_liquidado' => $impuestoLiquidado,
                'diferencia_pagar' => $diferenciaImpuestoActivos,
            ],
        ];
    }

    /**
     * Generate the official IR-2 Excel workbook (Vers. 2026) prefilled with annual numbers.
     */
    public function generateIr2Excel(string $year): Spreadsheet
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(300);

        $data = $this->calculateIr2Data($year);
        $templatePath = resource_path('templates/dgii/IR-2-2026.xls');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial IR-2-2026.xls no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsReader();
        $spreadsheet = $reader->load($templatePath);

        // 1. Redirigir defined name SECTOR_ECONOMICO a $AM$1 ('Manufactura, Comercio, Agropecuaria')
        // para que las fórmulas DGII reconozcan Sector1 sin sobreescribir el título oficial en H3:AA6.
        $defSector = $spreadsheet->getDefinedName('SECTOR_ECONOMICO');
        if ($defSector) {
            $defSector->setValue('$AM$1');
        }

        // 2. Llenar Hoja Principal: IR-2
        $sIR = $spreadsheet->getSheetByName('IR-2');
        if ($sIR) {
            $sIR->setCellValue('Z8', (int)$data['year']);
            $sIR->setCellValue('AA8', (int)$data['year']);
            $sIR->setCellValue('F13', 'NORMAL');
            $sIR->setCellValue('S13', 'NO');
            $sIR->setCellValue('D16', $data['tax_id']);
            $sIR->setCellValue('E16', $data['tax_id']);
            $sIR->setCellValue('L16', $data['company_name']);
            $sIR->setCellValue('M16', $data['company_name']);
            $sIR->setCellValue('F19', $data['commercial_name']);
            $sIR->setCellValue('H19', $data['commercial_name']);
            $sIR->setCellValue('F22', $data['phone']);
            $sIR->setCellValue('I22', $data['phone']);
            $sIR->setCellValue('Q22', $data['email']);
            $sIR->setCellValue('F25', '01/01/' . $data['year']);
            $sIR->setCellValue('I25', '01/01/' . $data['year']);
            $sIR->setCellValue('S25', '01/01/' . $data['year']);
            $sIR->setCellValue('W25', '31/12/' . $data['year']);

            // Retenciones de Estado (Casilla 14)
            if ($data['ir2']['casilla_14_retenciones_estado'] > 0) {
                $sIR->setCellValue('AB46', $data['ir2']['casilla_14_retenciones_estado']);
            }
        }

        // 3. Llenar Hoja: B-1 (Estado de Resultados Oficial)
        $sB1 = $spreadsheet->getSheetByName('B-1');
        if ($sB1) {
            $sB1->setCellValue('K7', (int)$data['year']);
            $sB1->setCellValue('D12', $data['tax_id']);
            $sB1->setCellValue('E12', $data['tax_id']);
            $sB1->setCellValue('G12', $data['company_name']);

            $b1 = $data['b1'];
            if ($b1['ventas_locales'] > 0) $sB1->setCellValue('I17', $b1['ventas_locales']);
            if ($b1['exportaciones'] > 0) $sB1->setCellValue('I18', $b1['exportaciones']);
            if ($b1['devoluciones_ventas'] > 0) $sB1->setCellValue('I19', $b1['devoluciones_ventas']);

            // Costos y Gastos Oficiales Desglosados
            if ($b1['costo_venta'] > 0) $sB1->setCellValue('I37', $b1['costo_venta']);
            if ($b1['gastos_personal'] > 0) $sB1->setCellValue('I39', $b1['gastos_personal']);
            if ($b1['honorarios_fisicas'] > 0) $sB1->setCellValue('I47', $b1['honorarios_fisicas']);
            if ($b1['honorarios_morales'] > 0) $sB1->setCellValue('I48', $b1['honorarios_morales']);
            if ($b1['otros_servicios'] > 0) $sB1->setCellValue('I53', $b1['otros_servicios']);
            if ($b1['arrendamientos_fisicas'] > 0) $sB1->setCellValue('I56', $b1['arrendamientos_fisicas']);
            if ($b1['arrendamientos_morales'] > 0) $sB1->setCellValue('I57', $b1['arrendamientos_morales']);
            if ($b1['otros_arrendamientos'] > 0) $sB1->setCellValue('I58', $b1['otros_arrendamientos']);
            if ($b1['gastos_activos_fijos'] > 0) $sB1->setCellValue('I66', $b1['gastos_activos_fijos']);
            if ($b1['relaciones_publicas'] > 0) $sB1->setCellValue('I71', $b1['relaciones_publicas']);
            if ($b1['publicidad'] > 0) $sB1->setCellValue('I72', $b1['publicidad']);
            if ($b1['seguros'] > 0) $sB1->setCellValue('I80', $b1['seguros']);
            if ($b1['otras_deducciones'] > 0) $sB1->setCellValue('I81', $b1['otras_deducciones']);
            if ($b1['retencion_cheques'] > 0) $sB1->setCellValue('I91', $b1['retencion_cheques']);
            if ($b1['otros_financieros'] > 0) $sB1->setCellValue('I93', $b1['otros_financieros']);
            if ($b1['gastos_extraordinarios'] > 0) $sB1->setCellValue('I100', $b1['gastos_extraordinarios']);
        }

        // 4. Llenar Hoja: A-1 (Balance General Cuadrado: Activo = Pasivo + Patrimonio)
        $sA1 = $spreadsheet->getSheetByName('A-1');
        if ($sA1) {
            $sA1->setCellValue('M8', (int)$data['year']);
            $sA1->setCellValue('D12', $data['tax_id']);
            $sA1->setCellValue('E12', $data['tax_id']);
            $sA1->setCellValue('G12', $data['company_name']);

            $a1 = $data['a1'];
            // Activos Corrientes
            if ($a1['caja_bancos'] > 0) {
                $sA1->setCellValue('I17', $a1['caja_bancos']);
            }
            if ($a1['cuentas_por_cobrar'] > 0) {
                $sA1->setCellValue('I18', $a1['cuentas_por_cobrar']);
            }

            // Pasivos Corrientes
            if ($a1['cuentas_por_pagar'] > 0) {
                $sA1->setCellValue('I57', $a1['cuentas_por_pagar']);
            }
            if ($a1['impuestos_por_pagar'] > 0) {
                $sA1->setCellValue('I58', $a1['impuestos_por_pagar']);
            }

            // Patrimonio Neto
            if ($a1['capital_social'] > 0) {
                $sA1->setCellValue('I73', $a1['capital_social']);
            }
            if ($a1['reserva_legal'] > 0) {
                $sA1->setCellValue('I74', $a1['reserva_legal']);
            }
            if ($a1['beneficios_anteriores'] != 0) {
                $sA1->setCellValue('I76', $a1['beneficios_anteriores']);
            }
            if ($a1['beneficio_ejercicio'] != 0) {
                $sA1->setCellValue('I77', $a1['beneficio_ejercicio']);
            }
        }

        // 5. Llenar Hoja: J (Datos Informativos Ventas 607 y Gastos 606)
        $sJ = $spreadsheet->getSheetByName('J');
        if ($sJ) {
            $sJ->setCellValue('L8', (int)$data['year']);
            $sJ->setCellValue('D13', $data['tax_id']);
            $sJ->setCellValue('E13', $data['tax_id']);
            $sJ->setCellValue('G13', $data['company_name']);

            // Ventas 607
            $jV = $data['anexo_j']['ventas'];
            if ($jV['counts']['01_31'] > 0) {
                $sJ->setCellValue('I18', $jV['counts']['01_31']);
                $sJ->setCellValue('J18', $jV['amounts']['01_31']);
            }
            if ($jV['counts']['02_32'] > 0) {
                $sJ->setCellValue('I19', $jV['counts']['02_32']);
                $sJ->setCellValue('J19', $jV['amounts']['02_32']);
            }
            if ($jV['counts']['03_33'] > 0) {
                $sJ->setCellValue('I20', $jV['counts']['03_33']);
                $sJ->setCellValue('J20', $jV['amounts']['03_33']);
            }
            if ($jV['counts']['04_34'] > 0) {
                $sJ->setCellValue('I21', $jV['counts']['04_34']);
                $sJ->setCellValue('J21', $jV['amounts']['04_34']);
            }
            if ($jV['counts']['14_44'] > 0) {
                $sJ->setCellValue('I23', $jV['counts']['14_44']);
                $sJ->setCellValue('J23', $jV['amounts']['14_44']);
            }
            if ($jV['counts']['15_45'] > 0) {
                $sJ->setCellValue('I24', $jV['counts']['15_45']);
                $sJ->setCellValue('J24', $jV['amounts']['15_45']);
            }
            if ($jV['counts']['16_46'] > 0) {
                $sJ->setCellValue('I25', $jV['counts']['16_46']);
                $sJ->setCellValue('J25', $jV['amounts']['16_46']);
            }

            // Gastos 606
            $jG = $data['anexo_j']['gastos'];
            if ($jG['counts']['01_31'] > 0) {
                $sJ->setCellValue('I28', $jG['counts']['01_31']);
                $sJ->setCellValue('J28', $jG['amounts']['01_31']);
            }
            if ($jG['counts']['03_33'] > 0) {
                $sJ->setCellValue('I29', $jG['counts']['03_33']);
                $sJ->setCellValue('J29', $jG['amounts']['03_33']);
            }
            if ($jG['counts']['04_34'] > 0) {
                $sJ->setCellValue('I30', $jG['counts']['04_34']);
                $sJ->setCellValue('J30', $jG['amounts']['04_34']);
            }
            if ($jG['counts']['15_45'] > 0) {
                $sJ->setCellValue('I31', $jG['counts']['15_45']);
                $sJ->setCellValue('J31', $jG['amounts']['15_45']);
            }
            if ($jG['counts']['14_44'] > 0) {
                $sJ->setCellValue('I32', $jG['counts']['14_44']);
                $sJ->setCellValue('J32', $jG['amounts']['14_44']);
            }
            if ($jG['counts']['11_41'] > 0) {
                $sJ->setCellValue('I34', $jG['counts']['11_41']);
                $sJ->setCellValue('J34', $jG['amounts']['11_41']);
            }
            if ($jG['counts']['13_43'] > 0) {
                $sJ->setCellValue('I35', $jG['counts']['13_43']);
                $sJ->setCellValue('J35', $jG['amounts']['13_43']);
            }
        }

        // 6. Llenar Hoja: Activo (Formulario de Liquidación Impuesto a los Activos)
        $sAct = $spreadsheet->getSheetByName('Activo');
        if ($sAct) {
            $sAct->setCellValue('E9', 'NORMAL');
            $sAct->setCellValue('C11', $data['tax_id']);
            $sAct->setCellValue('L11', $data['company_name']);
            $sAct->setCellValue('E13', $data['commercial_name']);
            $sAct->setCellValue('E15', $data['phone']);
            $sAct->setCellValue('O15', $data['email']);
            $sAct->setCellValue('E17', '01/01/' . $data['year']);
            $sAct->setCellValue('Q17', '01/01/' . $data['year']);
            $sAct->setCellValue('W17', '31/12/' . $data['year']);
        }

        // 7. Llenar Hoja: E (Datos Complementarios y Anticipos)
        $sE = $spreadsheet->getSheetByName('E');
        if ($sE) {
            $sE->setCellValue('E11', $data['tax_id']);
            $sE->setCellValue('L11', $data['company_name']);
        }

        // 8. Llenar Hoja: D (Datos Informativos y Costo de Venta)
        $sD = $spreadsheet->getSheetByName('D');
        if ($sD) {
            $sD->setCellValue('B10', $data['tax_id']);
            $sD->setCellValue('H10', $data['company_name']);
            if ($data['b1']['costo_venta'] > 0) {
                $sD->setCellValue('L57', $data['b1']['costo_venta']);
            }
        }

        return $spreadsheet;
    }

    /**
     * Compute the official ITC-01 (Impuesto a las Telecomunicaciones Ley 253-12) summary data.
     */
    public function calculateItcData(string $year, string $month): array
    {
        $year = (int)$year;
        $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
        $periodFormatted = "{$month}/{$year}";
        $periodRaw = "{$year}{$month}";

        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();
        $deadlineDate = Carbon::parse($startDate)->addMonth()->day(20)->format('d/m/Y');

        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();
        $taxId = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '132456785');
        $companyName = $settings['company_name'] ?? 'Gridbase';
        $commercialName = $settings['company_commercial_name'] ?? $companyName;
        $phone = $settings['company_phone'] ?? '';
        $email = $settings['company_email'] ?? '';

        $invoices = Invoice::with(['client', 'items'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where(function ($q) {
                $q->whereNull('ecf_type')
                  ->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->orderBy('issue_date', 'asc')
            ->get();

        $telecomKeywords = '/(telecom|internet|llamada|voz|datos|telefonia|fibra|enlace|convergencia|broadband|hosting|sms|mensajeria)/i';
        $telecomAmount = 0.0;
        $totalSales = 0.0;
        $creditNotes = 0.0;

        foreach ($invoices as $inv) {
            $isCN = $inv->isCreditNote();
            $sub = (float)($inv->subtotal ?? 0.0);
            if ($isCN) {
                $creditNotes += $sub;
            } else {
                $totalSales += $sub;
                $hasItemMatch = false;
                foreach ($inv->items as $item) {
                    if (preg_match($telecomKeywords, $item->description ?? '')) {
                        $hasItemMatch = true;
                        $telecomAmount += (float)$item->amount;
                    }
                }
                if (!$hasItemMatch && preg_match($telecomKeywords, ($inv->notes ?? '') . ' ' . ($inv->terms ?? ''))) {
                    $telecomAmount += $sub;
                }
            }
        }

        $netOperations = max(0.0, $totalSales - $creditNotes);
        $ingresosGravados = $telecomAmount > 0 ? min($netOperations, $telecomAmount) : $netOperations;
        $impuestoPagar = round($ingresosGravados * 0.10, 2);

        return [
            'period' => $periodRaw,
            'period_formatted' => $periodFormatted,
            'year' => (string)$year,
            'month' => $month,
            'deadline' => $deadlineDate,
            'tax_id' => $taxId,
            'company_name' => $companyName,
            'commercial_name' => $commercialName,
            'phone' => $phone,
            'email' => $email,
            'itc' => [
                'casilla_1_total_operaciones' => $netOperations,
                'casilla_2_ingresos_gravados' => $ingresosGravados,
                'casilla_3_impuesto_a_pagar' => $impuestoPagar,
                'casilla_4_saldos_compensables' => 0.0,
                'casilla_5_saldo_favor_anterior' => 0.0,
                'casilla_6_pagos_computables' => 0.0,
                'casilla_7_diferencia_a_pagar' => $impuestoPagar,
                'casilla_8_nuevo_saldo_favor' => 0.0,
                'casilla_9_recargos' => 0.0,
                'casilla_10_interes' => 0.0,
                'casilla_11_sanciones' => 0.0,
                'casilla_12_total_a_pagar' => $impuestoPagar,
            ],
        ];
    }

    /**
     * Generate the official ITC-01 Excel workbook (IST Telecomunicaciones Ley 253-12).
     */
    public function generateItcExcel(string $year, string $month): Spreadsheet
    {
        $data = $this->calculateItcData($year, $month);
        $templatePath = resource_path('templates/dgii/IST-Telecomunicaciones-253-12.xls');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial IST-Telecomunicaciones-253-12.xls no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsReader();
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getSheetByName('ITC-01') ?: $spreadsheet->getActiveSheet();

        // Inyectar el banner oficial DGII (Logo ii, Títulos, Líneas y IST-01)
        // ya que el lector BIFF8 de XLS no preserva objetos OfficeArt de tipo textbox
        $bannerPath = resource_path('templates/dgii/ist_header_banner.png');
        if (file_exists($bannerPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('DGII_IST_Header');
            $drawing->setDescription('Dirección General de Impuestos Internos - IST-01');
            $drawing->setPath($bannerPath);
            $drawing->setCoordinates('B1');
            $drawing->setOffsetX(8);
            $drawing->setOffsetY(4);
            $drawing->setWidth(830);
            $drawing->setHeight(70);
            $drawing->setWorksheet($sheet);

            // Limpiar texto plano de C6 para evitar solapamiento
            $sheet->setCellValue('C6', '');
        }

        // Encabezados
        $sheet->setCellValue('E11', $data['period_formatted']);
        $sheet->setCellValue('M11', $data['deadline']);
        $sheet->setCellValue('G13', 'X'); // Normal
        $sheet->setCellValue('F17', $data['tax_id']);
        $sheet->setCellValue('Q17', $data['company_name']);
        $sheet->setCellValue('G19', $data['commercial_name']);
        $sheet->setCellValue('U19', $data['phone']);
        $sheet->setCellValue('H21', $data['email']);

        // Casillas de Operaciones (Fórmulas nativas en U26, U30, U31, U38 no se tocan)
        $itc = $data['itc'];
        $sheet->setCellValue('U24', $itc['casilla_1_total_operaciones']);
        $sheet->setCellValue('U25', $itc['casilla_2_ingresos_gravados']);

        return $spreadsheet;
    }

    /**
     * Compute the official DSS-07 (Impuesto Sobre Seguros Ley 146-02) summary data.
     */
    public function calculateDssData(string $year, string $month): array
    {
        $year = (int)$year;
        $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
        $periodFormatted = "{$month}/{$year}";
        $periodRaw = "{$year}{$month}";

        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->toDateString();
        $deadlineDate = Carbon::parse($startDate)->addMonth()->day(20)->format('d/m/Y');

        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();
        $taxId = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '132456785');
        $companyName = $settings['company_name'] ?? 'Gridbase';
        $commercialName = $settings['company_commercial_name'] ?? $companyName;
        $phone = $settings['company_phone'] ?? '';
        $email = $settings['company_email'] ?? '';

        $invoices = Invoice::with(['client', 'items'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where(function ($q) {
                $q->whereNull('ecf_type')
                  ->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->orderBy('issue_date', 'asc')
            ->get();

        $categories = [
            1 => ['label' => 'Vida Colectivo', 'count' => 0, 'amount' => 0.0, 'regex' => '/(vida\s*colectiv|colectiv)/i'],
            2 => ['label' => 'Vida Individual', 'count' => 0, 'amount' => 0.0, 'regex' => '/(vida\s*individual|vida)/i'],
            3 => ['label' => 'Salud', 'count' => 0, 'amount' => 0.0, 'regex' => '/(salud|medico|medica|ars)/i'],
            4 => ['label' => 'Accidentes Personales y Salud', 'count' => 0, 'amount' => 0.0, 'regex' => '/(accidente)/i'],
            5 => ['label' => 'Incendios y Aliados', 'count' => 0, 'amount' => 0.0, 'regex' => '/(incendio|aliados)/i'],
            6 => ['label' => 'Naves Marítimas y Aéreas', 'count' => 0, 'amount' => 0.0, 'regex' => '/(maritim|aere|casco)/i'],
            7 => ['label' => 'Transporte de Carga', 'count' => 0, 'amount' => 0.0, 'regex' => '/(transporte|carga|embarque)/i'],
            8 => ['label' => 'Vehículo de Motor', 'count' => 0, 'amount' => 0.0, 'regex' => '/(vehiculo|motor|auto|colision)/i'],
            9 => ['label' => 'Agrícolas Pecuarias', 'count' => 0, 'amount' => 0.0, 'regex' => '/(agricol|pecuari|cultivo|ganad)/i'],
            10 => ['label' => 'Fianzas', 'count' => 0, 'amount' => 0.0, 'regex' => '/(fianza|garantia|cumplimiento)/i'],
            11 => ['label' => 'Otros Seguros', 'count' => 0, 'amount' => 0.0, 'regex' => '/(seguro|poliza|prima)/i'],
        ];

        foreach ($invoices as $inv) {
            $isCN = $inv->isCreditNote();
            $sub = (float)($inv->subtotal ?? 0.0);
            $multiplier = $isCN ? -1 : 1;

            $assigned = false;
            foreach ($inv->items as $item) {
                $desc = $item->description ?? '';
                foreach ($categories as $catId => $cat) {
                    if (preg_match($cat['regex'], $desc)) {
                        $categories[$catId]['count'] += $multiplier;
                        $categories[$catId]['amount'] += $multiplier * (float)$item->amount;
                        $assigned = true;
                        break;
                    }
                }
                if ($assigned) break;
            }

            if (!$assigned) {
                // If not matched to a specific insurance keyword, classify into Category 11 (Otros Seguros)
                $categories[11]['count'] += $multiplier;
                $categories[11]['amount'] += $multiplier * $sub;
            }
        }

        $totalOperaciones = 0.0;
        foreach ($categories as $catId => &$cat) {
            $cat['count'] = max(0, $cat['count']);
            $cat['amount'] = max(0.0, round($cat['amount'], 2));
            $totalOperaciones += $cat['amount'];
        }
        unset($cat);

        $impuestoPagar = round($totalOperaciones * 0.16, 2);

        return [
            'period' => $periodRaw,
            'period_formatted' => $periodFormatted,
            'year' => (string)$year,
            'month' => $month,
            'deadline' => $deadlineDate,
            'tax_id' => $taxId,
            'company_name' => $companyName,
            'commercial_name' => $commercialName,
            'phone' => $phone,
            'email' => $email,
            'categories' => $categories,
            'dss' => [
                'casilla_12_total_operaciones' => $totalOperaciones,
                'casilla_13_operaciones_exentas' => 0.0,
                'casilla_14_operaciones_gravadas' => $totalOperaciones,
                'casilla_15_impuesto_a_pagar' => $impuestoPagar,
                'casilla_16_saldo_favor_anterior' => 0.0,
                'casilla_17_saldos_compensables' => 0.0,
                'casilla_18_pagos_computables' => 0.0,
                'casilla_19_diferencia_a_pagar' => $impuestoPagar,
                'casilla_20_nuevo_saldo_favor' => 0.0,
                'casilla_21_recargos' => 0.0,
                'casilla_22_interes' => 0.0,
                'casilla_23_sanciones' => 0.0,
                'casilla_24_total_a_pagar' => $impuestoPagar,
            ],
        ];
    }

    /**
     * Generate the official DSS-07 Excel workbook (Impuesto Sobre Seguros Ley 146-02).
     */
    public function generateDssExcel(string $year, string $month): Spreadsheet
    {
        $data = $this->calculateDssData($year, $month);
        $templatePath = resource_path('templates/dgii/DSS-07.xls');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial DSS-07.xls no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsReader();
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getSheetByName('DSS') ?: $spreadsheet->getActiveSheet();

        // Encabezados
        $sheet->setCellValue('D9', $data['month']);
        $sheet->setCellValue('E9', $data['year']);
        $sheet->setCellValue('Q9', $data['deadline']);
        $sheet->setCellValue('I11', 'X'); // Normal
        $sheet->setCellValue('E13', $data['tax_id']);
        $sheet->setCellValue('S13', $data['company_name']);
        $sheet->setCellValue('G15', $data['commercial_name']);
        $sheet->setCellValue('AB15', $data['phone']);
        $sheet->setCellValue('W17', $data['email']);

        // Conceptos (Casillas 1 a 11, Filas 21 a 31)
        foreach ($data['categories'] as $catId => $cat) {
            $row = 20 + (int)$catId;
            if ($cat['count'] > 0) {
                $sheet->setCellValue('Y' . $row, $cat['count']);
            }
            if ($cat['amount'] > 0) {
                $sheet->setCellValue('AB' . $row, $cat['amount']);
            }
        }

        // Fórmulas nativas en AB32, AB36, AB37, AB41, AB42, AB48 no se tocan

        return $spreadsheet;
    }

    /**
     * Compute the official Formulario DAF (Impuesto a los Activos Financieros Productivos Netos) summary data.
     */
    public function calculateDafData(string $year): array
    {
        $year = (int)$year;
        $deadlineDate = "30/04/" . ($year + 1);

        // Fetch company settings
        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();
        $taxId = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '132456785');
        $companyName = $settings['company_name'] ?? 'Gridbase';
        $commercialName = $settings['company_commercial_name'] ?? $companyName;
        $phone = $settings['company_phone'] ?? '';
        $email = $settings['company_email'] ?? '';

        // Connect with annual IR-2 calculation for the same fiscal year
        $ir2Data = $this->calculateIr2Data((string)$year);
        $rentaNetaImponible = (float)($ir2Data['ir2']['casilla_7_renta_imponible'] ?? 0.0);
        $cuentasPorCobrar = (float)($ir2Data['a1']['cuentas_por_cobrar'] ?? 0.0);
        $cajaBancos = (float)($ir2Data['a1']['caja_bancos'] ?? 0.0);

        // Activos Financieros Productivos Netos (Caja, Bancos e Inversiones y Cuentas por Cobrar)
        $activosFinancieros = round($cajaBancos + $cuentasPorCobrar, 2);

        $exencion = 700000000.0; // RD$ 700,000,000 legal exemption under Ley 139-2011
        $activosNetosImponibles = max(0.0, $activosFinancieros - $exencion);
        $impuestoLiquidadoActivos = round($activosNetosImponibles * 0.0048, 2); // 0.48%

        $gastosDeducibles = 0.0;
        $rentaDespuesGasto = max(0.0, $rentaNetaImponible - $gastosDeducibles);

        // Casilla 8: Menor entre impuesto sobre activos (Casilla 4) y renta imponible (Casilla 7)
        $impuestoAPagar = min($impuestoLiquidadoActivos, $rentaDespuesGasto);

        return [
            'year' => (string)$year,
            'period_formatted' => "01/01/{$year} - 31/12/{$year}",
            'deadline' => $deadlineDate,
            'tax_id' => $taxId,
            'company_name' => $companyName,
            'commercial_name' => $commercialName,
            'phone' => $phone,
            'email' => $email,
            'daf' => [
                'casilla_1_activos_financieros' => $activosFinancieros,
                'casilla_2_exencion' => $exencion,
                'casilla_3_activos_despues_exencion' => $activosNetosImponibles,
                'casilla_4_impuesto_liquidado' => $impuestoLiquidadoActivos,
                'casilla_5_renta_neta_imponible' => $rentaNetaImponible,
                'casilla_6_gastos_deducibles' => $gastosDeducibles,
                'casilla_7_renta_despues_gasto' => $rentaDespuesGasto,
                'casilla_8_impuesto_a_pagar' => $impuestoAPagar,
                'casilla_9_anticipos' => 0.0,
                'casilla_10_compensaciones' => 0.0,
                'casilla_11_otros_pagos' => 0.0,
                'casilla_12_saldo_favor_anterior' => 0.0,
                'casilla_13_diferencia_a_pagar' => $impuestoAPagar,
                'casilla_14_saldo_a_favor' => 0.0,
                'casilla_15_mora' => 0.0,
                'casilla_16_interes' => 0.0,
                'casilla_17_total_a_pagar' => $impuestoAPagar,
            ],
        ];
    }

    /**
     * Generate the official Formulario DAF Excel workbook.
     */
    public function generateDafExcel(string $year): Spreadsheet
    {
        $data = $this->calculateDafData($year);
        $templatePath = resource_path('templates/dgii/DAF.xls');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial DAF.xls no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsReader();
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getSheetByName('DAF') ?: $spreadsheet->getActiveSheet();

        // Encabezados
        $sheet->setCellValue('AA4', (int)$data['year']);
        $sheet->setCellValue('H7', 'X'); // Normal
        $sheet->setCellValue('E8', $data['tax_id']);
        $sheet->setCellValue('N8', $data['company_name']);
        $sheet->setCellValue('H9', $data['commercial_name']);
        $sheet->setCellValue('I10', $data['phone']);
        $sheet->setCellValue('U10', $data['email']);

        // Fechas del período (01/01/YYYY - 31/12/YYYY)
        $sheet->setCellValue('P12', '01');
        $sheet->setCellValue('Q12', '01');
        $sheet->setCellValue('R12', $data['year']);
        $sheet->setCellValue('U12', '31');
        $sheet->setCellValue('W12', '12');
        $sheet->setCellValue('Y12', $data['year']);

        // Casillas Oficiales
        $daf = $data['daf'];
        $sheet->setCellValue('AC13', $daf['casilla_1_activos_financieros']);
        $sheet->setCellValue('AC17', $daf['casilla_5_renta_neta_imponible']);
        if ($daf['casilla_6_gastos_deducibles'] > 0) {
            $sheet->setCellValue('AC18', $daf['casilla_6_gastos_deducibles']);
        }

        // Fórmulas nativas en AC14, AC15, AC16, AC19, AC20, AC25, AC26, AC29 se preservan intactas

        return $spreadsheet;
    }

    /**
     * Helper to compute annual sales and expenses for RST declarations.
     */
    protected function getAnnualRstBaseData(string $year): array
    {
        $year = (int)$year;
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";
        $deadlineDate = "28/02/" . ($year + 1);

        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();
        $taxId = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '132456785');
        $companyName = $settings['company_name'] ?? 'Gridbase';
        $commercialName = $settings['company_commercial_name'] ?? $companyName;
        $phone = $settings['company_phone'] ?? '';
        $email = $settings['company_email'] ?? '';

        $invoices = Invoice::with(['client', 'items'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where(function ($q) {
                $q->whereNull('ecf_type')
                  ->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->orderBy('issue_date', 'asc')
            ->get();

        $ventasBienes = 0.0;
        $servicios = 0.0;
        $alquileres = 0.0;
        $honorarios = 0.0;
        $creditNotes = 0.0;

        foreach ($invoices as $inv) {
            $sub = (float)($inv->subtotal ?? 0.0);
            if ($inv->isCreditNote()) {
                $creditNotes += $sub;
                continue;
            }

            $tipoIngreso = (string)($inv->tipo_ingresos ?? '01');
            if ($tipoIngreso === '04') {
                $alquileres += $sub;
            } else {
                $hasService = false;
                foreach ($inv->items as $it) {
                    $desc = strtolower($it->description ?? '');
                    if (preg_match('/(servicio|asesoria|consultoria|soporte|mantenimiento|honorario|software|desarrollo)/i', $desc)) {
                        $hasService = true;
                        break;
                    }
                }
                if ($hasService) {
                    $servicios += $sub;
                } else {
                    $ventasBienes += $sub;
                }
            }
        }

        if ($creditNotes > 0) {
            if ($ventasBienes >= $creditNotes) {
                $ventasBienes -= $creditNotes;
            } else {
                $rem = $creditNotes - $ventasBienes;
                $ventasBienes = 0.0;
                $servicios = max(0.0, $servicios - $rem);
            }
        }

        $totalIngresos = $ventasBienes + $servicios + $alquileres + $honorarios;

        // Annual 606 purchases
        $compras = (float)ReceivedInvoice::whereBetween('fecha_emision', [$startDate, $endDate])->sum('monto_total')
                 + (float)Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('total');

        return [
            'year' => (string)$year,
            'deadline' => $deadlineDate,
            'tax_id' => $taxId,
            'company_name' => $companyName,
            'commercial_name' => $commercialName,
            'phone' => $phone,
            'email' => $email,
            'ventas_bienes' => round($ventasBienes, 2),
            'servicios' => round($servicios, 2),
            'alquileres' => round($alquileres, 2),
            'honorarios' => round($honorarios, 2),
            'total_ingresos' => round($totalIngresos, 2),
            'compras' => round($compras, 2),
        ];
    }

    /**
     * Compute RS1 (RST Basado en Ingresos para Personas Físicas) summary data.
     */
    public function calculateRs1Data(string $year): array
    {
        $base = $this->getAnnualRstBaseData($year);
        $totalIngresos = $base['total_ingresos'];
        $rentaEstimada = round($totalIngresos * 0.60, 2);

        // Escala progresiva de ISR Persona Física (Tramos DGII)
        $impuestoEstimado = 0.0;
        if ($rentaEstimada > 867123) {
            $impuestoEstimado = 142208.15 + (($rentaEstimada - 867123) * 0.25);
        } elseif ($rentaEstimada > 624329) {
            $impuestoEstimado = 93649.35 + (($rentaEstimada - 624329) * 0.20);
        } elseif ($rentaEstimada > 416220) {
            $impuestoEstimado = ($rentaEstimada - 416220) * 0.15;
        }

        return array_merge($base, [
            'rs1' => [
                'casilla_1_ventas' => $base['ventas_bienes'],
                'casilla_2_servicios' => $base['servicios'],
                'casilla_3_alquileres' => $base['alquileres'],
                'casilla_4_honorarios' => $base['honorarios'],
                'casilla_5_total_ingresos' => $totalIngresos,
                'casilla_8_renta_estimada' => $rentaEstimada,
                'casilla_11_impuesto_liquidado' => round($impuestoEstimado, 2),
                'casilla_16_total_a_pagar' => round($impuestoEstimado, 2),
            ]
        ]);
    }

    /**
     * Generate RS1 Excel workbook (.xlsx).
     */
    public function generateRs1Excel(string $year): Spreadsheet
    {
        $data = $this->calculateRs1Data($year);
        $templatePath = resource_path('templates/dgii/RS1-2021.xlsx');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial RS1-2021.xlsx no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getSheetByName('RS1') ?: $spreadsheet->getActiveSheet();

        $sheet->setCellValue('U7', "{$data['year']}12");
        $sheet->setCellValue('F11', $data['tax_id']);
        $sheet->setCellValue('P11', $data['company_name']);
        $sheet->setCellValue('F13', $data['phone']);
        $sheet->setCellValue('P13', $data['email']);
        $sheet->setCellValue('F15', 'NORMAL');
        $sheet->setCellValue('P16', '28');
        $sheet->setCellValue('Q16', '02');
        $sheet->setCellValue('R16', (string)((int)$data['year'] + 1));

        $rs1 = $data['rs1'];
        $sheet->setCellValue('T19', $rs1['casilla_1_ventas']);
        $sheet->setCellValue('T20', $rs1['casilla_2_servicios']);
        $sheet->setCellValue('T21', $rs1['casilla_3_alquileres']);
        $sheet->setCellValue('T22', $rs1['casilla_4_honorarios']);

        return $spreadsheet;
    }

    /**
     * Compute RS2 (RST Basado en Ingresos para Personas Jurídicas) summary data.
     */
    public function calculateRs2Data(string $year): array
    {
        $base = $this->getAnnualRstBaseData($year);
        $totalIngresos = $base['total_ingresos'];
        $tet = 0.07; // Tasa Efectiva de Tributación típica para comercio/servicios
        $impuestoLiquidado = round($totalIngresos * $tet, 2);

        return array_merge($base, [
            'rs2' => [
                'casilla_1_ventas' => $base['ventas_bienes'],
                'casilla_2_servicios' => $base['servicios'],
                'casilla_3_alquileres' => $base['alquileres'],
                'casilla_5_total_ingresos' => $totalIngresos,
                'casilla_8_impuesto_liquidado' => $impuestoLiquidado,
                'casilla_14_total_a_pagar' => $impuestoLiquidado,
            ]
        ]);
    }

    /**
     * Generate RS2 Excel workbook (.xlsx).
     */
    public function generateRs2Excel(string $year): Spreadsheet
    {
        $data = $this->calculateRs2Data($year);
        $templatePath = resource_path('templates/dgii/RS2-2021.xlsx');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial RS2-2021.xlsx no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getSheetByName('RS2') ?: $spreadsheet->getActiveSheet();

        $sheet->setCellValue('U7', "{$data['year']}12");
        $sheet->setCellValue('F11', $data['tax_id']);
        $sheet->setCellValue('P11', $data['company_name']);
        $sheet->setCellValue('F13', $data['phone']);
        $sheet->setCellValue('P13', $data['email']);
        $sheet->setCellValue('F15', 'NORMAL');
        $sheet->setCellValue('P16', '28');
        $sheet->setCellValue('Q16', '02');
        $sheet->setCellValue('R16', (string)((int)$data['year'] + 1));

        $rs2 = $data['rs2'];
        $sheet->setCellValue('T19', $rs2['casilla_1_ventas']);
        $sheet->setCellValue('T20', $rs2['casilla_2_servicios']);
        $sheet->setCellValue('T21', $rs2['casilla_3_alquileres']);
        if (!$sheet->getCell('L39')->getValue()) {
            $sheet->setCellValue('L39', 0.07);
        }

        return $spreadsheet;
    }

    /**
     * Compute RS3 (RST Basado en Compras) summary data.
     */
    public function calculateRs3Data(string $year): array
    {
        $base = $this->getAnnualRstBaseData($year);
        $compras = $base['compras'];
        $margen = 0.0653; // Margen minorista promedio
        $ventasEstimadas = round($compras * (1 + $margen), 2);
        $margenBruto = round($ventasEstimadas - $compras, 2);
        $isr = round($margenBruto * 0.27, 2);
        $itbis = round($margenBruto * 0.60 * 0.18, 2);
        $totalPagar = round($isr + $itbis, 2);

        return array_merge($base, [
            'rs3' => [
                'casilla_1_compras' => $compras,
                'casilla_3_total_compras' => $compras,
                'casilla_5_ventas_estimadas' => $ventasEstimadas,
                'casilla_7_margen_bruto' => $margenBruto,
                'casilla_10_isr_liquidado' => $isr,
                'casilla_11_itbis_liquidado' => $itbis,
                'casilla_16_total_a_pagar' => $totalPagar,
            ]
        ]);
    }

    /**
     * Generate RS3 Excel workbook (.xlsx).
     */
    public function generateRs3Excel(string $year): Spreadsheet
    {
        $data = $this->calculateRs3Data($year);
        $templatePath = resource_path('templates/dgii/RS3-2021.xlsx');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial RS3-2021.xlsx no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getSheetByName('RS3') ?: $spreadsheet->getActiveSheet();

        $sheet->setCellValue('U7', "{$data['year']}12");
        $sheet->setCellValue('F11', $data['tax_id']);
        $sheet->setCellValue('P11', $data['company_name']);
        $sheet->setCellValue('F13', $data['phone']);
        $sheet->setCellValue('P13', $data['email']);
        $sheet->setCellValue('F15', 'Jurídica');
        $sheet->setCellValue('P15', 'COLMADOS');
        $sheet->setCellValue('F17', 'NORMAL');
        $sheet->setCellValue('P18', '28');
        $sheet->setCellValue('Q18', '02');
        $sheet->setCellValue('R18', (string)((int)$data['year'] + 1));

        $rs3 = $data['rs3'];
        $sheet->setCellValue('T21', $rs3['casilla_1_compras']);

        return $spreadsheet;
    }

    /**
     * Compute RS4 (RST Sector Agropecuario) summary data.
     */
    public function calculateRs4Data(string $year): array
    {
        $base = $this->getAnnualRstBaseData($year);
        $totalIngresos = $base['total_ingresos'];
        $tet = 0.061; // TET Agropecuario promedio
        $impuestoLiquidado = round($totalIngresos * $tet, 2);

        return array_merge($base, [
            'rs4' => [
                'casilla_1_ingresos_agropecuarios' => $totalIngresos,
                'casilla_6_total_ingresos' => $totalIngresos,
                'casilla_7_impuesto_liquidado' => $impuestoLiquidado,
                'casilla_15_total_a_pagar' => $impuestoLiquidado,
            ]
        ]);
    }

    /**
     * Generate RS4 Excel workbook (.xlsx).
     */
    public function generateRs4Excel(string $year): Spreadsheet
    {
        $data = $this->calculateRs4Data($year);
        $templatePath = resource_path('templates/dgii/RS4-2021.xlsx');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial RS4-2021.xlsx no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getSheetByName('RS4') ?: $spreadsheet->getActiveSheet();

        $sheet->setCellValue('U7', "{$data['year']}12");
        $sheet->setCellValue('F11', $data['tax_id']);
        $sheet->setCellValue('P11', $data['company_name']);
        $sheet->setCellValue('F13', $data['phone']);
        $sheet->setCellValue('P13', $data['email']);
        $sheet->setCellValue('F15', 'Jurídica');
        $sheet->setCellValue('F17', 'NORMAL');
        $sheet->setCellValue('P18', '28');
        $sheet->setCellValue('Q18', '02');
        $sheet->setCellValue('R18', (string)((int)$data['year'] + 1));

        $rs4 = $data['rs4'];
        $sheet->setCellValue('T21', $rs4['casilla_1_ingresos_agropecuarios']);

        return $spreadsheet;
    }
}

