<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isQuote ?? false ? 'Cotización' : 'Factura' }}</title>
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
            background: #0B484C;
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
        .logo-fallback span { color: #00DF83; }
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
            color: #00DF83;
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
            color: #0B484C;
            text-transform: uppercase;
            border-bottom: 3px solid #0B484C;
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
            color: #0B484C;
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
        .items-table thead tr { background: #00DF83; }
        .items-table th {
            color: #0B484C;
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
            color: #0B484C;
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
        .totals-mini .grand .tv { color: #0B484C; font-size: 12px; }

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
            color: #0B484C;
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
            background: #0B484C;
            padding: 12px 45px;
            text-align: center;
        }
        .footer-contact {
            font-size: 10px;
            color: rgba(255,255,255,0.85);
        }
        .footer-contact span {
            color: #00DF83;
            margin: 0 8px;
        }
    </style>
</head>
<body>
<?php
$isQuote   = isset($is_quote) && $is_quote;
$docName   = $isQuote ? 'Cotización' : 'Factura';
$docNum    = $isQuote ? ($invoice['quote_number'] ?? '') : ($invoice['invoice_number'] ?? '');
$dateLabel = $isQuote ? 'Válida Hasta' : 'Vencimiento';
$dateField = $isQuote ? ($invoice['expiry_date'] ?? $invoice['due_date'] ?? '') : ($invoice['due_date'] ?? '');
$logoUrl   = 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png';

$badgeClass = 'badge-draft'; $badgeText = 'BORRADOR';
if (!empty($invoice['status'])) {
    if ($invoice['status'] === 'paid')    { $badgeClass = 'badge-paid';    $badgeText = 'PAGADA'; }
    elseif ($invoice['status'] === 'overdue') { $badgeClass = 'badge-overdue'; $badgeText = 'VENCIDA'; }
    elseif ($invoice['status'] === 'sent' || $invoice['status'] === 'pending') { $badgeClass = 'badge-sent'; $badgeText = 'PENDIENTE DE PAGO'; }
    elseif ($invoice['status'] === 'partial') { $badgeClass = 'badge-sent'; $badgeText = 'PAGO PARCIAL'; }
}
?>

<!-- ══════════════════════════ HEADER ══════════════════════════ -->
<div class="header-block">
    <table><tr>
        <td style="width:55%;">
            <div class="logo-area">
                <img src="<?= $logoUrl ?>" alt="GridBase">
            </div>
        </td>
        <td style="width:45%; text-align:right;">
            <div class="header-meta-box">
                <div class="meta-row-item"><span class="meta-label">NÚMERO/</span> <strong><?= htmlspecialchars($docNum) ?></strong></div>
                <div class="meta-row-item"><span class="meta-label">FECHA/</span> <strong><?= !empty($invoice['issue_date']) ? date('d/ m/ Y', strtotime($invoice['issue_date'])) : '' ?></strong></div>
                <?php if (!$isQuote && !empty($invoice['status'])): ?>
                <div style="margin-top:5px;"><span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></div>
                <?php endif; ?>
            </div>
        </td>
    </tr></table>
</div>

<!-- ══════════════════════════ BODY ══════════════════════════ -->
<div class="body-content">

    <!-- Title -->
    <div class="doc-title"><?= $docName ?></div>

    <!-- Info Columns -->
    <table class="info-cols"><tr>
        <td style="width:50%;">
            <div class="info-col-title">Facturar A</div>
            <div class="info-col-name"><?= htmlspecialchars($client['company_name'] ?: $client['contact_name']) ?></div>
            <div class="info-col-text">
                <?php if (!empty($client['company_name']) && !empty($client['contact_name'])): ?>
                    <?= htmlspecialchars($client['contact_name']) ?><br>
                <?php endif; ?>
                <?php if (!empty($client['address_line1'])): ?><?= htmlspecialchars($client['address_line1']) ?><br><?php endif; ?>
                <?php if (!empty($client['city'])): ?><?= htmlspecialchars($client['city']) ?><?= !empty($client['country']) ? ', ' . htmlspecialchars($client['country']) : '' ?><br><?php endif; ?>
                <?php if (!empty($client['email'])): ?><?= htmlspecialchars($client['email']) ?><br><?php endif; ?>
                <?php if (!empty($client['tax_id'])): ?>RNC: <?= htmlspecialchars($client['tax_id']) ?><?php endif; ?>
            </div>
        </td>
        <td style="width:50%;">
            <div class="info-col-title">Emisor</div>
            <div class="info-col-name"><?= htmlspecialchars($company['name'] ?? '') ?></div>
            <div class="info-col-text">
                <?php if (!empty($company['address'])): ?><?= htmlspecialchars($company['address']) ?><br><?php endif; ?>
                <?php if (!empty($company['city'])): ?><?= htmlspecialchars($company['city']) ?><?= !empty($company['country']) ? ', ' . htmlspecialchars($company['country']) : '' ?><br><?php endif; ?>
                <?php if (!empty($company['email'])): ?><?= htmlspecialchars($company['email']) ?><br><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><?= htmlspecialchars($company['phone']) ?><br><?php endif; ?>
                <?php if (!empty($company['tax_id'])): ?>RNC: <?= htmlspecialchars($company['tax_id']) ?><?php endif; ?>
            </div>
        </td>
    </tr></table>

    <!-- Items Table -->
    <table class="items-table">
        <thead><tr>
            <th style="width:45%;">DESCRIPCIÓN</th>
            <th class="text-center" style="width:15%;">CANT.</th>
            <th class="text-right" style="width:20%;">PRECIO</th>
            <th class="text-right" style="width:20%;">TOTAL</th>
        </tr></thead>
        <tbody>
            <?php foreach (($invoice['items'] ?? $items ?? []) as $index => $item): ?>
            <tr>
                <td class="item-desc"><?= htmlspecialchars($item['description']) ?></td>
                <td class="text-center"><?= number_format($item['quantity'], 0) ?></td>
                <td class="text-right">$<?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-right item-total">$<?= number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Payment + Totals -->
    <table class="bottom-area"><tr>
        <td style="width:55%; padding-right:30px;">
            <?php if (!empty($invoice['notes'])): ?>
                <div class="payment-title">Notas</div>
                <div class="payment-text"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
            <?php endif; ?>
        </td>
        <td style="width:45%;">
            <table class="totals-mini">
                <tr>
                    <td class="tl">SUBTOTAL</td>
                    <td class="tv">$<?= number_format($invoice['subtotal'] ?? 0, 2) ?></td>
                </tr>
                <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="tl">DESCUENTO</td>
                    <td class="tv" style="color:#00DF83;">-$<?= number_format($invoice['discount_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="tl">ITBIS (<?= number_format($invoice['tax_rate'] ?? 0, 0) ?>%)</td>
                    <td class="tv">$<?= number_format($invoice['tax_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand">
                    <td class="tl">TOTAL</td>
                    <td class="tv">$<?= number_format($invoice['total'] ?? 0, 2) ?></td>
                </tr>
                <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
                <tr class="paid-row">
                    <td class="tl">PAGADO</td>
                    <td class="tv" style="color:#059669;">-$<?= number_format($invoice['amount_paid'], 2) ?></td>
                </tr>
                <tr class="balance-row">
                    <td class="tl">SALDO</td>
                    <td class="tv">$<?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
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

</body>
</html>
