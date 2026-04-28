<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isQuote ?? false ? 'Cotización' : 'Factura' }} <?= htmlspecialchars($invoice['invoice_number'] ?? $invoice['quote_number'] ?? '') ?></title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        /* === MAIN LAYOUT === */
        .page-wrapper {
            width: 100%;
            min-height: 100%;
            position: relative;
        }

        /* === DARK SIDEBAR === */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: 100%;
            background: #1A1D26;
            color: #ffffff;
            padding: 40px 25px 30px 25px;
        }
        .sidebar-logo {
            margin-bottom: 30px;
        }
        .sidebar-logo img {
            max-width: 160px;
            height: auto;
        }
        .sidebar-divider {
            width: 40px;
            height: 3px;
            background: #D4832F;
            margin: 20px 0;
        }
        .sidebar-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #D4832F;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .sidebar-value {
            font-size: 12px;
            color: #E0E0E0;
            margin-bottom: 4px;
            line-height: 1.5;
        }
        .sidebar-value strong {
            font-size: 14px;
            color: #ffffff;
        }
        .sidebar-section {
            margin-bottom: 25px;
        }

        /* Payment method section */
        .payment-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #2D3140;
        }
        .payment-row {
            display: flex;
            margin-bottom: 8px;
        }
        .payment-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #D4832F;
            border-radius: 50%;
            margin-right: 8px;
            margin-top: 5px;
        }

        /* === MAIN CONTENT === */
        .main-content {
            margin-left: 220px;
            padding: 40px 35px 30px 35px;
        }

        /* Document header */
        .doc-header {
            margin-bottom: 5px;
        }
        .doc-title {
            font-size: 36px;
            font-weight: 900;
            color: #1A1D26;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 0;
        }
        .doc-number-line {
            font-size: 11px;
            color: #888;
            margin-bottom: 10px;
        }

        /* Orange accent corner */
        .accent-corner {
            position: fixed;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
        }
        .accent-arc {
            position: absolute;
            border: 8px solid #D4832F;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            top: -30px;
            right: -30px;
        }

        /* Total Due Box */
        .total-due-box {
            background: #FDF5EC;
            border-left: 4px solid #D4832F;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        .total-due-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .total-due-amount {
            font-size: 28px;
            font-weight: 900;
            color: #1A1D26;
        }
        .total-due-currency {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }

        /* === ITEMS TABLE === */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead tr {
            background: #D4832F;
        }
        .items-table th {
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table tbody tr {
            border-bottom: 1px solid #F0F0F0;
        }
        .items-table tbody tr:nth-child(even) {
            background: #FAFAFA;
        }
        .items-table td {
            padding: 12px 12px;
            vertical-align: top;
        }
        .item-number {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: #D4832F;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            line-height: 28px;
            border-radius: 4px;
        }
        .item-desc {
            font-weight: 600;
            color: #1A1D26;
            font-size: 12px;
        }
        .item-detail {
            font-size: 10px;
            color: #888;
            margin-top: 2px;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        /* === TOTALS === */
        .totals-wrapper {
            width: 100%;
            margin-bottom: 25px;
        }
        .totals-table {
            width: 250px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 11px;
        }
        .totals-table .total-label {
            text-align: right;
            color: #666;
        }
        .totals-table .total-value {
            text-align: right;
            font-weight: 600;
            width: 100px;
        }
        .grand-total-row {
            background: #D4832F;
        }
        .grand-total-row td {
            color: #ffffff !important;
            font-size: 14px !important;
            font-weight: 800 !important;
            padding: 10px 10px !important;
        }

        /* Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .badge-paid { background: #E8F5E9; color: #2E7D32; }
        .badge-overdue { background: #FFEBEE; color: #C62828; }
        .badge-draft { background: #F5F5F5; color: #666; }
        .badge-sent { background: #E3F2FD; color: #1565C0; }

        /* Terms & Notes */
        .terms-section {
            clear: both;
            padding-top: 15px;
            border-top: 1px solid #E0E0E0;
            margin-top: 10px;
        }
        .terms-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #1A1D26;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .terms-text {
            font-size: 10px;
            color: #666;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 15px;
            right: 35px;
            left: 255px;
            text-align: center;
            font-size: 9px;
            color: #AAA;
            border-top: 1px solid #E8E8E8;
            padding-top: 8px;
        }

        .clear { clear: both; }
    </style>
</head>
<body>

<?php
$isQuote = isset($is_quote) && $is_quote;
$docName = $isQuote ? 'Cotización' : 'Factura';
$docNum  = $isQuote ? ($invoice['quote_number'] ?? '') : ($invoice['invoice_number'] ?? '');
$dateLabel = $isQuote ? 'Válida Hasta' : 'Vencimiento';
$dateField = $isQuote ? ($invoice['expiry_date'] ?? $invoice['due_date'] ?? '') : ($invoice['due_date'] ?? '');

// Logo
$logoUrl = 'https://gridbase.com.do/wp-content/uploads/2025/02/cropped-imagen_2026-03-16_154126791.png';
$logoPath = public_path('assets/img/logo.png');
$logoData = '';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
}
?>

<div class="page-wrapper">

    <!-- ACCENT CORNER -->
    <div class="accent-corner">
        <div class="accent-arc"></div>
    </div>

    <!-- DARK SIDEBAR -->
    <div class="sidebar">
        <!-- Logo -->
        <div class="sidebar-logo">
            <?php if ($logoData): ?>
                <img src="data:image/png;base64,<?= $logoData ?>" alt="GridBase">
            <?php else: ?>
                <div style="font-size: 22px; font-weight: bold; color: #D4832F;">
                    Grid<span style="color: #fff;">Base</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="sidebar-divider"></div>

        <!-- Billed To -->
        <div class="sidebar-section">
            <div class="sidebar-label">Facturado a</div>
            <div class="sidebar-value">
                <strong><?= htmlspecialchars($client['company_name'] ?: $client['contact_name']) ?></strong>
            </div>
            <?php if (!empty($client['company_name']) && !empty($client['contact_name'])): ?>
                <div class="sidebar-value"><?= htmlspecialchars($client['contact_name']) ?></div>
            <?php endif; ?>
            <?php if (!empty($client['phone'])): ?>
                <div class="sidebar-value">T: <?= htmlspecialchars($client['phone']) ?></div>
            <?php endif; ?>
            <?php if (!empty($client['email'])): ?>
                <div class="sidebar-value"><?= htmlspecialchars($client['email']) ?></div>
            <?php endif; ?>
            <?php if (!empty($client['address_line1'])): ?>
                <div class="sidebar-value"><?= htmlspecialchars($client['address_line1']) ?></div>
            <?php endif; ?>
            <?php if (!empty($client['tax_id'])): ?>
                <div class="sidebar-value">RNC: <?= htmlspecialchars($client['tax_id']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Payment Method / Company Info -->
        <div class="payment-section">
            <div class="sidebar-label">Información de pago</div>
            <?php if (!empty($company['name'])): ?>
                <div class="sidebar-value" style="margin-top: 8px;">
                    <span class="payment-dot"></span>
                    <span style="font-size: 10px; color: #999;">Empresa</span><br>
                    <span style="margin-left: 14px;"><?= htmlspecialchars($company['name']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['tax_id'])): ?>
                <div class="sidebar-value">
                    <span class="payment-dot"></span>
                    <span style="font-size: 10px; color: #999;">RNC</span><br>
                    <span style="margin-left: 14px;"><?= htmlspecialchars($company['tax_id']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['email'])): ?>
                <div class="sidebar-value">
                    <span class="payment-dot"></span>
                    <span style="font-size: 10px; color: #999;">Email</span><br>
                    <span style="margin-left: 14px;"><?= htmlspecialchars($company['email']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['phone'])): ?>
                <div class="sidebar-value">
                    <span class="payment-dot"></span>
                    <span style="font-size: 10px; color: #999;">Teléfono</span><br>
                    <span style="margin-left: 14px;"><?= htmlspecialchars($company['phone']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Document Title -->
        <div class="doc-header">
            <div class="doc-title"><?= $docName ?></div>
            <div class="doc-number-line">
                N° <?= htmlspecialchars($docNum) ?>
                &nbsp;&bull;&nbsp;
                Fecha: <?= !empty($invoice['issue_date']) ? date('d/m/Y', strtotime($invoice['issue_date'])) : '' ?>
                &nbsp;&bull;&nbsp;
                <?= $dateLabel ?>: <strong><?= !empty($dateField) ? date('d/m/Y', strtotime($dateField)) : '' ?></strong>
                <?php if (!$isQuote && !empty($invoice['status'])): ?>
                    &nbsp;&nbsp;
                    <?php
                    $badgeClass = 'badge-draft';
                    $badgeText = ucfirst($invoice['status']);
                    if ($invoice['status'] === 'paid') { $badgeClass = 'badge-paid'; $badgeText = 'PAGADA'; }
                    elseif ($invoice['status'] === 'overdue') { $badgeClass = 'badge-overdue'; $badgeText = 'VENCIDA'; }
                    elseif ($invoice['status'] === 'sent') { $badgeClass = 'badge-sent'; $badgeText = 'ENVIADA'; }
                    elseif ($invoice['status'] === 'draft') { $badgeText = 'BORRADOR'; }
                    ?>
                    <span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Total Due Box -->
        <div class="total-due-box">
            <div class="total-due-label">Monto Total</div>
            <div class="total-due-amount">
                <span class="total-due-currency"><?= htmlspecialchars($invoice['currency'] ?? 'USD') ?> :</span>
                $<?= number_format($invoice['total'] ?? 0, 2) ?>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;">Cant</th>
                    <th>Producto / Servicio</th>
                    <th class="text-right" style="width: 90px;">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($invoice['items'] ?? $items ?? []) as $index => $item): ?>
                <tr>
                    <td class="text-center">
                        <span class="item-number"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></span>
                    </td>
                    <td>
                        <div class="item-desc"><?= htmlspecialchars($item['description']) ?></div>
                        <div class="item-detail"><?= number_format($item['quantity'], 0) ?> × $<?= number_format($item['unit_price'], 2) ?></div>
                    </td>
                    <td class="text-right" style="font-weight: 600;">$<?= number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-wrapper">
            <table class="totals-table">
                <tr>
                    <td class="total-label">Subtotal</td>
                    <td class="total-value">$<?= number_format($invoice['subtotal'] ?? 0, 2) ?></td>
                </tr>
                <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="total-label">Descuento</td>
                    <td class="total-value">-$<?= number_format($invoice['discount_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="total-label">ITBIS (<?= number_format($invoice['tax_rate'] ?? 0, 0) ?>%)</td>
                    <td class="total-value">$<?= number_format($invoice['tax_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total-row">
                    <td style="text-align: right;">Total</td>
                    <td style="text-align: right;"><?= htmlspecialchars($invoice['currency'] ?? 'USD') ?> $<?= number_format($invoice['total'] ?? 0, 2) ?></td>
                </tr>
                <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
                <tr>
                    <td class="total-label" style="padding-top: 10px;">Pagado</td>
                    <td class="total-value" style="padding-top: 10px; color: #2E7D32;">-$<?= number_format($invoice['amount_paid'], 2) ?></td>
                </tr>
                <tr>
                    <td class="total-label" style="font-weight: 800; color: #1A1D26;">Balance</td>
                    <td class="total-value" style="font-weight: 800; color: #C62828;">$<?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="clear"></div>

        <!-- Notes & Terms -->
        <?php if (!empty($invoice['notes']) || !empty($invoice['terms'])): ?>
        <div class="terms-section">
            <?php if (!empty($invoice['notes'])): ?>
                <div class="terms-title">Notas</div>
                <div class="terms-text"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($invoice['terms'])): ?>
                <div class="terms-title" style="margin-top: 12px;">Términos y Condiciones</div>
                <div class="terms-text"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <div class="footer">
        <?= htmlspecialchars($company['name'] ?? 'Gridbase') ?>
        <?php if (!empty($company['website'])) echo " &bull; " . htmlspecialchars($company['website']); ?>
        <?php if (!empty($company['email'])) echo " &bull; " . htmlspecialchars($company['email']); ?>
        <?php if (!empty($company['phone'])) echo " &bull; " . htmlspecialchars($company['phone']); ?>
    </div>

</div>

</body>
</html>
