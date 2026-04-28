<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($isQuote ?? false ? 'Cotización' : 'Factura') ?> <?= htmlspecialchars($invoice['invoice_number'] ?? $invoice['quote_number'] ?? '') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #1F2937;
            background: #ffffff;
            line-height: 1.5;
        }

        /* ── OUTER WRAPPER ── */
        .page {
            width: 100%;
            min-height: 100%;
            display: table;
            border-collapse: collapse;
        }

        /* ── LEFT SIDEBAR ── */
        .sidebar {
            width: 220px;
            background-color: #0D7560;
            color: #ffffff;
            vertical-align: top;
            padding: 50px 28px;
        }

        .sidebar-logo {
            margin-bottom: 40px;
        }
        .sidebar-logo img { height: 50px; }
        .sidebar-logo .logo-text {
            font-size: 24px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .sidebar-logo .logo-text span {
            color: #7EFFD4;
        }

        .sidebar-section {
            margin-bottom: 36px;
        }
        .sidebar-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #7EFFD4;
            margin-bottom: 10px;
        }
        .sidebar-value {
            font-size: 13px;
            color: #E0FFF5;
            line-height: 1.65;
        }
        .sidebar-value strong {
            color: #ffffff;
            font-size: 14px;
        }

        .sidebar-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.15);
            margin: 28px 0;
        }

        /* Big total on sidebar */
        .sidebar-total-box {
            background: rgba(0,0,0,0.18);
            border-radius: 10px;
            padding: 20px 18px;
            margin-top: 8px;
        }
        .sidebar-total-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #7EFFD4;
            margin-bottom: 8px;
        }
        .sidebar-total-amount {
            font-size: 28px;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
        }
        .sidebar-currency {
            font-size: 14px;
            font-weight: 500;
            color: #A8FFE0;
            margin-right: 3px;
        }

        /* ── MAIN CONTENT ── */
        .main {
            vertical-align: top;
            padding: 50px 48px 100px 48px;
        }

        /* ── MAIN HEADER ── */
        .main-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 48px;
        }
        .main-header td { vertical-align: bottom; }

        .doc-type {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #6B7280;
            margin-bottom: 6px;
        }
        .doc-number {
            font-size: 36px;
            font-weight: 900;
            color: #111827;
            letter-spacing: -1px;
            line-height: 1;
        }
        .doc-number span { color: #0D7560; }

        .status-badge {
            display: inline-block;
            padding: 7px 16px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-paid    { background: #D1FAE5; color: #065F46; border: 1.5px solid #34D399; }
        .status-pending { background: #FEF3C7; color: #92400E; border: 1.5px solid #F59E0B; }
        .status-overdue { background: #FEE2E2; color: #991B1B; border: 1.5px solid #F87171; }
        .status-draft   { background: #F3F4F6; color: #374151; border: 1.5px solid #9CA3AF; }

        /* ── SECTION LABEL ── */
        .section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9CA3AF;
            margin-bottom: 10px;
        }

        /* ── BILL TO / DATES ── */
        .meta-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 44px;
        }
        .meta-row td { vertical-align: top; }
        .meta-block { padding-right: 30px; }
        .meta-block:last-child { padding-right: 0; }

        .bill-name {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        .bill-contact {
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 2px;
        }
        .bill-detail {
            font-size: 13px;
            color: #4B5563;
        }

        .date-value {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }
        .date-sub {
            font-size: 12px;
            color: #6B7280;
        }

        /* ── ITEMS TABLE ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table thead tr {
            background-color: #F3F4F6;
            border-radius: 6px;
        }
        .items-table th {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6B7280;
            padding: 12px 14px;
            text-align: left;
            border: none;
        }
        .items-table th:first-child { border-radius: 6px 0 0 6px; }
        .items-table th:last-child  { border-radius: 0 6px 6px 0; }

        .items-table tbody tr { border-bottom: 1px solid #F3F4F6; }
        .items-table tbody tr:last-child { border-bottom: 2px solid #E5E7EB; }

        .items-table td {
            padding: 16px 14px;
            font-size: 13px;
            color: #374151;
            vertical-align: middle;
        }
        .item-name {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }
        .item-total {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        /* ── TOTALS ── */
        .bottom-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 36px;
        }
        .bottom-section td { vertical-align: top; }

        .notes-block {
            padding-right: 40px;
        }
        .notes-content {
            font-size: 13px;
            color: #4B5563;
            line-height: 1.65;
            background: #F9FAFB;
            padding: 18px 20px;
            border-radius: 8px;
            border-left: 3px solid #0D7560;
            margin-top: 10px;
        }
        .terms-content {
            font-size: 12px;
            color: #9CA3AF;
            line-height: 1.65;
            margin-top: 10px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 10px 0;
            font-size: 14px;
        }
        .totals-table .t-label { color: #6B7280; }
        .totals-table .t-value { text-align: right; font-weight: 500; color: #111827; width: 130px; }
        .totals-table .t-discount { color: #EF4444; font-weight: 500; text-align: right; }
        .totals-table .t-paid { color: #059669; font-weight: 500; text-align: right; }

        .totals-table .grand-row td {
            border-top: 2px solid #111827;
            padding-top: 18px;
            padding-bottom: 18px;
        }
        .grand-label {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
        }
        .grand-value {
            font-size: 22px;
            font-weight: 900;
            color: #0D7560;
            text-align: right;
        }

        .balance-row td {
            background-color: #FEF2F2;
            padding: 14px 12px;
            border-radius: 6px;
        }
        .balance-label {
            font-size: 13px;
            font-weight: 700;
            color: #991B1B;
        }
        .balance-value {
            font-size: 18px;
            font-weight: 900;
            color: #DC2626;
            text-align: right;
        }

        /* ── FOOTER ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            border-top: 1px solid #E5E7EB;
            padding: 14px 48px;
            font-size: 11px;
            color: #9CA3AF;
        }
        .footer-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-inner td { vertical-align: middle; }
        .footer-dot { color: #D1D5DB; margin: 0 8px; }
    </style>
</head>
<body>
<?php
$isQuote   = isset($document_type) && $document_type === 'quote';
$docName   = $isQuote ? 'Cotización' : 'Factura';
$docNum    = $isQuote ? ($invoice['quote_number'] ?? '') : ($invoice['invoice_number'] ?? '');
$dateLabel = $isQuote ? 'Válida Hasta' : 'Vencimiento';
$dateField = $isQuote ? ($invoice['expiry_date'] ?? '') : ($invoice['due_date'] ?? '');
$status    = $invoice['status'] ?? 'draft';
$badgeClass = 'status-draft';
$badgeText  = 'Borrador';
if ($status === 'paid')    { $badgeClass = 'status-paid';    $badgeText = 'Pagada'; }
if ($status === 'pending') { $badgeClass = 'status-pending'; $badgeText = 'Pendiente'; }
if ($status === 'overdue') { $badgeClass = 'status-overdue'; $badgeText = 'Vencida'; }
?>

<table class="page" cellspacing="0" cellpadding="0">
<tr>

<!-- ═══════════════════════════════ SIDEBAR ═══════════════════════════════ -->
<td class="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <?php
        $logoPath = __DIR__ . '/../assets/img/logo.png';
        if (file_exists($logoPath)):
            $logoData = base64_encode(file_get_contents($logoPath));
        ?>
            <img src="data:image/png;base64,<?= $logoData ?>" alt="Logo">
        <?php else: ?>
            <div class="logo-text">Grid<span>Base</span></div>
        <?php endif; ?>
    </div>

    <!-- Company Info -->
    <div class="sidebar-section">
        <div class="sidebar-label">Emisor</div>
        <div class="sidebar-value">
            <strong><?= htmlspecialchars($company['company_name'] ?? 'Gridbase Digital Solutions') ?></strong><br>
            <?php if(!empty($company['company_address'])): ?>
                <?= htmlspecialchars($company['company_address']) ?><br>
            <?php endif; ?>
            <?php if(!empty($company['company_city'])): ?>
                <?= htmlspecialchars($company['company_city']) ?>
                <?= !empty($company['company_country']) ? ', ' . htmlspecialchars($company['company_country']) : '' ?><br>
            <?php endif; ?>
            <?php if(!empty($company['company_email'])): ?>
                <?= htmlspecialchars($company['company_email']) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!empty($company['company_tax_id'])): ?>
    <div class="sidebar-section">
        <div class="sidebar-label">RNC / Cédula</div>
        <div class="sidebar-value">
            <strong><?= htmlspecialchars($company['company_tax_id']) ?></strong>
        </div>
    </div>
    <?php endif; ?>

    <hr class="sidebar-divider">

    <!-- Dates -->
    <div class="sidebar-section">
        <div class="sidebar-label">Fecha de Emisión</div>
        <div class="sidebar-value">
            <strong><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></strong>
        </div>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-label"><?= $dateLabel ?></div>
        <div class="sidebar-value">
            <strong><?= date('d/m/Y', strtotime($dateField)) ?></strong>
        </div>
    </div>

    <hr class="sidebar-divider">

    <!-- Total -->
    <div class="sidebar-total-box">
        <div class="sidebar-total-label">
            <?= $isQuote ? 'Valor Total' : 'Monto Total' ?>
        </div>
        <div class="sidebar-total-amount">
            <span class="sidebar-currency"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?></span>
            <?= number_format($invoice['total'], 2) ?>
        </div>
    </div>

</td>

<!-- ═══════════════════════════════ MAIN ═══════════════════════════════ -->
<td class="main">

    <!-- Doc Title Row -->
    <table class="main-header" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div class="doc-type"><?= $docName ?></div>
                <div class="doc-number">#<span><?= htmlspecialchars($docNum) ?></span></div>
            </td>
            <td style="text-align: right;">
                <?php if (!$isQuote): ?>
                    <span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Bill To + Dates -->
    <table class="meta-row" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 55%;">
                <div class="section-label">Facturado a</div>
                <div class="bill-name"><?= htmlspecialchars($invoice['company_name'] ?: $invoice['contact_name']) ?></div>
                <?php if ($invoice['company_name']): ?>
                    <div class="bill-contact"><?= htmlspecialchars($invoice['contact_name']) ?></div>
                <?php endif; ?>
                <?php if(!empty($invoice['address_line1'])): ?>
                    <div class="bill-detail"><?= htmlspecialchars($invoice['address_line1']) ?></div>
                <?php endif; ?>
                <?php if(!empty($invoice['city'])): ?>
                    <div class="bill-detail"><?= htmlspecialchars($invoice['city']) ?>, <?= htmlspecialchars($invoice['country'] ?? '') ?></div>
                <?php endif; ?>
                <?php if(!empty($invoice['client_tax_id'])): ?>
                    <div class="bill-detail" style="margin-top:8px; font-weight:600; color:#374151;">RNC/Cédula: <?= htmlspecialchars($invoice['client_tax_id']) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:44%">Descripción</th>
                <th class="text-center" style="width:14%">Cant.</th>
                <th class="text-right" style="width:18%">Precio Unit.</th>
                <th class="text-right" style="width:20%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice['items'] as $index => $item): ?>
            <tr>
                <td style="color:#9CA3AF; font-size:12px;"><?= $index + 1 ?></td>
                <td><span class="item-name"><?= nl2br(htmlspecialchars($item['description'])) ?></span></td>
                <td class="text-center"><?= number_format($item['quantity'], 2) ?></td>
                <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-right"><span class="item-total"><?= number_format($item['amount'], 2) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Notes + Totals -->
    <table class="bottom-section" cellspacing="0" cellpadding="0">
        <tr>
            <!-- Notes -->
            <td style="width:50%;">
                <div class="notes-block">
                    <?php if(!empty($invoice['notes'])): ?>
                        <div class="section-label">Notas</div>
                        <div class="notes-content">
                            <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($invoice['terms'])): ?>
                        <div class="section-label" style="margin-top:24px;">Términos y Condiciones</div>
                        <div class="terms-content">
                            <?= nl2br(htmlspecialchars($invoice['terms'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>

            <!-- Totals -->
            <td style="width:50%;">
                <table class="totals-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="t-label">Subtotal</td>
                        <td class="t-value"><?= htmlspecialchars($invoice['currency'] ?? '') ?> <?= number_format($invoice['subtotal'], 2) ?></td>
                    </tr>

                    <?php if ($invoice['discount_amount'] > 0): ?>
                    <tr>
                        <td class="t-label">Descuento</td>
                        <td class="t-discount">− <?= htmlspecialchars($invoice['currency'] ?? '') ?> <?= number_format($invoice['discount_amount'], 2) ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($invoice['tax_amount'] > 0): ?>
                    <tr>
                        <td class="t-label">ITBIS (<?= number_format($invoice['tax_rate'], 1) ?>%)</td>
                        <td class="t-value"><?= htmlspecialchars($invoice['currency'] ?? '') ?> <?= number_format($invoice['tax_amount'], 2) ?></td>
                    </tr>
                    <?php endif; ?>

                    <tr class="grand-row">
                        <td class="grand-label">Total</td>
                        <td class="grand-value"><?= htmlspecialchars($invoice['currency'] ?? '') ?> <?= number_format($invoice['total'], 2) ?></td>
                    </tr>

                    <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
                    <tr>
                        <td class="t-label" style="padding-top:16px;">Monto Pagado</td>
                        <td class="t-paid" style="padding-top:16px;">− <?= htmlspecialchars($invoice['currency'] ?? '') ?> <?= number_format($invoice['amount_paid'], 2) ?></td>
                    </tr>
                    <tr class="balance-row">
                        <td class="balance-label">Saldo Pendiente</td>
                        <td class="balance-value"><?= htmlspecialchars($invoice['currency'] ?? '') ?> <?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

</td>
</tr>
</table>

<!-- FOOTER -->
<div class="footer">
    <table class="footer-inner" cellspacing="0" cellpadding="0">
        <tr>
            <td><?= htmlspecialchars($company['company_name'] ?? 'Gridbase') ?><?php if(!empty($company['company_website'])): ?><span class="footer-dot">&bull;</span><?= htmlspecialchars($company['company_website']) ?><?php endif; ?><?php if(!empty($company['company_email'])): ?><span class="footer-dot">&bull;</span><?= htmlspecialchars($company['company_email']) ?><?php endif; ?></td>
            <td style="text-align:right; color:#D1D5DB;">Documento generado el <?= date('d/m/Y') ?></td>
        </tr>
    </table>
</div>

</body>
</html>
