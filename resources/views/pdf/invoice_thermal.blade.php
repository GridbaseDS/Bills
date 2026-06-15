<?php
if (!function_exists('getMonochromeLogo')) {
    function getMonochromeLogo($url) {
        if (empty($url)) return '';
        try {
            $imageContent = @file_get_contents($url);
            if (!$imageContent) return $url;
            
            $im = @imagecreatefromstring($imageContent);
            if (!$im) return $url;
            
            $width = imagesx($im);
            $height = imagesy($im);
            
            $isPng = (strpos($url, '.png') !== false || substr($imageContent, 1, 3) === 'PNG');
            
            if ($isPng) {
                $newImg = imagecreatetruecolor($width, $height);
                imagealphablending($newImg, false);
                imagesavealpha($newImg, true);
                $transparent = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
                imagefill($newImg, 0, 0, $transparent);
                
                for ($x = 0; $x < $width; $x++) {
                    for ($y = 0; $y < $height; $y++) {
                        $rgba = imagecolorat($im, $x, $y);
                        $colors = imagecolorsforindex($im, $rgba);
                        $alpha = $colors['alpha'];
                        
                        if ($alpha < 127) {
                            $black = imagecolorallocatealpha($newImg, 0, 0, 0, $alpha);
                            imagesetpixel($newImg, $x, $y, $black);
                        }
                    }
                }
                $renderImg = $newImg;
            } else {
                imagefilter($im, IMG_FILTER_GRAYSCALE);
                imagefilter($im, IMG_FILTER_CONTRAST, -30);
                $renderImg = $im;
            }
            
            ob_start();
            imagepng($renderImg);
            $imageData = ob_get_clean();
            
            imagedestroy($im);
            if ($isPng) {
                imagedestroy($renderImg);
            }
            
            return 'data:image/png;base64,' . base64_encode($imageData);
        } catch (\Throwable $e) {
            return $url;
        }
    }
}

$isQuote   = isset($is_quote) && $is_quote;
$isEcf     = !$isQuote && ($invoice['is_ecf'] ?? false);
$ecfType   = (int)($invoice['ecf_type'] ?? 32);
$docName   = $isQuote ? 'Cotización' : ($isEcf ? 'e-CF' : 'Factura');
$docNum    = $isQuote ? ($invoice['quote_number'] ?? '') : ($isEcf ? ($invoice['encf'] ?? '') : ($invoice['invoice_number'] ?? ''));
$dateLabel = $isQuote ? 'Validez' : 'Vence';
$dateField = $isQuote ? ($invoice['expiry_date'] ?? $invoice['due_date'] ?? '') : ($invoice['due_date'] ?? '');

$showFechaVencimiento = $isEcf && !in_array($ecfType, [32, 34]);
$fechaVencimientoSeq  = $settings['dgii_ncf_expiry_date'] ?? '31/12/2028';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimientoSeq)) {
    $fechaVencimientoSeq = date('d/m/Y', strtotime($fechaVencimientoSeq));
}

// ECF type names
$ecfTypeNames = [
    31 => 'Factura Crédito Fiscal Electrónica',
    32 => 'Factura Consumo Electrónica',
    33 => 'Nota de Débito Electrónica',
    34 => 'Nota de Crédito Electrónica',
    41 => 'Comprobante de Compras Electrónico',
    43 => 'Gastos Menores Electrónico',
    44 => 'Regímenes Especiales Electrónico',
    45 => 'Comprobante Gubernamental Electrónico',
    46 => 'Comprobante de Exportaciones',
    47 => 'Pagos al Exterior Electrónico',
];
$ecfTypeName = $isEcf ? ($ecfTypeNames[$ecfType] ?? 'Comprobante Electrónico') : $docName;

$badgeText = 'BORRADOR';
if (!empty($invoice['status'])) {
    if ($invoice['status'] === 'paid')    { $badgeText = 'PAGADA'; }
    elseif ($invoice['status'] === 'overdue') { $badgeText = 'VENCIDA'; }
    elseif ($invoice['status'] === 'sent' || $invoice['status'] === 'pending') { $badgeText = 'PENDIENTE'; }
    elseif ($invoice['status'] === 'partial') { $badgeText = 'PAGO PARCIAL'; }
}

// Calculate page height dynamically in mm
$itemCount = count($invoice['items'] ?? $items ?? []);
$baseHeight = 85;

$itemsHeight = 0;
foreach (($invoice['items'] ?? $items ?? []) as $item) {
    $descLength = strlen($item['description'] ?? '');
    $lines = max(1, ceil($descLength / 16));
    $itemsHeight += $lines * 4.5 + 4;
}

$totalsHeight = 15;
if (($invoice['discount_amount'] ?? 0) > 0) { $totalsHeight += 4; }
if (($invoice['tax_amount'] ?? 0) > 0) { $totalsHeight += 4; }
if (($invoice['amount_paid'] ?? 0) > 0) { $totalsHeight += 8; }
if (!empty($invoice['currency']) && $invoice['currency'] !== 'DOP') { $totalsHeight += 8; }

$notesHeight = 0;
if (!empty($invoice['notes'])) {
    $notesHeight += ceil(strlen($invoice['notes']) / 30) * 4 + 6;
}
if (!empty($invoice['terms'])) {
    $notesHeight += ceil(strlen($invoice['terms']) / 30) * 4 + 6;
}

$dgiiHeight = 0;
if (!$isQuote && $isEcf) {
    $dgiiHeight = 55;
}

$pageHeight = $baseHeight + $itemsHeight + $totalsHeight + $notesHeight + $dgiiHeight;
$pageHeight = max(120, $pageHeight + 10);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isQuote ?? false ? 'Cotización' : 'Factura' }}</title>
    <style>
        @page {
            size: 80mm {{ $pageHeight }}mm;
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier', 'DejaVu Sans', monospace, sans-serif;
            font-size: 8px;
            color: #000000;
            background: #FFFFFF;
            line-height: 1.3;
            margin: 4mm 4mm 6mm 4mm;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        
        .divider {
            border-top: 1px dashed #000000;
            margin: 5px 0;
        }

        /* ── HEADER ── */
        .company-name {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .company-info {
            font-size: 8px;
            margin-bottom: 4px;
        }

        /* ── DOCUMENT INFO ── */
        .doc-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 4px 0 2px 0;
        }
        .meta-info {
            font-size: 8px;
            margin-bottom: 4px;
        }

        /* ── CLIENT INFO ── */
        .client-info {
            font-size: 8px;
            margin-bottom: 5px;
        }

        /* ── TABLE ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        .items-table th {
            font-size: 8px;
            font-weight: bold;
            border-bottom: 1px dashed #000000;
            padding: 3px 0;
            text-align: left;
        }
        .items-table td {
            font-size: 8px;
            padding: 4px 0;
            vertical-align: top;
        }
        .items-table td.item-desc {
            word-wrap: break-word;
            max-width: 35mm;
        }

        /* ── TOTALS ── */
        .totals-table {
            width: 100%;
            margin-top: 5px;
        }
        .totals-table td {
            font-size: 8px;
            padding: 2px 0;
        }
        .totals-table .label {
            text-align: right;
            padding-right: 8px;
        }
        .totals-table .val {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }
        .balance-row td {
            border-top: 1px dashed #000000;
            padding-top: 4px;
            font-size: 9px;
        }

        /* ── FOOTER & QR ── */
        .qr-section {
            margin: 10px 0;
            text-align: center;
        }
        .qr-section img {
            width: 30mm;
            height: 30mm;
            display: inline-block;
        }
        .security-info {
            font-size: 7.5px;
            margin-top: 4px;
            line-height: 1.4;
        }
        .footer-thanks {
            font-size: 8px;
            margin-top: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div style="height: 4mm; width: 100%;"></div>


<!-- ── EMISOR (COMPANY) ── -->
<div class="text-center">
    @if(!empty($settings['company_logo']) || !empty($settings['pdf_logo_url']))
        <div style="margin-bottom: 4px;">
            <img src="{{ getMonochromeLogo($settings['company_logo'] ?: $settings['pdf_logo_url']) }}" style="max-width: 40mm; max-height: 12mm; display: inline-block; object-fit: contain;" alt="Logo">
        </div>
    @else
        <div class="company-name">{{ $company['name'] ?? 'GridBase' }}</div>
    @endif
    @if(!empty($settings['dgii_razon_social']) && $settings['dgii_razon_social'] !== ($company['name'] ?? ''))
        <div class="company-info">{{ htmlspecialchars($settings['dgii_razon_social']) }}</div>
    @endif
    <div class="company-info">
        @if(!empty($company['tax_id']))
            RNC: {{ htmlspecialchars($company['tax_id']) }}<br>
        @endif
        @if(!empty($company['address']))
            {{ htmlspecialchars($company['address']) }}{{ !empty($company['city']) ? ', ' . htmlspecialchars($company['city']) : '' }}<br>
        @endif
        @if(!empty($company['phone']))
            Tel: {{ htmlspecialchars($company['phone']) }}<br>
        @endif
        @if(!empty($company['email']))
            Email: {{ htmlspecialchars($company['email']) }}
        @endif
    </div>
</div>

<div class="divider"></div>

<!-- ── DOCUMENT INFO ── -->
<div class="text-center">
    <div class="doc-title">{{ strtoupper($ecfTypeName) }}</div>
    <div class="meta-info">
        @if($isQuote)
            <span class="bold">Cotización No:</span> {{ htmlspecialchars($docNum) }}<br>
        @else
            <span class="bold">{{ $isEcf ? 'e-NCF' : 'No. Factura' }}:</span> {{ htmlspecialchars($docNum) }}<br>
        @endif
        <span class="bold">Fecha Emisión:</span> {{ !empty($invoice['issue_date']) ? date('d-m-Y', strtotime($invoice['issue_date'])) : '' }}<br>
        <span class="bold">{{ $dateLabel }}:</span> {{ !empty($dateField) ? date('d-m-Y', strtotime($dateField)) : '' }}<br>
        @if($showFechaVencimiento)
            <span class="bold">Vence Secuencia:</span> {{ htmlspecialchars($fechaVencimientoSeq) }}<br>
        @endif
        @if($isEcf && in_array($ecfType, [33, 34]) && !empty($invoice['modified_ncf']))
            <span class="bold">NCF Modificado:</span> {{ htmlspecialchars($invoice['modified_ncf']) }}<br>
        @endif
        @if(!$isQuote)
            <span class="bold">Estado:</span> {{ $badgeText }}
        @endif
    </div>
</div>

<div class="divider"></div>

<!-- ── RECEPTOR (CLIENT) ── -->
<?php
$clientName = trim($client['company_name'] ?? '') ?: trim($client['contact_name'] ?? '');
$clientRnc = trim($client['tax_id'] ?? '');
$hasComprador = !in_array($ecfType, [43, 47]);
?>
@if($hasComprador && (!empty($clientName) || !empty($clientRnc)))
<div class="client-info">
    <span class="bold">CLIENTE:</span> {{ htmlspecialchars($clientName) }}<br>
    @if(!empty($clientRnc))
        <span class="bold">RNC/CÉDULA:</span> {{ htmlspecialchars($clientRnc) }}<br>
    @endif
</div>
<div class="divider"></div>
@endif

<!-- ── ITEMS ── -->
<?php $taxRate = (float)($invoice['tax_rate'] ?? 0); ?>
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 10%;">CANT</th>
            <th style="width: 44%;">DESC</th>
            <th style="width: 23%; text-align: right;">PRECIO</th>
            <th style="width: 23%; text-align: right;">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach (($invoice['items'] ?? $items ?? []) as $item)
            <?php
                $itemAmount = (float)($item['amount'] ?? ($item['quantity'] * $item['unit_price']));
            ?>
            <tr>
                <td style="white-space: nowrap;">{{ number_format($item['quantity'], 1) }}</td>
                <td class="item-desc">{{ htmlspecialchars($item['description']) }}</td>
                <td class="text-right" style="white-space: nowrap;">{{ number_format($item['unit_price'], 2) }}</td>
                <td class="text-right bold" style="white-space: nowrap;">{{ number_format($itemAmount, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="divider"></div>

<!-- ── TOTALS ── -->
<table class="totals-table">
    <tr>
        <td class="label">SUBTOTAL</td>
        <td class="val">{{ number_format($invoice['subtotal'] ?? 0, 2) }}</td>
    </tr>
    @if (($invoice['discount_amount'] ?? 0) > 0)
    <tr>
        <td class="label">DESCUENTO</td>
        <td class="val">-{{ number_format($invoice['discount_amount'], 2) }}</td>
    </tr>
    @endif
    @if (($invoice['tax_amount'] ?? 0) > 0)
    <tr>
        <td class="label">ITBIS ({{ number_format($taxRate) }}%)</td>
        <td class="val">{{ number_format($invoice['tax_amount'], 2) }}</td>
    </tr>
    @endif
    <tr class="balance-row">
        <td class="label bold">TOTAL</td>
        <td class="val">{{ number_format($invoice['total'] ?? 0, 2) }} {{ $invoice['currency'] ?? 'DOP' }}</td>
    </tr>
    @if(!$isQuote && ($invoice['amount_paid'] ?? 0) > 0)
    <tr>
        <td class="label">PAGADO</td>
        <td class="val" style="color: #000;">-{{ number_format($invoice['amount_paid'], 2) }}</td>
    </tr>
    <tr>
        <td class="label bold">PENDIENTE</td>
        <td class="val">{{ number_format($invoice['total'] - $invoice['amount_paid'], 2) }} {{ $invoice['currency'] ?? 'DOP' }}</td>
    </tr>
    @endif
    @if(!empty($invoice['currency']) && $invoice['currency'] !== 'DOP' && !empty($invoice['exchange_rate']) && $invoice['exchange_rate'] != 1)
    <tr>
        <td colspan="2" class="text-right" style="font-size: 7.5px; padding-top: 4px;">
            Tasa: {{ number_format($invoice['exchange_rate'], 4) }}<br>
            Equiv: DOP {{ number_format($invoice['total'] * $invoice['exchange_rate'], 2) }}
        </td>
    </tr>
    @endif
</table>

<!-- ── NOTES & TERMS ── -->
@if(!empty($invoice['notes']))
    <div class="divider"></div>
    <div class="bold" style="font-size: 8px;">Notas:</div>
    <div style="font-size: 7.5px; padding: 2px 0;">{{ htmlspecialchars($invoice['notes']) }}</div>
@endif

@if(!empty($invoice['terms']))
    <div class="divider"></div>
    <div class="bold" style="font-size: 8px;">Términos:</div>
    <div style="font-size: 7.5px; padding: 2px 0; font-style: italic;">{{ htmlspecialchars($invoice['terms']) }}</div>
@endif

<!-- ── DGII QR & SECURITY CODE ── -->
@if($isEcf)
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

    if (empty($fechaFirma) && !empty($invoice['signed_at'])) {
        $fechaFirma = date('d-m-Y H:i:s', strtotime($invoice['signed_at']));
    }
    if (empty($fechaFirma)) {
        $fechaFirma = date('d-m-Y H:i:s');
    }

    $dgiiEnv = $settings['dgii_env'] ?? 'testing';
    $ecfQrPath = match($dgiiEnv) { 'production' => 'ecf', 'certification' => 'certecf', default => 'testecf' };
    $isRfce = $ecfType === 32 && (float)$invoice['total'] < 250000;
    $hasComprador = !in_array($ecfType, [43, 47]);

    if ($isRfce) {
        $qrUrl = "https://fc.dgii.gov.do/{$ecfQrPath}/ConsultaTimbreFC?"
            . "RncEmisor={$rncEmisor}"
            . "&ENCF={$encf}"
            . "&MontoTotal={$monto}"
            . "&CodigoSeguridad=" . urlencode($codSeguridad);
    } else {
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

    $qrImgSrc = '';
    if (class_exists('\chillerlan\QRCode\QRCode')) {
        try {
            $qrOptions = new \chillerlan\QRCode\QROptions();
            $qrOptions->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
            $qrOptions->scale = 4;
            $qrOptions->quietzoneSize = 1;
            $qrOptions->outputBase64 = true;
            $qrImgSrc = (new \chillerlan\QRCode\QRCode($qrOptions))->render($qrUrl);
        } catch (\Throwable $e) {
            $qrImgSrc = '';
        }
    }
    if (empty($qrImgSrc)) {
        $qrImgSrc = "https://quickchart.io/qr?text=" . urlencode($qrUrl) . "&size=120&margin=1&format=png";
    }
    ?>
    <div class="divider"></div>
    <div class="qr-section">
        <img src="<?= $qrImgSrc ?>" alt="QR DGII">
        <div class="security-info">
            <span class="bold">Código de Seguridad:</span> {{ htmlspecialchars($codSeguridad) }}<br>
            <span class="bold">Fecha de Firma:</span> {{ htmlspecialchars($fechaFirma) }}<br>
            <div style="font-size: 6.5px; margin-top: 3px; color: #555;">Representación impresa de e-CF</div>
        </div>
    </div>
@endif

<div class="divider"></div>

<div class="text-center footer-thanks">
    ¡Gracias por su compra!<br>
    <span style="font-size: 7px; font-weight: normal; text-transform: none;">GridBase Digital Solutions</span>
</div>
<div style="height: 6mm; width: 100%;"></div>

</body>
</html>
