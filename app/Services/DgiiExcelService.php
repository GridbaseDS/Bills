<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class DgiiExcelService
{
    const DGII_GREEN = '008000';
    const DGII_LIGHT_GREEN = 'E2EFDA';
    const DGII_GRAY = 'F2F2F2';
    const TEXT_WHITE = 'FFFFFF';
    const BORDER_GRAY = 'D9D9D9';

    /**
     * Catalog of DGII 608 Anulation Types
     */
    const ANULATION_TYPES = [
        '01' => 'Deterioro de Factura Pre-Impresa',
        '02' => 'Errores de Impresión (Factura Pre-Impresa)',
        '03' => 'Impresión Defectuosa',
        '04' => 'Duplicidad de Factura',
        '05' => 'Corrección de la Información',
        '06' => 'Cambio de Productos',
        '07' => 'Devolución de Productos',
        '08' => 'Omisión de Productos',
        '09' => 'Errores en Secuencias de NCF',
    ];

    /**
     * Generate Formato 606 (Compras y Gastos de Bienes y Servicios)
     */
    public function generate606Excel(string $companyTaxId, string $period, array $records): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('606');
        $sheet->setShowGridLines(true);

        // Header Title Block
        $sheet->setCellValue('B1', 'Dirección General de Impuestos Internos');
        $sheet->setCellValue('H1', 'Herramienta de Distribución Gratuita');
        $sheet->setCellValue('B2', 'Formato de Envío de Compras de Bienes y Servicios (Formato 606)');
        $sheet->setCellValue('H2', 'Norma General 07-2018');
        $sheet->setCellValue('B3', 'Bills - Sistema de Facturación Electrónica');

        $sheet->getStyle('B1')->getFont()->setName('Tahoma')->setSize(13)->setBold(true);
        $sheet->getStyle('H1')->getFont()->setName('Tahoma')->setSize(9)->setItalic(true);
        $sheet->getStyle('B2')->getFont()->setName('Tahoma')->setSize(11)->setBold(true);
        $sheet->getStyle('H2')->getFont()->setName('Tahoma')->setSize(9);
        $sheet->getStyle('B3')->getFont()->setName('Tahoma')->setSize(9)->getColor()->setRGB('666666');

        // Metadata Block
        $metadata = [
            4 => ['A' => 'RNC o Cédula:', 'B' => $companyTaxId, 'type' => 'string'],
            5 => ['A' => 'Período:', 'B' => $period, 'type' => 'string'],
            6 => ['A' => 'Cantidad Registros:', 'B' => count($records), 'type' => 'number'],
        ];

        foreach ($metadata as $row => $meta) {
            $sheet->setCellValue("A{$row}", $meta['A']);
            $sheet->getStyle("A{$row}")->getFont()->setName('Tahoma')->setSize(9)->setBold(true);
            
            if ($meta['type'] === 'string') {
                $sheet->setCellValueExplicit("B{$row}", $meta['B'], DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue("B{$row}", $meta['B']);
            }
            $sheet->getStyle("B{$row}")->getFont()->setName('Tahoma')->setSize(9);
        }

        // Section Title
        $sheet->setCellValue('B9', 'Detalle de Compras y Gastos');
        $sheet->getStyle('B9')->getFont()->setName('Tahoma')->setSize(11)->setBold(true);

        // Column numbering (Row 10)
        $sheet->setCellValue('A10', '');
        for ($col = 1; $col <= 23; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col + 1); // Col B is 2
            $sheet->setCellValue("{$colLetter}10", $col);
            $sheet->getStyle("{$colLetter}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colLetter}10")->getFont()->setName('Tahoma')->setSize(8)->getColor()->setRGB('555555');
        }

        // Table Column Headers (Row 11)
        $headers = [
            'A' => 'Líneas',
            'B' => 'RNC o Cédula',
            'C' => 'Tipo ID',
            'D' => 'Tipo Bienes y Servicios',
            'E' => 'Número Comprobante Fiscal',
            'F' => 'NCF o Doc Modificado',
            'G' => 'Fecha Comprobante',
            'H' => 'Fecha Pago',
            'I' => 'Monto Servicios',
            'J' => 'Monto Bienes',
            'K' => 'Total Facturado',
            'L' => 'ITBIS Facturado',
            'M' => 'ITBIS Retenido',
            'N' => 'ITBIS Proporcional',
            'O' => 'ITBIS al Costo',
            'P' => 'ITBIS por Adelantar',
            'Q' => 'ITBIS Percibido',
            'R' => 'Tipo Retención ISR',
            'S' => 'Monto Retención Renta',
            'T' => 'ISR Percibido',
            'U' => 'Imp. Selectivo Consumo',
            'V' => 'Otros Impuestos',
            'W' => 'Propina Legal',
            'X' => 'Forma de Pago',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}11", $title);
        }

        // Style Header Row 11
        $headerStyle = [
            'font' => [
                'name' => 'Tahoma',
                'size' => 9,
                'bold' => true,
                'color' => ['rgb' => self::TEXT_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::DGII_GREEN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::TEXT_WHITE],
                ],
            ],
        ];
        $sheet->getStyle('A11:X11')->applyFromArray($headerStyle);
        $sheet->getRowDimension(11)->setRowHeight(38);

        // Data Rows (Row 12+)
        $currentRow = 12;
        $lineNum = 1;

        foreach ($records as $r) {
            $sheet->setCellValue("A{$currentRow}", $lineNum);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Strings / Codes
            $sheet->setCellValueExplicit("B{$currentRow}", $r['rnc_proveedor'] ?? '', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currentRow}", (string)($r['tipo_identificacion'] ?? '1'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currentRow}", (string)($r['tipo_bien_servicio'] ?? '02'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currentRow}", (string)($r['ncf'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currentRow}", (string)($r['ncf_modificado'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$currentRow}", (string)($r['fecha_comprobante'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$currentRow}", (string)($r['fecha_pago'] ?? ''), DataType::TYPE_STRING);

            // Centering string codes
            $sheet->getStyle("B{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Numeric Columns (I to W)
            $sheet->setCellValue("I{$currentRow}", (float)($r['monto_servicios'] ?? 0));
            $sheet->setCellValue("J{$currentRow}", (float)($r['monto_bienes'] ?? 0));
            $sheet->setCellValue("K{$currentRow}", (float)($r['total_facturado'] ?? 0));
            $sheet->setCellValue("L{$currentRow}", (float)($r['itbis_facturado'] ?? 0));
            $sheet->setCellValue("M{$currentRow}", (float)($r['itbis_retenido'] ?? 0));
            $sheet->setCellValue("N{$currentRow}", (float)($r['itbis_proporcional'] ?? 0));
            $sheet->setCellValue("O{$currentRow}", (float)($r['itbis_costo'] ?? 0));
            $sheet->setCellValue("P{$currentRow}", (float)($r['itbis_adelantar'] ?? 0));
            $sheet->setCellValue("Q{$currentRow}", (float)($r['itbis_percibido'] ?? 0));
            $sheet->setCellValueExplicit("R{$currentRow}", (string)($r['tipo_retencion_isr'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("S{$currentRow}", (float)($r['isr_retenido'] ?? 0));
            $sheet->setCellValue("T{$currentRow}", (float)($r['isr_percibido'] ?? 0));
            $sheet->setCellValue("U{$currentRow}", (float)($r['isc'] ?? 0));
            $sheet->setCellValue("V{$currentRow}", (float)($r['otros_impuestos'] ?? 0));
            $sheet->setCellValue("W{$currentRow}", (float)($r['propina_legal'] ?? 0));
            $sheet->setCellValueExplicit("X{$currentRow}", (string)($r['forma_pago'] ?? '02'), DataType::TYPE_STRING);

            // Alignments & Number formats
            $sheet->getStyle("I{$currentRow}:Q{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("S{$currentRow}:W{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("R{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("X{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Font & Row height
            $sheet->getStyle("A{$currentRow}:X{$currentRow}")->getFont()->setName('Tahoma')->setSize(9);
            $sheet->getRowDimension($currentRow)->setRowHeight(20);

            $currentRow++;
            $lineNum++;
        }

        // Apply borders to data rows
        if ($lineNum > 1) {
            $lastDataRow = $currentRow - 1;
            $sheet->getStyle("A12:X{$lastDataRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER_GRAY);

            // Summary Totals Row
            $sheet->setCellValue("A{$currentRow}", 'TOTALES');
            $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sumCols = ['I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'S', 'T', 'U', 'V', 'W'];
            foreach ($sumCols as $c) {
                $sheet->setCellValue("{$c}{$currentRow}", "=SUM({$c}12:{$c}{$lastDataRow})");
                $sheet->getStyle("{$c}{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            // Style Totals Row
            $totalStyle = [
                'font' => ['name' => 'Tahoma', 'size' => 9, 'bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::DGII_LIGHT_GREEN]],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
                ],
            ];
            $sheet->getStyle("A{$currentRow}:X{$currentRow}")->applyFromArray($totalStyle);
            $sheet->getRowDimension($currentRow)->setRowHeight(24);

            // Update Metadata Card Total Monto Facturado (Row 7)
            $sheet->setCellValue('A7', 'Total Monto Facturado:');
            $sheet->getStyle('A7')->getFont()->setName('Tahoma')->setSize(9)->setBold(true);
            $sheet->setCellValue('B7', "=K{$currentRow}");
            $sheet->getStyle('B7')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('B7')->getFont()->setName('Tahoma')->setSize(9)->setBold(true);
        }

        // Auto-fit Column Widths
        $this->autoFitColumns($sheet, 'A', 'X');

        return $spreadsheet;
    }

    /**
     * Generate Formato 607 (Ventas de Bienes y Servicios)
     */
    public function generate607Excel(string $companyTaxId, string $period, array $records): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('607');
        $sheet->setShowGridLines(true);

        // Header Title Block
        $sheet->setCellValue('B1', 'Dirección General de Impuestos Internos');
        $sheet->setCellValue('H1', 'Herramienta de Distribución Gratuita');
        $sheet->setCellValue('B2', 'Formato de Envío de Ventas de Bienes y Servicios (Formato 607)');
        $sheet->setCellValue('H2', 'Norma General 07-2018');
        $sheet->setCellValue('B3', 'Bills - Sistema de Facturación Electrónica');

        $sheet->getStyle('B1')->getFont()->setName('Tahoma')->setSize(13)->setBold(true);
        $sheet->getStyle('H1')->getFont()->setName('Tahoma')->setSize(9)->setItalic(true);
        $sheet->getStyle('B2')->getFont()->setName('Tahoma')->setSize(11)->setBold(true);
        $sheet->getStyle('H2')->getFont()->setName('Tahoma')->setSize(9);
        $sheet->getStyle('B3')->getFont()->setName('Tahoma')->setSize(9)->getColor()->setRGB('666666');

        // Metadata Block
        $metadata = [
            4 => ['A' => 'RNC o Cédula:', 'B' => $companyTaxId, 'type' => 'string'],
            5 => ['A' => 'Período:', 'B' => $period, 'type' => 'string'],
            6 => ['A' => 'Cantidad Registros:', 'B' => count($records), 'type' => 'number'],
        ];

        foreach ($metadata as $row => $meta) {
            $sheet->setCellValue("A{$row}", $meta['A']);
            $sheet->getStyle("A{$row}")->getFont()->setName('Tahoma')->setSize(9)->setBold(true);

            if ($meta['type'] === 'string') {
                $sheet->setCellValueExplicit("B{$row}", $meta['B'], DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue("B{$row}", $meta['B']);
            }
            $sheet->getStyle("B{$row}")->getFont()->setName('Tahoma')->setSize(9);
        }

        // Section Title
        $sheet->setCellValue('B9', 'Detalle de Ventas');
        $sheet->getStyle('B9')->getFont()->setName('Tahoma')->setSize(11)->setBold(true);

        // Column numbering (Row 10)
        $sheet->setCellValue('A10', '');
        for ($col = 1; $col <= 23; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue("{$colLetter}10", $col);
            $sheet->getStyle("{$colLetter}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colLetter}10")->getFont()->setName('Tahoma')->setSize(8)->getColor()->setRGB('555555');
        }

        // Table Column Headers (Row 11) - Norma 07-18
        $headers = [
            'A' => 'Líneas',
            'B' => 'RNC/Cédula o Pasaporte',
            'C' => 'Tipo Identificación',
            'D' => 'Número Comprobante Fiscal',
            'E' => 'NCF Modificado',
            'F' => 'Tipo de Ingreso',
            'G' => 'Fecha Comprobante',
            'H' => 'Fecha Retención',
            'I' => 'Monto Facturado',
            'J' => 'ITBIS Facturado',
            'K' => 'ITBIS Retenido por Terceros',
            'L' => 'ITBIS Percibido',
            'M' => 'Retención Renta por Terceros',
            'N' => 'ISR Percibido',
            'O' => 'Imp. Selectivo Consumo',
            'P' => 'Otros Impuestos/Tasas',
            'Q' => 'Monto Propina Legal',
            'R' => 'Efectivo',
            'S' => 'Cheque/ Transferencia',
            'T' => 'Tarjeta Débito/Crédito',
            'U' => 'Venta a Crédito',
            'V' => 'Bonos o Certificados',
            'W' => 'Permuta',
            'X' => 'Otras Formas de Venta',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}11", $title);
        }

        $headerStyle = [
            'font' => [
                'name' => 'Tahoma',
                'size' => 9,
                'bold' => true,
                'color' => ['rgb' => self::TEXT_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::DGII_GREEN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::TEXT_WHITE],
                ],
            ],
        ];
        $sheet->getStyle('A11:X11')->applyFromArray($headerStyle);
        $sheet->getRowDimension(11)->setRowHeight(38);

        // Data Rows
        $currentRow = 12;
        $lineNum = 1;

        foreach ($records as $r) {
            $sheet->setCellValue("A{$currentRow}", $lineNum);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Text columns
            $sheet->setCellValueExplicit("B{$currentRow}", (string)($r['rnc_cliente'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currentRow}", (string)($r['tipo_identificacion'] ?? '3'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currentRow}", (string)($r['ncf'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$currentRow}", (string)($r['ncf_modificado'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currentRow}", (string)($r['tipo_ingreso'] ?? '01'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$currentRow}", (string)($r['fecha_comprobante'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$currentRow}", (string)($r['fecha_pago'] ?? ''), DataType::TYPE_STRING);

            $sheet->getStyle("B{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Numbers
            $sheet->setCellValue("I{$currentRow}", (float)($r['monto_facturado'] ?? 0));
            $sheet->setCellValue("J{$currentRow}", (float)($r['itbis_facturado'] ?? 0));
            $sheet->setCellValue("K{$currentRow}", (float)($r['itbis_retenido'] ?? 0));
            
            // ITBIS percibido: leave empty if 0 per DGII rule
            $itbisPerc = (!empty($r['itbis_percibido']) && (float)$r['itbis_percibido'] > 0) ? (float)$r['itbis_percibido'] : null;
            if ($itbisPerc !== null) {
                $sheet->setCellValue("L{$currentRow}", $itbisPerc);
            } else {
                $sheet->setCellValue("L{$currentRow}", '');
            }

            $sheet->setCellValue("M{$currentRow}", (float)($r['retencion_isr'] ?? 0));

            // ISR percibido: leave empty if 0 per DGII rule
            $isrPerc = (!empty($r['isr_percibido']) && (float)$r['isr_percibido'] > 0) ? (float)$r['isr_percibido'] : null;
            if ($isrPerc !== null) {
                $sheet->setCellValue("N{$currentRow}", $isrPerc);
            } else {
                $sheet->setCellValue("N{$currentRow}", '');
            }

            $sheet->setCellValue("O{$currentRow}", (float)($r['isc'] ?? 0));
            $sheet->setCellValue("P{$currentRow}", (float)($r['otros_impuestos'] ?? 0));
            $sheet->setCellValue("Q{$currentRow}", (float)($r['propina_legal'] ?? 0));

            // Formas de Pago (R to X)
            $sheet->setCellValue("R{$currentRow}", (float)($r['efectivo'] ?? 0));
            $sheet->setCellValue("S{$currentRow}", (float)($r['bancos'] ?? 0));
            $sheet->setCellValue("T{$currentRow}", (float)($r['tarjeta'] ?? 0));
            $sheet->setCellValue("U{$currentRow}", (float)($r['credito'] ?? 0));
            $sheet->setCellValue("V{$currentRow}", (float)($r['bonos'] ?? 0));
            $sheet->setCellValue("W{$currentRow}", (float)($r['permuta'] ?? 0));
            $sheet->setCellValue("X{$currentRow}", (float)($r['otras_formas'] ?? 0));

            // Format monetary columns
            $sheet->getStyle("I{$currentRow}:X{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');

            $sheet->getStyle("A{$currentRow}:X{$currentRow}")->getFont()->setName('Tahoma')->setSize(9);
            $sheet->getRowDimension($currentRow)->setRowHeight(20);

            $currentRow++;
            $lineNum++;
        }

        // Apply borders & Totals
        if ($lineNum > 1) {
            $lastDataRow = $currentRow - 1;
            $sheet->getStyle("A12:X{$lastDataRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER_GRAY);

            // Totals Row
            $sheet->setCellValue("A{$currentRow}", 'TOTALES');
            $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sumCols = ['I', 'J', 'K', 'M', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X'];
            foreach ($sumCols as $c) {
                $sheet->setCellValue("{$c}{$currentRow}", "=SUM({$c}12:{$c}{$lastDataRow})");
                $sheet->getStyle("{$c}{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            $totalStyle = [
                'font' => ['name' => 'Tahoma', 'size' => 9, 'bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::DGII_LIGHT_GREEN]],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
                ],
            ];
            $sheet->getStyle("A{$currentRow}:X{$currentRow}")->applyFromArray($totalStyle);
            $sheet->getRowDimension($currentRow)->setRowHeight(24);

            // Update Metadata Card Total Monto Facturado (Row 7)
            $sheet->setCellValue('A7', 'Total Facturado Neto:');
            $sheet->getStyle('A7')->getFont()->setName('Tahoma')->setSize(9)->setBold(true);
            $sheet->setCellValue('B7', "=I{$currentRow}");
            $sheet->getStyle('B7')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('B7')->getFont()->setName('Tahoma')->setSize(9)->setBold(true);
        }

        // Auto-fit Column Widths
        $this->autoFitColumns($sheet, 'A', 'X');

        return $spreadsheet;
    }

    /**
     * Generate Formato 608 (Comprobantes Fiscales Anulados)
     */
    public function generate608Excel(string $companyTaxId, string $period, array $records): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('608');
        $sheet->setShowGridLines(true);

        // Header Title Block
        $sheet->setCellValue('B1', 'Dirección General de Impuestos Internos');
        $sheet->setCellValue('G1', 'HERRAMIENTA DE DISTRIBUCIÓN GRATUITA');
        $sheet->setCellValue('B2', 'Formato de Envío de Comprobantes Fiscales Anulados (Formato 608)');
        $sheet->setCellValue('G2', 'Todos los Derechos Reservados DGII');
        $sheet->setCellValue('B3', 'Bills - Sistema de Facturación Electrónica');

        $sheet->getStyle('B1')->getFont()->setName('Tahoma')->setSize(13)->setBold(true);
        $sheet->getStyle('G1')->getFont()->setName('Tahoma')->setSize(9)->setItalic(true);
        $sheet->getStyle('B2')->getFont()->setName('Tahoma')->setSize(11)->setBold(true);
        $sheet->getStyle('G2')->getFont()->setName('Tahoma')->setSize(9);
        $sheet->getStyle('B3')->getFont()->setName('Tahoma')->setSize(9)->getColor()->setRGB('666666');

        // Metadata Block
        $metadata = [
            5 => ['A' => 'RNC o Cédula:', 'B' => $companyTaxId, 'type' => 'string'],
            6 => ['A' => 'Período:', 'B' => $period, 'type' => 'string'],
            7 => ['A' => 'Cantidad Registros:', 'B' => count($records), 'type' => 'number'],
        ];

        foreach ($metadata as $row => $meta) {
            $sheet->setCellValue("A{$row}", $meta['A']);
            $sheet->getStyle("A{$row}")->getFont()->setName('Tahoma')->setSize(9)->setBold(true);

            if ($meta['type'] === 'string') {
                $sheet->setCellValueExplicit("B{$row}", $meta['B'], DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue("B{$row}", $meta['B']);
            }
            $sheet->getStyle("B{$row}")->getFont()->setName('Tahoma')->setSize(9);
        }

        // Anulation Catalog Legend Box (Columns I-J)
        $sheet->setCellValue('I2', 'Código');
        $sheet->setCellValue('J2', 'Tabla de Tipos de Anulación (Norma DGII)');
        $sheet->getStyle('I2:J2')->getFont()->setName('Tahoma')->setSize(9)->setBold(true)->getColor()->setRGB(self::TEXT_WHITE);
        $sheet->getStyle('I2:J2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::DGII_GREEN);
        $sheet->getStyle('I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $catRow = 3;
        foreach (self::ANULATION_TYPES as $code => $desc) {
            $sheet->setCellValueExplicit("I{$catRow}", $code, DataType::TYPE_STRING);
            $sheet->setCellValue("J{$catRow}", $desc);
            $sheet->getStyle("I{$catRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$catRow}:J{$catRow}")->getFont()->setName('Tahoma')->setSize(8.5);
            $sheet->getStyle("I{$catRow}:J{$catRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::DGII_GRAY);
            $catRow++;
        }
        $sheet->getStyle("I2:J" . ($catRow - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER_GRAY);

        // Section Title
        $sheet->setCellValue('B9', 'Detalle de Comprobantes Anulados');
        $sheet->getStyle('B9')->getFont()->setName('Tahoma')->setSize(11)->setBold(true);

        // Column numbering (Row 10)
        $sheet->setCellValue('B10', '1');
        $sheet->setCellValue('C10', '2');
        $sheet->setCellValue('D10', '3');
        $sheet->getStyle('B10:D10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B10:D10')->getFont()->setName('Tahoma')->setSize(8)->getColor()->setRGB('555555');

        // Table Column Headers (Row 11)
        $headers = [
            'A' => 'Líneas',
            'B' => 'Número de Comprobante Fiscal',
            'C' => 'Fecha de Comprobante',
            'D' => 'Tipo de Anulación',
            'E' => 'Descripción del Motivo',
            'F' => 'Cliente / Beneficiario',
            'G' => 'Monto Total',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}11", $title);
        }

        $headerStyle = [
            'font' => [
                'name' => 'Tahoma',
                'size' => 9,
                'bold' => true,
                'color' => ['rgb' => self::TEXT_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::DGII_GREEN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::TEXT_WHITE],
                ],
            ],
        ];
        $sheet->getStyle('A11:G11')->applyFromArray($headerStyle);
        $sheet->getRowDimension(11)->setRowHeight(32);

        // Data Rows
        $currentRow = 12;
        $lineNum = 1;

        foreach ($records as $r) {
            $sheet->setCellValue("A{$currentRow}", $lineNum);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $typeCode = str_pad((string)($r['tipo_anulacion'] ?? '05'), 2, '0', STR_PAD_LEFT);
            $desc = self::ANULATION_TYPES[$typeCode] ?? ($r['motivo_descripcion'] ?? 'Corrección de la Información');

            $sheet->setCellValueExplicit("B{$currentRow}", (string)($r['ncf'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currentRow}", (string)($r['fecha_comprobante'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currentRow}", $typeCode, DataType::TYPE_STRING);
            $sheet->setCellValue("E{$currentRow}", $desc);
            $sheet->setCellValue("F{$currentRow}", (string)($r['cliente_nombre'] ?? ''));
            $sheet->setCellValue("G{$currentRow}", (float)($r['monto'] ?? 0));

            $sheet->getStyle("B{$currentRow}:D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');

            $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getFont()->setName('Tahoma')->setSize(9);
            $sheet->getRowDimension($currentRow)->setRowHeight(20);

            $currentRow++;
            $lineNum++;
        }

        // Borders
        if ($lineNum > 1) {
            $lastDataRow = $currentRow - 1;
            $sheet->getStyle("A12:G{$lastDataRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER_GRAY);

            // Totals row for amount
            $sheet->setCellValue("A{$currentRow}", 'TOTAL ANULADO:');
            $sheet->mergeCells("A{$currentRow}:F{$currentRow}");
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue("G{$currentRow}", "=SUM(G12:G{$lastDataRow})");
            $sheet->getStyle("G{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');

            $totalStyle = [
                'font' => ['name' => 'Tahoma', 'size' => 9, 'bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::DGII_LIGHT_GREEN]],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
                ],
            ];
            $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray($totalStyle);
            $sheet->getRowDimension($currentRow)->setRowHeight(24);
        }

        // Auto-fit Column Widths
        $this->autoFitColumns($sheet, 'A', 'G');
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(46);

        return $spreadsheet;
    }

    /**
     * Helper to auto-fit columns with safety margins
     */
    private function autoFitColumns($sheet, string $startCol, string $endCol): void
    {
        $startIdx = Coordinate::columnIndexFromString($startCol);
        $endIdx = Coordinate::columnIndexFromString($endCol);

        for ($i = $startIdx; $i <= $endIdx; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }
}
