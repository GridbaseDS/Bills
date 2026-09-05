<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class DgiiChunkFilter implements IReadFilter
{
    private int $maxRow;

    public function __construct(int $maxRow = 500)
    {
        $this->maxRow = $maxRow;
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row <= $this->maxRow;
    }
}

class DgiiExcelService
{
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
     * Generate Formato 606 using the official DGII template (Formato_DGII_606.xls)
     */
    public function generate606Excel(string $companyTaxId, string $period, array $records): Spreadsheet
    {
        $templatePath = resource_path('templates/dgii/Formato_DGII_606.xls');
        if (!file_exists($templatePath)) {
            // Fallback if named Formato_Monica.xls
            $templatePath = resource_path('templates/dgii/Formato_Monica.xls');
        }
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template Formato 606 no encontrado en: {$templatePath}");
        }

        $recordCount = count($records);
        $maxRow = max(20, 11 + $recordCount);

        $reader = IOFactory::createReader('Xls');
        $reader->setReadFilter(new DgiiChunkFilter($maxRow));
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Branding: Ensure B3 and document properties are Gridbase Bills
        $sheet->setCellValue('B3', 'Gridbase Bills');
        $spreadsheet->getProperties()
            ->setCreator('Gridbase Bills')
            ->setLastModifiedBy('Gridbase Bills')
            ->setTitle("DGII 606 {$companyTaxId} {$period}")
            ->setCompany('Gridbase');

        // 1. Fill Header Metadata (Rows 4 - 7)
        // Column C is the official white input box with border; Column B is also populated for compatibility
        $sheet->setCellValueExplicit('C4', $companyTaxId, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B4', $companyTaxId, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C5', $period, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B5', $period, DataType::TYPE_STRING);
        $sheet->setCellValue('C6', $recordCount);
        $sheet->setCellValue('K7', 0); // Total de líneas de error

        // 2. Fill Detail Rows (Starting at Row 12)
        $row = 12;
        $line = 1;
        foreach ($records as $r) {
            $sheet->setCellValue("A{$row}", $line);
            $sheet->setCellValueExplicit("B{$row}", (string)($r['rnc_proveedor'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", (string)($r['tipo_identificacion'] ?? '1'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", (string)($r['tipo_bien_servicio'] ?? '02'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$row}", (string)($r['ncf'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$row}", (string)($r['ncf_modificado'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$row}", (string)($r['fecha_comprobante'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("I{$row}", (string)($r['fecha_pago'] ?? ''), DataType::TYPE_STRING);

            $sheet->setCellValue("K{$row}", (float)($r['monto_servicios'] ?? 0));
            $sheet->setCellValue("L{$row}", (float)($r['monto_bienes'] ?? 0));
            $sheet->setCellValue("M{$row}", (float)($r['total_facturado'] ?? 0));
            $sheet->setCellValue("N{$row}", (float)($r['itbis_facturado'] ?? 0));
            $sheet->setCellValue("O{$row}", (float)($r['itbis_retenido'] ?? 0));
            $sheet->setCellValue("P{$row}", (float)($r['itbis_proporcional'] ?? 0));
            $sheet->setCellValue("Q{$row}", (float)($r['itbis_costo'] ?? 0));
            $sheet->setCellValue("R{$row}", (float)($r['itbis_adelantar'] ?? 0));
            $sheet->setCellValue("S{$row}", (float)($r['itbis_percibido'] ?? 0));

            $sheet->setCellValueExplicit("T{$row}", (string)($r['tipo_retencion_isr'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("U{$row}", (float)($r['isr_retenido'] ?? 0));
            $sheet->setCellValue("V{$row}", (float)($r['isr_percibido'] ?? 0));
            $sheet->setCellValue("W{$row}", (float)($r['isc'] ?? 0));
            $sheet->setCellValue("X{$row}", (float)($r['otros_impuestos'] ?? 0));
            $sheet->setCellValue("Y{$row}", (float)($r['propina_legal'] ?? 0));
            $sheet->setCellValueExplicit("Z{$row}", (string)($r['forma_pago'] ?? '02'), DataType::TYPE_STRING);

            $row++;
            $line++;
        }

        return $spreadsheet;
    }

    /**
     * Generate Formato 607 using the official DGII template (Formato_DGII_607.xls)
     */
    public function generate607Excel(string $companyTaxId, string $period, array $records): Spreadsheet
    {
        $templatePath = resource_path('templates/dgii/Formato_DGII_607.xls');
        if (!file_exists($templatePath)) {
            $templatePath = resource_path('templates/dgii/Formato_Monica_607.xls');
        }
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template Formato 607 no encontrado en: {$templatePath}");
        }

        $recordCount = count($records);
        $maxRow = max(20, 11 + $recordCount);

        $reader = IOFactory::createReader('Xls');
        $reader->setReadFilter(new DgiiChunkFilter($maxRow));
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Branding: Ensure B3 and document properties are Gridbase Bills
        $sheet->setCellValue('B3', 'Gridbase Bills');
        $spreadsheet->getProperties()
            ->setCreator('Gridbase Bills')
            ->setLastModifiedBy('Gridbase Bills')
            ->setTitle("DGII 607 {$companyTaxId} {$period}")
            ->setCompany('Gridbase');

        // 1. Fill Header Metadata (Rows 4 - 7)
        // A4:B4 and A5:B5 are merged label cells; Column C is the official white input box with border
        $sheet->setCellValueExplicit('C4', $companyTaxId, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B4', $companyTaxId, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C5', $period, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B5', $period, DataType::TYPE_STRING);
        $sheet->setCellValue('C6', $recordCount);
        $sheet->setCellValue('G7', 0); // Total de Errores

        // 2. Fill Detail Rows (Starting at Row 12)
        $totalMonto = 0;
        $row = 12;
        $line = 1;
        foreach ($records as $r) {
            $monto = (float)($r['monto_facturado'] ?? 0);
            $totalMonto += $monto;

            $sheet->setCellValue("A{$row}", $line);
            $sheet->setCellValueExplicit("B{$row}", (string)($r['rnc_cliente'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", (string)($r['tipo_identificacion'] ?? '1'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", (string)($r['ncf'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$row}", (string)($r['ncf_modificado'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$row}", (string)($r['tipo_ingreso'] ?? '01'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$row}", (string)($r['fecha_comprobante'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$row}", (string)($r['fecha_pago'] ?? ''), DataType::TYPE_STRING);

            $sheet->setCellValue("I{$row}", $monto);
            $sheet->setCellValue("J{$row}", (float)($r['itbis_facturado'] ?? 0));
            $sheet->setCellValue("K{$row}", (float)($r['itbis_retenido'] ?? 0));

            // Pre-validator rule: leave blank if 0
            if (!empty($r['itbis_percibido']) && (float)$r['itbis_percibido'] > 0) {
                $sheet->setCellValue("L{$row}", (float)$r['itbis_percibido']);
            } else {
                $sheet->setCellValue("L{$row}", '');
            }

            $sheet->setCellValue("M{$row}", (float)($r['retencion_isr'] ?? 0));

            if (!empty($r['isr_percibido']) && (float)$r['isr_percibido'] > 0) {
                $sheet->setCellValue("N{$row}", (float)$r['isr_percibido']);
            } else {
                $sheet->setCellValue("N{$row}", '');
            }

            $sheet->setCellValue("O{$row}", (float)($r['isc'] ?? 0));
            $sheet->setCellValue("P{$row}", (float)($r['otros_impuestos'] ?? 0));
            $sheet->setCellValue("Q{$row}", (float)($r['propina_legal'] ?? 0));

            // Formas de Pago (17 a 23)
            $sheet->setCellValue("R{$row}", (float)($r['efectivo'] ?? 0));
            $sheet->setCellValue("S{$row}", (float)($r['bancos'] ?? 0));
            $sheet->setCellValue("T{$row}", (float)($r['tarjeta'] ?? 0));
            $sheet->setCellValue("U{$row}", (float)($r['credito'] ?? 0));
            $sheet->setCellValue("V{$row}", (float)($r['bonos'] ?? 0));
            $sheet->setCellValue("W{$row}", (float)($r['permuta'] ?? 0));
            $sheet->setCellValue("X{$row}", (float)($r['otras_formas'] ?? 0));

            $row++;
            $line++;
        }

        // Valor Calculado en E7
        $sheet->setCellValue('E7', $totalMonto);

        return $spreadsheet;
    }

    /**
     * Generate Formato 608 using the official DGII template (Formato_DGII_608.xls)
     */
    public function generate608Excel(string $companyTaxId, string $period, array $records): Spreadsheet
    {
        $templatePath = resource_path('templates/dgii/Formato_DGII_608.xls');
        if (!file_exists($templatePath)) {
            $templatePath = resource_path('templates/dgii/Formato_Monica_608.xls');
        }
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template Formato 608 no encontrado en: {$templatePath}");
        }

        $recordCount = count($records);
        $maxRow = max(20, 11 + $recordCount);

        $reader = IOFactory::createReader('Xls');
        $reader->setReadFilter(new DgiiChunkFilter($maxRow));
        $spreadsheet = $reader->load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Branding: Ensure B3 and document properties are Gridbase Bills
        $sheet->setCellValue('B3', 'Gridbase Bills');
        $spreadsheet->getProperties()
            ->setCreator('Gridbase Bills')
            ->setLastModifiedBy('Gridbase Bills')
            ->setTitle("DGII 608 {$companyTaxId} {$period}")
            ->setCompany('Gridbase');

        // 1. Fill Header Metadata (Rows 5 - 7)
        // Column C is the official white input box with border; Column B is also populated for compatibility
        $sheet->setCellValueExplicit('C5', $companyTaxId, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B5', $companyTaxId, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C6', $period, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B6', $period, DataType::TYPE_STRING);
        $sheet->setCellValue('C7', $recordCount);
        $sheet->setCellValue('E7', 0); // Total Errores

        // 2. Fill Detail Rows (Starting at Row 12)
        $row = 12;
        $line = 1;
        foreach ($records as $r) {
            $sheet->setCellValue("A{$row}", $line);
            $sheet->setCellValueExplicit("B{$row}", (string)($r['ncf'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", (string)($r['fecha_comprobante'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$row}", str_pad((string)($r['tipo_anulacion'] ?? '05'), 2, '0', STR_PAD_LEFT), DataType::TYPE_STRING);

            $row++;
            $line++;
        }

        return $spreadsheet;
    }
}
