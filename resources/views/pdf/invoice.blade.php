<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isQuote ?? false ? 'Cotización' : 'Factura' }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #E0E8E0;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #0B1F1A;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 210px;
            height: 100%;
            background: #081A15;
            padding: 35px 22px 25px 22px;
            border-right: 1px solid rgba(180, 231, 23, 0.08);
        }
        .sidebar-logo {
            margin-bottom: 25px;
            text-align: center;
        }
        .sidebar-logo img {
            max-width: 150px;
            height: auto;
        }
        .sidebar-divider {
            width: 35px;
            height: 2px;
            background: #B4E717;
            margin: 18px 0;
            border-radius: 2px;
        }
        .sidebar-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #B4E717;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .sidebar-value {
            font-size: 10px;
            color: #8BA899;
            margin-bottom: 3px;
            line-height: 1.5;
        }
        .sidebar-value strong {
            font-size: 12px;
            color: #F0F5F0;
        }
        .sidebar-section {
            margin-bottom: 20px;
        }
        .sidebar-sep {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(180, 231, 23, 0.08);
        }
        .sidebar-dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            background: #00D690;
            border-radius: 50%;
            margin-right: 6px;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            margin-left: 210px;
            padding: 35px 30px 25px 30px;
            background: #0B1F1A;
            min-height: 100%;
        }

        /* ── HEADER ── */
        .doc-title {
            font-size: 32px;
            font-weight: 900;
            color: #F0F5F0;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 0;
        }
        .doc-number-line {
            font-size: 10px;
            color: #5C7A6A;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }
        .doc-number-line strong {
            color: #B4E717;
        }

        /* ── ACCENT CORNER ── */
        .accent-corner {
            position: fixed;
            top: 0;
            right: 0;
            width: 90px;
            height: 90px;
        }
        .accent-arc {
            position: absolute;
            border: 6px solid rgba(180, 231, 23, 0.2);
            border-radius: 50%;
            width: 110px;
            height: 110px;
            top: -30px;
            right: -30px;
        }
        .accent-arc-inner {
            position: absolute;
            border: 4px solid rgba(0, 214, 144, 0.15);
            border-radius: 50%;
            width: 70px;
            height: 70px;
            top: -10px;
            right: -10px;
        }

        /* ── TOTAL DUE BOX ── */
        .total-due-box {
            background: rgba(180, 231, 23, 0.06);
            border: 1px solid rgba(180, 231, 23, 0.12);
            border-left: 3px solid #B4E717;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .total-due-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #5C7A6A;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .total-due-amount {
            font-size: 26px;
            font-weight: 900;
            color: #F0F5F0;
        }
        .total-due-currency {
            font-size: 12px;
            color: #8BA899;
            font-weight: normal;
        }

        /* ── ITEMS TABLE ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .items-table thead tr {
            background: linear-gradient(135deg, #B4E717 0%, #9ACC10 100%);
        }
        .items-table th {
            color: #0B1F1A;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 9px 10px;
            text-align: left;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table tbody tr {
            border-bottom: 1px solid rgba(180, 231, 23, 0.06);
        }
        .items-table tbody tr:nth-child(even) {
            background: rgba(180, 231, 23, 0.02);
        }
        .items-table td {
            padding: 10px 10px;
            vertical-align: top;
            color: #E0E8E0;
        }
        .item-number {
            display: inline-block;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #B4E717 0%, #9ACC10 100%);
            color: #0B1F1A;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            line-height: 24px;
            border-radius: 4px;
        }
        .item-desc {
            font-weight: 600;
            color: #F0F5F0;
            font-size: 11px;
        }
        .item-detail {
            font-size: 9px;
            color: #5C7A6A;
            margin-top: 2px;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        /* ── TOTALS ── */
        .totals-wrapper {
            width: 100%;
            margin-bottom: 20px;
        }
        .totals-table {
            width: 230px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 8px;
            font-size: 10px;
        }
        .totals-table .total-label {
            text-align: right;
            color: #5C7A6A;
        }
        .totals-table .total-value {
            text-align: right;
            font-weight: 600;
            width: 90px;
            color: #E0E8E0;
        }
        .grand-total-row {
            background: linear-gradient(135deg, #B4E717 0%, #9ACC10 100%);
        }
        .grand-total-row td {
            color: #0B1F1A !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            padding: 9px 8px !important;
            border-radius: 4px;
        }

        /* ── STATUS BADGE ── */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .badge-paid { background: rgba(52, 211, 153, 0.15); color: #34D399; }
        .badge-overdue { background: rgba(251, 113, 133, 0.15); color: #FB7185; }
        .badge-draft { background: rgba(92, 122, 106, 0.2); color: #8BA899; }
        .badge-sent { background: rgba(56, 189, 248, 0.15); color: #38BDF8; }

        /* ── TERMS ── */
        .terms-section {
            clear: both;
            padding-top: 12px;
            border-top: 1px solid rgba(180, 231, 23, 0.06);
            margin-top: 8px;
        }
        .terms-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #B4E717;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .terms-text {
            font-size: 9px;
            color: #5C7A6A;
            line-height: 1.6;
        }

        /* ── FOOTER ── */
        .footer {
            position: fixed;
            bottom: 12px;
            right: 30px;
            left: 240px;
            text-align: center;
            font-size: 8px;
            color: #5C7A6A;
            border-top: 1px solid rgba(180, 231, 23, 0.06);
            padding-top: 6px;
        }
        .footer-accent {
            color: #B4E717;
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
        <div class="accent-arc-inner"></div>
    </div>

    <!-- DARK SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <?php if ($logoData): ?>
                <img src="data:image/png;base64,<?= $logoData ?>" alt="GridBase">
            <?php else: ?>
                <div style="font-size: 20px; font-weight: bold; color: #B4E717;">
                    Grid<span style="color: #F0F5F0;">Base</span>
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

        <!-- Company Info -->
        <div class="sidebar-sep">
            <div class="sidebar-label">Información de pago</div>
            <?php if (!empty($company['name'])): ?>
                <div class="sidebar-value" style="margin-top: 6px;">
                    <span class="sidebar-dot"></span>
                    <span style="font-size: 9px; color: #5C7A6A;">Empresa</span><br>
                    <span style="margin-left: 11px; color: #E0E8E0;"><?= htmlspecialchars($company['name']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['tax_id'])): ?>
                <div class="sidebar-value">
                    <span class="sidebar-dot"></span>
                    <span style="font-size: 9px; color: #5C7A6A;">RNC</span><br>
                    <span style="margin-left: 11px; color: #E0E8E0;"><?= htmlspecialchars($company['tax_id']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['email'])): ?>
                <div class="sidebar-value">
                    <span class="sidebar-dot"></span>
                    <span style="font-size: 9px; color: #5C7A6A;">Email</span><br>
                    <span style="margin-left: 11px; color: #E0E8E0;"><?= htmlspecialchars($company['email']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['phone'])): ?>
                <div class="sidebar-value">
                    <span class="sidebar-dot"></span>
                    <span style="font-size: 9px; color: #5C7A6A;">Teléfono</span><br>
                    <span style="margin-left: 11px; color: #E0E8E0;"><?= htmlspecialchars($company['phone']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <div class="doc-header">
            <div class="doc-title"><?= $docName ?></div>
            <div class="doc-number-line">
                N° <strong><?= htmlspecialchars($docNum) ?></strong>
                &nbsp;·&nbsp;
                Emisión: <?= !empty($invoice['issue_date']) ? date('d/m/Y', strtotime($invoice['issue_date'])) : '' ?>
                &nbsp;·&nbsp;
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

        <!-- Total Due -->
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
                    <th style="width: 36px;">Cant</th>
                    <th>Producto / Servicio</th>
                    <th class="text-right" style="width: 85px;">Monto</th>
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
                    <td class="text-right" style="font-weight: 600; color: #F0F5F0;">$<?= number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) ?></td>
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
                    <td class="total-value" style="color: #00D690;">-$<?= number_format($invoice['discount_amount'], 2) ?></td>
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
                    <td class="total-label" style="padding-top: 8px;">Pagado</td>
                    <td class="total-value" style="padding-top: 8px; color: #00D690;">-$<?= number_format($invoice['amount_paid'], 2) ?></td>
                </tr>
                <tr>
                    <td class="total-label" style="font-weight: 800; color: #F0F5F0;">Balance</td>
                    <td class="total-value" style="font-weight: 800; color: #FB7185;">$<?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
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
                <div class="terms-title" style="margin-top: 10px;">Términos y Condiciones</div>
                <div class="terms-text"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <div class="footer">
        <span class="footer-accent"><?= htmlspecialchars($company['name'] ?? 'Gridbase') ?></span>
        <?php if (!empty($company['website'])) echo " · " . htmlspecialchars($company['website']); ?>
        <?php if (!empty($company['email'])) echo " · " . htmlspecialchars($company['email']); ?>
        <?php if (!empty($company['phone'])) echo " · " . htmlspecialchars($company['phone']); ?>
    </div>

</div>

</body>
</html>
