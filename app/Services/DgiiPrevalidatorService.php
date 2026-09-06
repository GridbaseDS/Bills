<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DgiiPrevalidatorService
{
    const NCF_TYPES_606_B = ['01', '03', '04', '11', '12', '13', '14', '15', '17'];
    const NCF_TYPES_606_E = ['31', '33', '34', '41', '43', '44', '45', '46', '47'];

    const NCF_TYPES_607_B = ['01', '02', '03', '04', '12', '14', '15', '16'];
    const NCF_TYPES_607_E = ['31', '32', '33', '34', '44', '45'];

    const NCF_TYPES_608_B = ['01', '02', '03', '04', '11', '12', '13', '14', '15', '16', '17', '46'];
    const NCF_TYPES_608_E = ['31', '32', '33', '34', '41', '43', '44', '45', '46', '47'];

    const ANULATION_TYPES_608 = [
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
     * Validador oficial de RNC (Módulo 11 con pesos '79865432')
     */
    public function validateRnc(string $rnc): bool
    {
        $clean = preg_replace('/[^0-9]/', '', $rnc);
        if (strlen($clean) !== 9) {
            return false;
        }

        $weights = [7, 9, 8, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += (int)$clean[$i] * $weights[$i];
        }

        $rem = $sum % 11;
        if ($rem === 0) {
            $dv = 2;
        } elseif ($rem === 1) {
            $dv = 1;
        } else {
            $dv = 11 - $rem;
        }

        return $dv === (int)$clean[8];
    }

    /**
     * Validador oficial de Cédula (Módulo 10 con pesos '1212121212')
     */
    public function validateCedula(string $cedula): bool
    {
        $clean = preg_replace('/[^0-9]/', '', $cedula);
        if (strlen($clean) !== 11) {
            return false;
        }

        $weights = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2];
        $total = 0;
        for ($i = 0; $i < 10; $i++) {
            $prod = (int)$clean[$i] * $weights[$i];
            if ($prod > 9) {
                $prod = intdiv($prod, 10) + ($prod % 10);
            }
            $total += $prod;
        }

        $decena = (int)ceil($total / 10.0) * 10;
        $dv = $decena - $total;
        if ($dv === 10) {
            $dv = 0;
        }

        return $dv === (int)$clean[10];
    }

    /**
     * Valida si un identificador tributario es un RNC o Cédula válido
     */
    public function validateTaxId(string $taxId): array
    {
        $clean = preg_replace('/[^0-9]/', '', $taxId);
        $len = strlen($clean);

        if ($len === 9) {
            return ['valid' => $this->validateRnc($clean), 'type' => 'RNC (9 dígitos)', 'clean' => $clean];
        } elseif ($len === 11) {
            return ['valid' => $this->validateCedula($clean), 'type' => 'Cédula (11 dígitos)', 'clean' => $clean];
        }

        return ['valid' => false, 'type' => 'Longitud Inválida', 'clean' => $clean];
    }

    /**
     * Valida la estructura y tipo de comprobante de un NCF según formato DGII
     */
    public function validateNcf(string $ncf, string $format): array
    {
        $ncf = strtoupper(trim($ncf));
        if (empty($ncf)) {
            return ['valid' => false, 'message' => 'El NCF no puede estar vacío.'];
        }

        if (str_starts_with($ncf, 'B')) {
            if (strlen($ncf) !== 11) {
                return ['valid' => false, 'message' => "Longitud de NCF Serie B debe ser 11 caracteres (tiene " . strlen($ncf) . ")."];
            }
            $tipo = substr($ncf, 1, 2);
            if ($format === '606' && !in_array($tipo, self::NCF_TYPES_606_B)) {
                return ['valid' => false, 'message' => "Tipo B{$tipo} no permitido en Formato 606."];
            }
            if ($format === '607' && !in_array($tipo, self::NCF_TYPES_607_B)) {
                return ['valid' => false, 'message' => "Tipo B{$tipo} no permitido en Formato 607."];
            }
            if ($format === '608' && !in_array($tipo, self::NCF_TYPES_608_B)) {
                return ['valid' => false, 'message' => "Tipo B{$tipo} no permitido en Formato 608."];
            }
            return ['valid' => true, 'is_ecf' => false, 'type' => $tipo];
        }

        if (str_starts_with($ncf, 'E')) {
            if (strlen($ncf) !== 13) {
                return ['valid' => false, 'message' => "Longitud de e-NCF Serie E debe ser 13 caracteres (tiene " . strlen($ncf) . ")."];
            }
            $tipo = substr($ncf, 1, 2);
            if ($format === '606' && !in_array($tipo, self::NCF_TYPES_606_E)) {
                return ['valid' => false, 'message' => "Tipo E{$tipo} no permitido en Formato 606."];
            }
            if ($format === '607' && !in_array($tipo, self::NCF_TYPES_607_E)) {
                return ['valid' => false, 'message' => "Tipo E{$tipo} no permitido en Formato 607."];
            }
            if ($format === '608' && !in_array($tipo, self::NCF_TYPES_608_E)) {
                return ['valid' => false, 'message' => "Tipo E{$tipo} no permitido en Formato 608."];
            }
            return ['valid' => true, 'is_ecf' => true, 'type' => $tipo];
        }

        return ['valid' => false, 'message' => 'Serie de comprobante inválida (debe iniciar con B o E).'];
    }

    /**
     * Valida un lote de registros para un formato específico (606, 607, 608)
     */
    public function validateData(string $format, string $companyTaxId, string $period, array $records): array
    {
        $errors = [];
        $warnings = [];

        $taxIdCheck = $this->validateTaxId($companyTaxId);
        if (!$taxIdCheck['valid']) {
            $errors[] = "Encabezado: El RNC de la empresa ({$companyTaxId}) no es un RNC/Cédula válido según el algoritmo oficial de la DGII.";
        }

        if (strlen($period) !== 6 || !is_numeric($period)) {
            $errors[] = "Encabezado: El período ({$period}) debe tener el formato AAAAMM (6 dígitos).";
        } else {
            $month = (int)substr($period, 4, 2);
            if ($month < 1 || $month > 12) {
                $errors[] = "Encabezado: El mes del período ({$month}) es inválido (debe ser entre 01 y 12).";
            }
        }

        $recordCount = count($records);
        if ($recordCount === 0) {
            $warnings[] = "El período no contiene registros para reportar.";
        }

        $seenNcfs = [];

        foreach ($records as $idx => $r) {
            $line = $idx + 1;

            if ($format === '607') {
                $this->validateLine607($line, $r, $period, $companyTaxId, $errors, $warnings, $seenNcfs);
            } elseif ($format === '606') {
                $this->validateLine606($line, $r, $period, $companyTaxId, $errors, $warnings, $seenNcfs);
            } elseif ($format === '608') {
                $this->validateLine608($line, $r, $period, $errors, $warnings, $seenNcfs);
            }
        }

        return [
            'valid' => count($errors) === 0,
            'format' => $format,
            'rnc' => $companyTaxId,
            'period' => $period,
            'record_count' => $recordCount,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function validateLine607(int $line, array $r, string $period, string $companyTaxId, array &$errors, array &$warnings, array &$seenNcfs): void
    {
        $ncf = trim($r['ncf'] ?? '');
        $ncfCheck = $this->validateNcf($ncf, '607');
        if (!$ncfCheck['valid']) {
            $errors[] = "Línea {$line}: {$ncfCheck['message']}";
        }

        // NCF Duplicado en el mismo reporte
        $upperNcf = strtoupper($ncf);
        if (isset($seenNcfs[$upperNcf])) {
            $errors[] = "Línea {$line}: NCF duplicado ({$ncf}). La DGII rechaza reportes con NCF repetido en el mismo período.";
        }
        $seenNcfs[$upperNcf] = true;

        // Nota de Crédito requiere NCF Modificado
        $isCreditNote = str_starts_with($upperNcf, 'B04') || str_starts_with($upperNcf, 'E34');
        $ncfMod = trim($r['ncf_modificado'] ?? '');
        if ($isCreditNote && empty($ncfMod)) {
            $errors[] = "Línea {$line}: La Nota de Crédito ({$ncf}) requiere obligatoriamente un NCF Modificado.";
        }

        // Validar Fecha de Comprobante
        $fComp = trim($r['fecha_comprobante'] ?? '');
        if (strlen($fComp) !== 8 || !is_numeric($fComp)) {
            $errors[] = "Línea {$line}: Fecha de comprobante ({$fComp}) inválida (debe ser AAAAMMDD).";
        } elseif (substr($fComp, 0, 6) !== $period) {
            $errors[] = "Línea {$line}: La fecha del comprobante ({$fComp}) no corresponde al período declarado ({$period}).";
        }

        // Validar Cliente
        $rncCli = trim($r['rnc_cliente'] ?? '');
        $tipoId = trim((string)($r['tipo_identificacion'] ?? '1'));
        if (!empty($rncCli) && in_array($tipoId, ['1', '2'])) {
            $cliCheck = $this->validateTaxId($rncCli);
            if (!$cliCheck['valid']) {
                $errors[] = "Línea {$line}: RNC/Cédula del cliente ({$rncCli}) no es válido según algoritmo DGII.";
            }
        }

        // Validar cuadre de Formas de Pago
        $monto = (float)($r['monto_facturado'] ?? 0);
        $itbis = (float)($r['itbis_facturado'] ?? 0);
        $totalEsperado = round($monto + $itbis, 2);

        $efectivo = (float)($r['efectivo'] ?? 0);
        $bancos = (float)($r['bancos'] ?? 0);
        $tarjeta = (float)($r['tarjeta'] ?? 0);
        $credito = (float)($r['credito'] ?? 0);
        $bonos = (float)($r['bonos'] ?? 0);
        $permuta = (float)($r['permuta'] ?? 0);
        $otras = (float)($r['otras_formas'] ?? 0);

        $totalPagos = round($efectivo + $bancos + $tarjeta + credito + $bonos + $permuta + $otras, 2);
        if (abs($totalPagos - $totalEsperado) > 0.05) {
            $warnings[] = "Línea {$line} (NCF {$ncf}): La suma de formas de pago (RD$ " . number_format($totalPagos, 2) . ") no coincide con el total facturado (RD$ " . number_format($totalEsperado, 2) . ").";
        }
    }

    protected function validateLine606(int $line, array $r, string $period, string $companyTaxId, array &$errors, array &$warnings, array &$seenNcfs): void
    {
        $ncf = trim($r['ncf'] ?? '');
        $ncfCheck = $this->validateNcf($ncf, '606');
        if (!$ncfCheck['valid']) {
            $errors[] = "Línea {$line}: {$ncfCheck['message']}";
        }

        $upperNcf = strtoupper($ncf);
        if (isset($seenNcfs[$upperNcf])) {
            $errors[] = "Línea {$line}: NCF duplicado ({$ncf}) en Formato 606.";
        }
        $seenNcfs[$upperNcf] = true;

        $rncProv = trim($r['rnc_proveedor'] ?? '');
        if (empty($rncProv)) {
            $errors[] = "Línea {$line}: RNC o Cédula del proveedor no puede estar en blanco.";
        } else {
            $cleanProv = preg_replace('/[^0-9]/', '', $rncProv);
            $cleanCompany = preg_replace('/[^0-9]/', '', $companyTaxId);
            if ($cleanProv === $cleanCompany && !str_starts_with($upperNcf, 'B11') && !str_starts_with($upperNcf, 'E41')) {
                $errors[] = "Línea {$line}: El RNC del proveedor ({$rncProv}) no debe ser el mismo de la empresa que reporta.";
            }

            $tipoId = trim((string)($r['tipo_identificacion'] ?? '1'));
            if (in_array($tipoId, ['1', '2'])) {
                $provCheck = $this->validateTaxId($rncProv);
                if (!$provCheck['valid']) {
                    $errors[] = "Línea {$line}: RNC/Cédula del proveedor ({$rncProv}) no es válido según algoritmo DGII.";
                }
            }
        }

        $fComp = trim($r['fecha_comprobante'] ?? '');
        if (strlen($fComp) !== 8 || !is_numeric($fComp)) {
            $errors[] = "Línea {$line}: Fecha de comprobante ({$fComp}) inválida (debe ser AAAAMMDD).";
        }

        $formaPago = trim((string)($r['forma_pago'] ?? '02'));
        if (!in_array($formaPago, ['01', '02', '03', '04', '05', '06', '07'])) {
            $errors[] = "Línea {$line}: Forma de pago ({$formaPago}) inválida (debe ser entre 01 y 07).";
        }
    }

    protected function validateLine608(int $line, array $r, string $period, array &$errors, array &$warnings, array &$seenNcfs): void
    {
        $ncf = trim($r['ncf'] ?? '');
        $ncfCheck = $this->validateNcf($ncf, '608');
        if (!$ncfCheck['valid']) {
            $errors[] = "Línea {$line}: {$ncfCheck['message']}";
        }

        $fAnul = trim($r['fecha_comprobante'] ?? $r['fecha_anulacion'] ?? '');
        if (strlen($fAnul) !== 8 || !is_numeric($fAnul)) {
            $errors[] = "Línea {$line}: Fecha de anulación ({$fAnul}) inválida (debe ser AAAAMMDD).";
        } elseif (substr($fAnul, 0, 6) !== $period) {
            $errors[] = "Línea {$line}: La fecha de anulación ({$fAnul}) no corresponde al período ({$period}).";
        }

        $tipoAnul = trim((string)($r['tipo_anulacion'] ?? '04'));
        if (!isset(self::ANULATION_TYPES_608[$tipoAnul])) {
            $errors[] = "Línea {$line}: Tipo de anulación ({$tipoAnul}) no reconocido por la DGII (debe ser entre 01 y 09).";
        }
    }
}
