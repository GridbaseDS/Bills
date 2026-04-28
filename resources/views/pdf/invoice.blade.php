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
            color: #333333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #FFFFFF;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 200px;
            height: 100%;
            background: #0B484C;
            padding: 35px 20px 25px 20px;
        }
        .sidebar-logo {
            margin-bottom: 22px;
            text-align: center;
        }
        .sidebar-logo img {
            max-width: 145px;
            height: auto;
        }
        .sidebar-divider {
            width: 32px;
            height: 2px;
            background: #00DF83;
            margin: 16px 0;
            border-radius: 2px;
        }
        .sidebar-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #00DF83;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .sidebar-value {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.65);
            margin-bottom: 3px;
            line-height: 1.5;
        }
        .sidebar-value strong {
            font-size: 12px;
            color: #FFFFFF;
        }
        .sidebar-section {
            margin-bottom: 18px;
        }
        .sidebar-sep {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            background: #00DF83;
            border-radius: 50%;
            margin-right: 6px;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            margin-left: 200px;
            padding: 35px 30px 25px 30px;
            background: #FFFFFF;
            min-height: 100%;
        }

        /* ── HEADER ── */
        .doc-title {
            font-size: 30px;
            font-weight: 700;
            color: #0B484C;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0;
        }
        .doc-number-line {
            font-size: 10px;
            color: #7E7E7E;
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }
        .doc-number-line strong {
            color: #0B484C;
        }

        /* ── TOTAL DUE BOX ── */
        .total-due-box {
            background: #F6F5F2;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-left: 3px solid #0B484C;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .total-due-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #7E7E7E;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .total-due-amount {
            font-size: 26px;
            font-weight: 800;
            color: #0B484C;
        }
        .total-due-currency {
            font-size: 12px;
            color: #7E7E7E;
            font-weight: normal;
        }

        /* ── ITEMS TABLE ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .items-table thead tr {
            background: #0B484C;
        }
        .items-table th {
            color: #FFFFFF;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 9px 10px;
            text-align: left;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table tbody tr {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }
        .items-table tbody tr:nth-child(even) {
            background: #FAFAF8;
        }
        .items-table td {
            padding: 10px 10px;
            vertical-align: top;
            color: #333333;
        }
        .item-number {
            display: inline-block;
            width: 22px;
            height: 22px;
            background: #0B484C;
            color: #FFFFFF;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            line-height: 22px;
            border-radius: 4px;
        }
        .item-desc {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 11px;
        }
        .item-detail {
            font-size: 9px;
            color: #7E7E7E;
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
            color: #7E7E7E;
        }
        .totals-table .total-value {
            text-align: right;
            font-weight: 600;
            width: 90px;
            color: #333333;
        }
        .grand-total-row {
            background: #0B484C;
        }
        .grand-total-row td {
            color: #FFFFFF !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            padding: 9px 8px !important;
            border-radius: 4px;
        }

        /* ── STATUS BADGE ── */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .badge-paid { background: rgba(0, 223, 131, 0.12); color: #0B484C; }
        .badge-overdue { background: rgba(251, 113, 133, 0.12); color: #d32f2f; }
        .badge-draft { background: rgba(0, 0, 0, 0.06); color: #7E7E7E; }
        .badge-sent { background: rgba(56, 189, 248, 0.12); color: #0277bd; }

        /* ── TERMS ── */
        .terms-section {
            clear: both;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            margin-top: 8px;
        }
        .terms-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0B484C;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .terms-text {
            font-size: 9px;
            color: #7E7E7E;
            line-height: 1.6;
        }

        /* ── FOOTER ── */
        .footer {
            position: fixed;
            bottom: 12px;
            right: 30px;
            left: 230px;
            text-align: center;
            font-size: 8px;
            color: #7E7E7E;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            padding-top: 6px;
        }
        .footer-accent {
            color: #0B484C;
            font-weight: 600;
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

    <!-- DARK TEAL SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <?php if ($logoData): ?>
                <img src="data:image/png;base64,<?= $logoData ?>" alt="GridBase">
            <?php else: ?>
                <div style="font-size: 20px; font-weight: bold; color: #FFFFFF;">
                    Grid<span style="color: #00DF83;">Base</span>
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
                    <span style="font-size: 9px; color: rgba(255,255,255,0.45);">Empresa</span><br>
                    <span style="margin-left: 11px; color: #FFFFFF;"><?= htmlspecialchars($company['name']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['tax_id'])): ?>
                <div class="sidebar-value">
                    <span class="sidebar-dot"></span>
                    <span style="font-size: 9px; color: rgba(255,255,255,0.45);">RNC</span><br>
                    <span style="margin-left: 11px; color: #FFFFFF;"><?= htmlspecialchars($company['tax_id']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['email'])): ?>
                <div class="sidebar-value">
                    <span class="sidebar-dot"></span>
                    <span style="font-size: 9px; color: rgba(255,255,255,0.45);">Email</span><br>
                    <span style="margin-left: 11px; color: #FFFFFF;"><?= htmlspecialchars($company['email']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($company['phone'])): ?>
                <div class="sidebar-value">
                    <span class="sidebar-dot"></span>
                    <span style="font-size: 9px; color: rgba(255,255,255,0.45);">Teléfono</span><br>
                    <span style="margin-left: 11px; color: #FFFFFF;"><?= htmlspecialchars($company['phone']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN CONTENT (WHITE) -->
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
                <span class="total-due-currency"><?= htmlspecialchars($invoice['currency'] ?? 'USD') ?></span>
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
                    <td class="text-right" style="font-weight: 600; color: #1a1a1a;">$<?= number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) ?></td>
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
                    <td class="total-value" style="color: #00DF83;">-$<?= number_format($invoice['discount_amount'], 2) ?></td>
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
                    <td class="total-value" style="padding-top: 8px; color: #00DF83;">-$<?= number_format($invoice['amount_paid'], 2) ?></td>
                </tr>
                <tr>
                    <td class="total-label" style="font-weight: 800; color: #0B484C;">Balance</td>
                    <td class="total-value" style="font-weight: 800; color: #d32f2f;">$<?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
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
