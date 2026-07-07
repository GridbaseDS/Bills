<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isQuote ?? false ? 'Cotización' : 'Factura' }}</title>
    <?php
    $primaryColor = $settings['pdf_primary_color'] ?? '#0B484C';
    $accentColor  = $settings['pdf_accent_color'] ?? '#00DF83';
    $pdfLogoUrl   = $settings['pdf_logo_url'] ?? 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png';
    $showFooter   = ($settings['pdf_show_footer'] ?? '1') === '1';
    ?>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #2D2D2D;
            background: #FFFFFF;
            line-height: 1.4;
        }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ── HEADER BLOCK ── */
        .header-block {
            background: <?= $primaryColor ?>;
            padding: 35px 45px 30px 45px;
            color: #FFFFFF;
        }
        .header-block td { vertical-align: middle; }

        .logo-area img { height: 55px; }
        .logo-fallback {
            font-size: 22px;
            font-weight: 700;
            color: #FFFFFF;
        }
        .logo-fallback span { color: <?= $accentColor ?>; }
        .company-label {
            font-size: 14px;
            color: #FFFFFF;
            margin-top: 6px;
            font-weight: 400;
        }
        .company-label strong { font-weight: 700; }

        .header-meta-box {

            border-radius: 4px;
            padding: 10px 16px;
            text-align: right;
            display: inline-block;
            float: right;
        }
        .meta-row-item {
            font-size: 11px;
            color: rgba(255,255,255,0.6);
            margin-bottom: 3px;
            line-height: 1.5;
        }
        .meta-row-item strong {
            color: #FFFFFF;
            font-weight: 700;
        }
        .meta-label {
            color: <?= $accentColor ?>;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 9px;
        }

        /* ── BODY ── */
        .body-content {
            padding: 35px 45px 60px 45px;
        }

        /* Document title */
        .doc-title {
            font-size: 28px;
            font-weight: 700;
            color: <?= $primaryColor ?>;
            text-transform: uppercase;
            border-bottom: 3px solid <?= $primaryColor ?>;
            display: inline-block;
            padding-bottom: 4px;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }

        /* Info columns */
        .info-cols { margin-bottom: 25px; }
        .info-cols td { padding-right: 25px; }
        .info-col-title {
            font-size: 12px;
            font-weight: 700;
            color: <?= $primaryColor ?>;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .info-col-name {
            font-size: 13px;
            font-weight: 700;
            color: #2D2D2D;
            margin-bottom: 2px;
        }
        .info-col-text {
            font-size: 11px;
            color: #666666;
            line-height: 1.6;
        }

        /* ── ITEMS TABLE ── */
        .items-table { margin-bottom: 25px; }
        .items-table thead tr { background: <?= $accentColor ?>; }
        .items-table th {
            color: <?= $primaryColor ?>;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
        }
        .items-table tbody tr {
            border-bottom: 1px solid #E8E8E8;
        }
        .items-table td {
            padding: 10px 12px;
            font-size: 11px;
            color: #444444;
            vertical-align: middle;
        }
        .items-table td.item-desc {
            color: #2D2D2D;
            font-size: 12px;
        }
        .items-table td.item-total {
            font-weight: 700;
            color: #2D2D2D;
        }

        /* ── BOTTOM AREA ── */
        .bottom-area { margin-bottom: 25px; }
        .bottom-area td { vertical-align: top; }

        /* Payment / Notes left side */
        .payment-title {
            font-size: 11px;
            font-weight: 700;
            color: <?= $primaryColor ?>;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .payment-text {
            font-size: 11px;
            color: #444;
            line-height: 1.6;
        }

        /* Totals right side */
        .totals-mini { width: 100%; }
        .totals-mini td {
            padding: 5px 0;
            font-size: 12px;
        }
        .totals-mini .tl {
            text-align: right;
            color: #888;
            padding-right: 15px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 10px;
        }
        .totals-mini .tv {
            text-align: right;
            font-weight: 600;
            color: #2D2D2D;
            width: 80px;
        }
        .totals-mini .grand td {
            border-top: 1.5px solid #2D2D2D;
            padding-top: 8px;
            font-weight: 700;
            font-size: 12px;
        }
        .totals-mini .grand .tl { color: #2D2D2D; font-size: 11px; }
        .totals-mini .grand .tv { color: <?= $primaryColor ?>; font-size: 12px; }

        .totals-mini .paid-row td { padding-top: 10px; }
        .totals-mini .balance-row td {
            background: #FEF2F2;
            padding: 6px 8px;
            border-radius: 3px;
        }
        .totals-mini .balance-row .tl { color: #991B1B; }
        .totals-mini .balance-row .tv { color: #DC2626; font-weight: 700; }

        /* ── TERMS ── */
        .terms-section { margin-top: 20px; }
        .terms-title {
            font-size: 11px;
            font-weight: 700;
            color: <?= $primaryColor ?>;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .terms-line {
            border-top: 1.5px solid #E0E0E0;
            margin-bottom: 8px;
        }
        .terms-text {
            font-size: 11px;
            color: #666;
            line-height: 1.65;
            font-style: italic;
        }

        /* Status */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-paid    { background: #D1FAE5; color: #065F46; }
        .badge-overdue { background: #FEE2E2; color: #991B1B; }
        .badge-draft   { background: #F3F4F6; color: #6B7280; }
        .badge-sent    { background: #DBEAFE; color: #1E40AF; }

        /* ── FOOTER ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: <?= $primaryColor ?>;
            padding: 12px 45px;
            text-align: center;
        }
        .footer-contact {
            font-size: 10px;
            color: rgba(255,255,255,0.85);
        }
        .footer-contact span {
            color: <?= $accentColor ?>;
            margin: 0 8px;
        }
    </style>
</head>
<body>
<?php
$isQuote   = isset($is_quote) && $is_quote;
$isEcf     = !$isQuote && ($invoice['is_ecf'] ?? false);
$ecfType   = (int)($invoice['ecf_type'] ?? 32);
$docName   = $isQuote ? 'Cotización' : ($isEcf ? 'e-CF' : 'Factura');
$docNum    = $isQuote ? ($invoice['quote_number'] ?? '') : ($isEcf ? ($invoice['encf'] ?? '') : ($invoice['invoice_number'] ?? ''));
$dateLabel = $isQuote ? 'Válida Hasta' : 'Vencimiento';
$dateField = $isQuote ? ($invoice['expiry_date'] ?? $invoice['due_date'] ?? '') : ($invoice['due_date'] ?? '');
$logoUrl   = $pdfLogoUrl;

// Fecha vencimiento secuencia: NO aplica para E32 y E34
$showFechaVencimiento = $isEcf && !in_array($ecfType, [32, 34]);
$fechaVencimientoSeq  = $settings['dgii_ncf_expiry_date'] ?? '31/12/2028';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimientoSeq)) {
    $fechaVencimientoSeq = date('d/m/Y', strtotime($fechaVencimientoSeq));
}

// Modification codes for E33/E34
$modCodeDescriptions = [
    1 => 'Anula el NCF modificado',
    2 => 'Corrige Texto del Comprobante Fiscal modificado',
    3 => 'Corrige montos del NCF modificado',
    4 => 'Reemplazo NCF emitido en contingencia',
];

$badgeClass = 'badge-draft'; $badgeText = 'BORRADOR';
if (!empty($invoice['status'])) {
    if ($invoice['status'] === 'paid')    { $badgeClass = 'badge-paid';    $badgeText = 'PAGADA'; }
    elseif ($invoice['status'] === 'overdue') { $badgeClass = 'badge-overdue'; $badgeText = 'VENCIDA'; }
    elseif ($invoice['status'] === 'sent' || $invoice['status'] === 'pending') { $badgeClass = 'badge-sent'; $badgeText = 'PENDIENTE DE PAGO'; }
    elseif ($invoice['status'] === 'partial') { $badgeClass = 'badge-sent'; $badgeText = 'PAGO PARCIAL'; }
}

// ECF type names
$ecfTypeNames = [
    31 => 'Factura de Credito Fiscal Electronica',
    32 => 'Factura de Consumo Electronica',
    33 => 'Nota de Debito Electronica',
    34 => 'Nota de Credito Electronica',
    41 => 'Comprobante de Compras Electronico',
    43 => 'Comprobante de Gastos Menores Electronico',
    44 => 'Comprobante de Regimenes Especiales Electronico',
    45 => 'Comprobante Gubernamental Electronico',
    46 => 'Comprobante de Exportaciones Electronico',
    47 => 'Comprobante de Pagos al Exterior Electronico',
];
$ecfTypeName = $isEcf ? ($ecfTypeNames[$ecfType] ?? 'Comprobante Fiscal Electronico') : $docName;
?>

<!-- ══════════════════════════ HEADER ══════════════════════════ -->
<div class="header-block">
    <table><tr>
        <td style="width:55%; vertical-align:top;">
            <div class="logo-area">
                <img src="<?= $logoUrl ?>" alt="<?= htmlspecialchars($company['name'] ?? '') ?>">
            </div>
            <div style="margin-top:8px;">
                <div style="font-size:14px; font-weight:700; color:#FFFFFF;"><?= htmlspecialchars($company['name'] ?? '') ?></div>
                <?php if (!empty($settings['dgii_razon_social']) && $settings['dgii_razon_social'] !== ($company['name'] ?? '')): ?>
                <div style="font-size:11px; color:rgba(255,255,255,0.75);"><?= htmlspecialchars($settings['dgii_razon_social']) ?></div>
                <?php endif; ?>
                <?php if (!empty($company['tax_id'])): ?>
                <div style="font-size:11px; color:rgba(255,255,255,0.75);">RNC <?= htmlspecialchars($company['tax_id']) ?></div>
                <?php endif; ?>
                <?php if (!empty($company['address'])): ?>
                <div style="font-size:10px; color:rgba(255,255,255,0.6);">Dirección: <?= htmlspecialchars($company['address']) ?><?= !empty($company['city']) ? ', ' . htmlspecialchars($company['city']) : '' ?></div>
                <?php endif; ?>
                <div style="font-size:10px; color:rgba(255,255,255,0.6);">Fecha Emisión: <?= !empty($invoice['issue_date']) ? date('d-m-Y', strtotime($invoice['issue_date'])) : '' ?></div>
            </div>
        </td>
        <td style="width:45%; text-align:right; vertical-align:top;">
            <div style="font-size:14px; font-weight:700; color:#FFFFFF;"><?= $ecfTypeName ?></div>
            <div style="font-size:11px; color:rgba(255,255,255,0.75); line-height:1.6; margin-top:4px;">
                <div><span class="meta-label">e-NCF/</span> <strong style="color:#FFF;"><?= htmlspecialchars($docNum) ?></strong></div>
                <?php if ($showFechaVencimiento): ?>
                <div><span class="meta-label">FECHA VENCIMIENTO/</span> <strong style="color:#FFF;"><?= htmlspecialchars($fechaVencimientoSeq) ?></strong></div>
                <?php endif; ?>
                <?php if ($isEcf && in_array($ecfType, [33, 34]) && !empty($invoice['modified_ncf'])): ?>
                <div><span class="meta-label">NCF MOD./</span> <strong style="color:#FFF;"><?= htmlspecialchars($invoice['modified_ncf']) ?></strong></div>
                <div style="font-size:9px; color:rgba(255,255,255,0.6);"><?= htmlspecialchars($modCodeDescriptions[(int)($invoice['modification_code'] ?? 1)] ?? '') ?></div>
                <?php endif; ?>
            </div>
            <?php if (!$isQuote && !empty($invoice['status'])): ?>
            <div style="margin-top:5px;"><span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></div>
            <?php endif; ?>
        </td>
    </tr></table>
</div>

<!-- ══════════════════════════ SEPARATOR + CLIENT ══════════════════════════ -->
<div style="padding: 10px 45px 0 45px;">
    <div style="border-top: 1.5px solid <?= $primaryColor ?>; padding-top:10px;">
        <?php
        $clientName = trim($client['company_name'] ?? '') ?: trim($client['contact_name'] ?? '');
        $clientRnc = trim($client['tax_id'] ?? '');
        $hasComprador = !in_array($ecfType, [43, 47]);
        ?>
        <?php if ($hasComprador): ?>
        <div style="font-size:12px; color:#2D2D2D; margin-bottom:2px;">
            <strong>Razón Social Cliente:</strong> <?= htmlspecialchars($clientName) ?>
        </div>
        <?php if (!empty($clientRnc)): ?>
        <div style="font-size:12px; color:#2D2D2D; margin-bottom:8px;">
            <strong>RNC Cliente:</strong> <?= htmlspecialchars($clientRnc) ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════ BODY ══════════════════════════ -->
<div class="body-content" style="padding-top:10px;">

    <!-- Items Table — DGII layout with ITBIS column -->
    <?php
    $taxRate = (float)($invoice['tax_rate'] ?? 0);
    ?>
    <table class="items-table">
        <thead><tr>
            <th class="text-center" style="width:10%;">CANT.</th>
            <th style="width:<?= $taxRate > 0 ? '30%' : '40%' ?>;">DESCRIPCIÓN</th>
            <th class="text-center" style="width:12%;">U/M</th>
            <th class="text-right" style="width:15%;">PRECIO</th>
            <?php if ($taxRate > 0): ?>
            <th class="text-right" style="width:15%;">ITBIS</th>
            <?php endif; ?>
            <th class="text-right" style="width:18%;">VALOR</th>
        </tr></thead>
        <tbody>
            <?php foreach (($invoice['items'] ?? $items ?? []) as $index => $item): ?>
            <?php
                $itemAmount = (float)($item['amount'] ?? ($item['quantity'] * $item['unit_price']));
                $itemItbis = $taxRate > 0 ? round($itemAmount * ($taxRate / 100), 2) : 0;
            ?>
            <tr>
                <td class="text-center"><?= number_format($item['quantity'], 2) ?></td>
                <td class="item-desc"><?= htmlspecialchars($item['description']) ?></td>
                <td class="text-center" style="font-size:10px;">UND</td>
                <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
                <?php if ($taxRate > 0): ?>
                <td class="text-right"><?= number_format($itemItbis, 2) ?></td>
                <?php endif; ?>
                <td class="text-right item-total"><?= number_format($itemAmount, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Payment + Totals -->
    <table class="bottom-area"><tr>
        <td style="width:55%; padding-right:30px;">
            <?php if ($isEcf): ?>
                <?php
                $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
                $rncComprador = preg_replace('/[^0-9]/', '', $client['tax_id'] ?? '');
                $encf = $invoice['encf'] ?? '';
                $monto = '';
                $fechaFirma = '';
                $xmlFechaEmision = '';
                if (!empty($invoice['signed_xml_path'])) {
                    $xmlPath = storage_path('app/private/' . $invoice['signed_xml_path']);
                    if (!file_exists($xmlPath)) {
                        $xmlPath = storage_path('app/' . $invoice['signed_xml_path']);
                    }
                    if (file_exists($xmlPath)) {
                        $xmlContent = file_get_contents($xmlPath);
                        if (preg_match('/<MontoTotal>([^<]+)<\/MontoTotal>/', $xmlContent, $mMonto)) {
                            $monto = number_format((float)$mMonto[1], 2, '.', '');
                        }
                        if (preg_match('/<FechaHoraFirma>([^<]+)<\/FechaHoraFirma>/', $xmlContent, $mFirma)) {
                            $fechaFirma = $mFirma[1];
                        }
                        if (preg_match('/<FechaEmision>([^<]+)<\/FechaEmision>/', $xmlContent, $mEmision)) {
                            $xmlFechaEmision = $mEmision[1];
                        }
                    }
                }

                if (empty($monto)) {
                    $monto = number_format((float)$invoice['total'], 2, '.', '');
                }

                $fechaEmision = $xmlFechaEmision ?: date('d-m-Y', strtotime($invoice['issue_date']));
                $codSeguridad = $invoice['security_code'] ?? '';

                // Fallback to DB signed_at only if XML is unavailable
                if (empty($fechaFirma) && !empty($invoice['signed_at'])) {
                    $fechaFirma = date('d-m-Y H:i:s', strtotime($invoice['signed_at']));
                }
                if (empty($fechaFirma)) {
                    $fechaFirma = date('d-m-Y H:i:s');
                }

                // Determine QR URL based on type — per Informe Técnico DGII pág. 36
                // Environment must match API submission path: testing→testecf, certification→certecf, production→ecf
                $dgiiEnv = $settings['dgii_env'] ?? 'testing';
                $ecfQrPath = match($dgiiEnv) { 'production' => 'ecf', 'certification' => 'certecf', default => 'testecf' };
                $isRfce = $ecfType === 32 && (float)$invoice['total'] < 250000;
                // Types 43 (Gastos Menores) and 47 (Pagos al Exterior) have no RNCComprador
                $hasComprador = !in_array($ecfType, [43, 47]) && (strlen($rncComprador) === 9 || strlen($rncComprador) === 11);

                if ($isRfce) {
                    // FC<250k: fc.dgii.gov.do — params: RncEmisor, ENCF, MontoTotal, CodigoSeguridad
                    $qrUrl = "https://fc.dgii.gov.do/{$ecfQrPath}/ConsultaTimbreFC?"
                        . "RncEmisor={$rncEmisor}"
                        . "&ENCF={$encf}"
                        . "&MontoTotal={$monto}"
                        . "&CodigoSeguridad=" . urlencode($codSeguridad);
                } else {
                    // Regular e-CF: ecf.dgii.gov.do — all params PascalCase
                    $qrUrl = "https://ecf.dgii.gov.do/{$ecfQrPath}/ConsultaTimbre?"
                        . "RncEmisor={$rncEmisor}";
                    if ($hasComprador) {
                        $qrUrl .= "&RncComprador={$rncComprador}";
                    }
                    $qrUrl .= "&ENCF={$encf}"
                        . "&FechaEmision={$fechaEmision}"
                        . "&MontoTotal={$monto}"
                        . "&FechaFirma=" . urlencode($fechaFirma)
                        . "&CodigoSeguridad=" . urlencode($codSeguridad);
                }

                // Generate QR code
                $qrImgSrc = '';
                if (class_exists('\chillerlan\QRCode\QRCode')) {
                    try {
                        $qrOptions = new \chillerlan\QRCode\QROptions();
                        $qrOptions->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
                        $qrOptions->scale = 5;
                        $qrOptions->quietzoneSize = 2;
                        $qrOptions->outputBase64 = true;
                        $qrImgSrc = (new \chillerlan\QRCode\QRCode($qrOptions))->render($qrUrl);
                    } catch (\Throwable $e) {
                        $qrImgSrc = '';
                    }
                }
                if (empty($qrImgSrc)) {
                    $qrImgSrc = "https://quickchart.io/qr?text=" . urlencode($qrUrl) . "&size=150&margin=1&format=png";
                }
                ?>
                <!-- QR Code — per DGII reference model -->
                <div style="margin-bottom:2px; text-align:left; padding-left:5px;">
                    <img src="<?= $qrImgSrc ?>" style="width:105px; height:105px; display:inline-block;" alt="QR DGII">
                </div>
                <!-- Código de Seguridad y Fecha Firma — DEBAJO del QR -->
                <div style="font-size:10px; color:#000000; line-height:1.4; text-align:left;">
                    Código de Seguridad: <?= htmlspecialchars($codSeguridad) ?><br>
                    Fecha Firma: <?= htmlspecialchars($fechaFirma) ?>
                </div>
                <div style="margin-top:15px;"></div>
            <?php endif; ?>
            <?php if (!empty($invoice['notes'])): ?>
                <div class="payment-title">Notas</div>
                <div class="payment-text"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
            <?php endif; ?>
        </td>
        <td style="width:45%;">
            <table class="totals-mini">
                <tr>
                    <td class="tl">SUBTOTAL GRAVADO</td>
                    <td class="tv"><?= number_format($invoice['subtotal'] ?? 0, 2) ?></td>
                </tr>
                <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="tl">DESCUENTO</td>
                    <td class="tv" style="color:<?= $accentColor ?>;">-<?= number_format($invoice['discount_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="tl">TOTAL ITBIS</td>
                    <td class="tv"><?= number_format($invoice['tax_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand">
                    <td class="tl">TOTAL</td>
                    <td class="tv"><?= number_format($invoice['total'] ?? 0, 2) ?></td>
                </tr>
                <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
                <tr class="paid-row">
                    <td class="tl">PAGADO</td>
                    <td class="tv" style="color:#059669;">-<?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> <?= number_format($invoice['amount_paid'], 2) ?></td>
                </tr>
                <tr class="balance-row">
                    <td class="tl">SALDO</td>
                    <td class="tv"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> <?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($invoice['currency']) && $invoice['currency'] !== 'DOP' && !empty($invoice['exchange_rate']) && $invoice['exchange_rate'] != 1): ?>
                <tr>
                    <td colspan="2" style="text-align:right; font-size:10px; color:#666; padding-top:10px; border-top: 1px dashed #E0E0E0;">
                        Tasa de Cambio: <?= number_format($invoice['exchange_rate'], 4) ?><br>
                        <strong>Equivalente: DOP <?= number_format($invoice['total'] * $invoice['exchange_rate'], 2) ?></strong>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </td>
    </tr></table>

    <!-- Terms -->
    <?php if (!empty($invoice['terms'])): ?>
    <div class="terms-section">
        <div class="terms-title">Términos y Condiciones</div>
        <div class="terms-line"></div>
        <div class="terms-text"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></div>
    </div>
    <?php endif; ?>

</div>

<!-- FOOTER -->
<?php if ($showFooter): ?>
<div class="footer">
    <div class="footer-contact">
        <?php if (!empty($company['website'])): ?>
            <?= htmlspecialchars($company['website']) ?>
        <?php endif; ?>
        <?php if (!empty($company['email'])): ?>
            <span>&bull;</span><?= htmlspecialchars($company['email']) ?>
        <?php endif; ?>
        <?php if (!empty($company['phone'])): ?>
            <span>&bull;</span><?= htmlspecialchars($company['phone']) ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</body>
</html>
