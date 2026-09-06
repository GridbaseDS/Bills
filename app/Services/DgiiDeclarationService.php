<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
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

            // Anexo J clasificación
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

        // 3. Compras y Gastos (Anexo B-1 rubros 5 al 13 y Anexo J gastos 606)
        $gastosRubros = [
            '01' => 0.0, // Personal (B-1!I39)
            '02' => 0.0, // Trabajos y Servicios (B-1!I47)
            '03' => 0.0, // Arrendamientos (B-1!I56)
            '04' => 0.0, // Gastos Activos Fijos (B-1!I66)
            '05' => 0.0, // Representación / Publicidad (B-1!I71)
            '06' => 0.0, // Otras Deducciones / Seguros (B-1!I80)
            '07' => 0.0, // Financieros (B-1!I90)
            '08' => 0.0, // Extraordinarios (B-1!I100)
            '09' => 0.0, // Costo de Venta (B-1!I37)
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

        // Process ReceivedInvoices
        foreach ($receivedInvoices as $ri) {
            $encfClean = strtoupper(trim($ri->encf ?? ''));
            if (!empty($encfClean)) $seenNcfs[$encfClean] = true;

            $montoNeto = (float)($ri->monto_subtotal ?? ($ri->monto_total - ($ri->monto_total / 1.18)));
            if ($montoNeto <= 0) $montoNeto = (float)$ri->monto_total;
            $totalGastosFacturados += (float)$ri->monto_total;
            $totalGastosPagados += (float)$ri->monto_total;

            $tipoGasto = str_pad($ri->tipo_bien_servicio ?? '02', 2, '0', STR_PAD_LEFT);
            if (isset($gastosRubros[$tipoGasto])) {
                $gastosRubros[$tipoGasto] += round($montoNeto, 2);
            } else {
                $gastosRubros['02'] += round($montoNeto, 2);
            }

            $prefix = substr($encfClean, 0, 3);
            if (in_array($prefix, ['B01', 'E31'])) {
                $jGastosCounts['01_31']++;
                $jGastosAmounts['01_31'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B03', 'E33'])) {
                $jGastosCounts['03_33']++;
                $jGastosAmounts['03_33'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B04', 'E34'])) {
                $jGastosCounts['04_34']++;
                $jGastosAmounts['04_34'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B15', 'E45'])) {
                $jGastosCounts['15_45']++;
                $jGastosAmounts['15_45'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B14', 'E44'])) {
                $jGastosCounts['14_44']++;
                $jGastosAmounts['14_44'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B11', 'E41'])) {
                $jGastosCounts['11_41']++;
                $jGastosAmounts['11_41'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B13', 'E43'])) {
                $jGastosCounts['13_43']++;
                $jGastosAmounts['13_43'] += round($montoNeto, 2);
            } else {
                $jGastosCounts['01_31']++;
                $jGastosAmounts['01_31'] += round($montoNeto, 2);
            }
        }

        // Process Expenses
        foreach ($expenses as $exp) {
            $ncfClean = strtoupper(trim($exp->ncf ?? ''));
            if (!empty($ncfClean) && isset($seenNcfs[$ncfClean])) continue;
            if (!empty($ncfClean)) $seenNcfs[$ncfClean] = true;

            $montoNeto = (float)($exp->amount ?? 0.0);
            $totalGastosFacturados += ($montoNeto + (float)($exp->tax_amount ?? 0.0));
            $totalGastosPagados += ($montoNeto + (float)($exp->tax_amount ?? 0.0));

            $tipoGasto = str_pad($exp->expense_type ?? '02', 2, '0', STR_PAD_LEFT);
            if (isset($gastosRubros[$tipoGasto])) {
                $gastosRubros[$tipoGasto] += round($montoNeto, 2);
            } else {
                $gastosRubros['02'] += round($montoNeto, 2);
            }

            $prefix = substr($ncfClean, 0, 3);
            if (in_array($prefix, ['B01', 'E31'])) {
                $jGastosCounts['01_31']++;
                $jGastosAmounts['01_31'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B03', 'E33'])) {
                $jGastosCounts['03_33']++;
                $jGastosAmounts['03_33'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B04', 'E34'])) {
                $jGastosCounts['04_34']++;
                $jGastosAmounts['04_34'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B15', 'E45'])) {
                $jGastosCounts['15_45']++;
                $jGastosAmounts['15_45'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B14', 'E44'])) {
                $jGastosCounts['14_44']++;
                $jGastosAmounts['14_44'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B11', 'E41'])) {
                $jGastosCounts['11_41']++;
                $jGastosAmounts['11_41'] += round($montoNeto, 2);
            } elseif (in_array($prefix, ['B13', 'E43'])) {
                $jGastosCounts['13_43']++;
                $jGastosAmounts['13_43'] += round($montoNeto, 2);
            } else {
                $jGastosCounts['01_31']++;
                $jGastosAmounts['01_31'] += round($montoNeto, 2);
            }
        }

        // Total Costos y Gastos Operativos
        $totalCostosYGastos = array_sum($gastosRubros);

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

        // Cajas y Bancos estimado
        $cajaYBancos = max(0.0, round($totalCobrosRecibidos - $totalGastosPagados, 2));

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
            'b1' => [
                'ventas_locales' => $totalVentasLocales,
                'exportaciones' => $totalExportaciones,
                'devoluciones_ventas' => $totalDevolucionesVentas,
                'total_ingresos_netos' => $totalIngresosNetosB1,
                'costo_venta' => $gastosRubros['09'],
                'gastos_personal' => $gastosRubros['01'],
                'gastos_servicios' => $gastosRubros['02'],
                'arrendamientos' => $gastosRubros['03'],
                'gastos_activos_fijos' => $gastosRubros['04'],
                'gastos_representacion' => $gastosRubros['05'],
                'otras_deducciones' => $gastosRubros['06'],
                'gastos_financieros' => $gastosRubros['07'],
                'gastos_extraordinarios' => $gastosRubros['08'],
                'total_costos_gastos' => $totalCostosYGastos,
                'beneficio_neto' => $beneficioNetoAntesImpuesto,
            ],
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
        ];
    }

    /**
     * Generate the official IR-2 Excel workbook (Vers. 2026) prefilled with annual numbers.
     */
    public function generateIr2Excel(string $year): Spreadsheet
    {
        ini_set('memory_limit', '1536M');
        set_time_limit(300);

        $data = $this->calculateIr2Data($year);
        $templatePath = resource_path('templates/dgii/IR-2-2026.xls');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("La plantilla oficial IR-2-2026.xls no fue encontrada en: {$templatePath}");
        }

        $reader = new XlsReader();
        $spreadsheet = $reader->load($templatePath);

        // 1. Llenar Hoja: IR-2
        $sIR = $spreadsheet->getSheetByName('IR-2');
        if ($sIR) {
            // H3: Establecer el sector para que las fórmulas DGII activen Sector1
            $sIR->setCellValue('H3', 'Manufactura, Comercio, Agropecuaria');
            $sIR->setCellValue('AA8', (int)$data['year']);
            $sIR->setCellValue('F13', 'NORMAL');
            $sIR->setCellValue('D16', $data['tax_id']);
            $sIR->setCellValue('L16', $data['company_name']);
            $sIR->setCellValue('F19', $data['commercial_name']);
            $sIR->setCellValue('F22', $data['phone']);
            $sIR->setCellValue('Q22', $data['email']);
            $sIR->setCellValue('S25', '01/01/' . $data['year']);
            $sIR->setCellValue('W25', '31/12/' . $data['year']);

            // Retenciones de Estado (Casilla 14)
            if ($data['ir2']['casilla_14_retenciones_estado'] > 0) {
                $sIR->setCellValue('AB46', $data['ir2']['casilla_14_retenciones_estado']);
            }
        }

        // 2. Llenar Hoja: B-1 (Estado de Resultados)
        $sB1 = $spreadsheet->getSheetByName('B-1');
        if ($sB1) {
            $sB1->setCellValue('K7', (int)$data['year']);
            $sB1->setCellValue('D12', $data['tax_id']);
            $sB1->setCellValue('G12', $data['company_name']);

            $b1 = $data['b1'];
            if ($b1['ventas_locales'] > 0) $sB1->setCellValue('I17', $b1['ventas_locales']);
            if ($b1['exportaciones'] > 0) $sB1->setCellValue('I18', $b1['exportaciones']);
            if ($b1['devoluciones_ventas'] > 0) $sB1->setCellValue('I19', $b1['devoluciones_ventas']);

            // Costos y Gastos
            if ($b1['costo_venta'] > 0) $sB1->setCellValue('I37', $b1['costo_venta']);
            if ($b1['gastos_personal'] > 0) $sB1->setCellValue('I39', $b1['gastos_personal']);
            if ($b1['gastos_servicios'] > 0) $sB1->setCellValue('I47', $b1['gastos_servicios']);
            if ($b1['arrendamientos'] > 0) $sB1->setCellValue('I56', $b1['arrendamientos']);
            if ($b1['gastos_activos_fijos'] > 0) $sB1->setCellValue('I66', $b1['gastos_activos_fijos']);
            if ($b1['gastos_representacion'] > 0) $sB1->setCellValue('I71', $b1['gastos_representacion']);
            if ($b1['otras_deducciones'] > 0) $sB1->setCellValue('I80', $b1['otras_deducciones']);
            if ($b1['gastos_financieros'] > 0) $sB1->setCellValue('I90', $b1['gastos_financieros']);
            if ($b1['gastos_extraordinarios'] > 0) $sB1->setCellValue('I100', $b1['gastos_extraordinarios']);
        }

        // 3. Llenar Hoja: J (Datos Informativos Ventas y Gastos)
        $sJ = $spreadsheet->getSheetByName('J');
        if ($sJ) {
            $sJ->setCellValue('L8', (int)$data['year']);
            $sJ->setCellValue('D13', $data['tax_id']);
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

        // 4. Llenar Hoja: A-1 (Balance General)
        $sA1 = $spreadsheet->getSheetByName('A-1');
        if ($sA1) {
            $sA1->setCellValue('M8', (int)$data['year']);
            $sA1->setCellValue('D12', $data['tax_id']);
            $sA1->setCellValue('G12', $data['company_name']);

            $a1 = $data['a1'];
            if ($a1['caja_bancos'] > 0) {
                $sA1->setCellValue('I17', $a1['caja_bancos']);
            }
            if ($a1['cuentas_por_cobrar'] > 0) {
                $sA1->setCellValue('I18', $a1['cuentas_por_cobrar']);
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
}

