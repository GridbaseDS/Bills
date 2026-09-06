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
}
