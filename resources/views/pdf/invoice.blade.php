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
            font-size: 9px;
            color: #2D2D2D;
            background: #FFFFFF;
            line-height: 1.4;
            width: 595px;
        }

        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ── HEADER ── */
        .header {
            background: #0B484C;
            padding: 20px 35px;
            color: #FFFFFF;
        }
        .header td { vertical-align: middle; }
        .header-logo img { height: 32px; }
        .header-title {
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: right;
            color: #FFFFFF;
        }
        .header-num {
            font-size: 10px;
            color: #00DF83;
            text-align: right;
            margin-top: 2px;
            font-weight: 600;
        }

        /* Green bar */
        .green-bar {
            height: 3px;
            background: #00DF83;
        }

        /* ── BODY AREA ── */
        .body-area {
            padding: 18px 35px 20px 35px;
        }

        /* ── INFO COLS ── */
        .info-table { margin-bottom: 16px; }
        .info-table td { padding-bottom: 0; }

        .col-label {
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0B484C;
            border-bottom: 1.5px solid #00DF83;
            padding-bottom: 3px;
            margin-bottom: 6px;
            display: inline-block;
        }

        .col-name {
            font-size: 10px;
            font-weight: 700;
            color: #0B484C;
            margin-top: 6px;
            margin-bottom: 2px;
        }
        .col-text {
            font-size: 8px;
            color: #666;
            line-height: 1.5;
        }
        .col-client-name {
            font-size: 11px;
            font-weight: 700;
            color: #0B484C;
            margin-top: 6px;
            margin-bottom: 2px;
        }
        .col-client-text {
            font-size: 8.5px;
            color: #444;
            line-height: 1.5;
        }

        /* Details mini */
        .det-table td {
            padding: 3px 0;
            font-size: 8px;
        }
        .det-label {
            color: #999;
            font-size: 7px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .det-value {
            text-align: right;
            font-weight: 700;
            color: #2D2D2D;
            font-size: 9px;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 50px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-paid    { background: #D1FAE5; color: #065F46; }
        .badge-overdue { background: #FEE2E2; color: #991B1B; }
        .badge-draft   { background: #F3F4F6; color: #6B7280; }
        .badge-sent    { background: #DBEAFE; color: #1E40AF; }

        /* ── TOTAL BOX ── */
        .total-box {
            background: #F6F5F2;
            border-left: 3px solid #0B484C;
            padding: 10px 16px;
            margin-bottom: 16px;
        }
        .total-box td { vertical-align: middle; }
        .total-box-label {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #7E7E7E;
        }
        .total-box-amount {
            font-size: 20px;
            font-weight: 700;
            color: #0B484C;
            text-align: right;
        }
        .total-box-currency {
            font-size: 9px;
            color: #999;
            font-weight: 400;
        }

        /* ── ITEMS ── */
        .items { margin-bottom: 14px; }
        .items thead tr { background: #0B484C; }
        .items th {
            color: #FFF;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            text-align: left;
            border-bottom: 2px solid #00DF83;
        }
        .items tbody tr { border-bottom: 1px solid #F0F0F0; }
        .items tbody tr:nth-child(even) { background: #FAFAF8; }
        .items td {
            padding: 7px 8px;
            font-size: 9px;
            vertical-align: middle;
        }
        .item-idx {
            display: inline-block;
            width: 15px; height: 15px;
            background: #0B484C;
            color: #FFF;
            font-size: 6.5px;
            font-weight: 700;
            text-align: center;
            line-height: 15px;
            border-radius: 2px;
        }
        .item-name {
            font-weight: 700;
            color: #0B484C;
            font-size: 9px;
        }

        /* ── BOTTOM ── */
        .bottom-table { margin-top: 2px; }
        .bottom-table td { vertical-align: top; }

        .notes-area { padding-right: 20px; }
        .notes-title {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #0B484C;
            margin-bottom: 4px;
        }
        .notes-body {
            font-size: 8px;
            color: #888;
            line-height: 1.5;
        }

        /* Totals */
        .totals td {
            padding: 4px 8px;
            font-size: 9px;
        }
        .t-label { text-align: right; color: #999; font-weight: 500; }
        .t-value { text-align: right; font-weight: 600; color: #2D2D2D; width: 80px; }
        .t-sep td { border-top: 1px solid #EEE; }

        .t-grand td {
            background: #0B484C;
            color: #FFF !important;
            font-size: 10px !important;
            font-weight: 700 !important;
            padding: 7px 8px !important;
        }
        .t-grand .t-value { color: #00DF83 !important; }

        .t-paid td { padding-top: 8px !important; }
        .t-balance td {
            background: #FEF2F2;
            color: #DC2626 !important;
            font-weight: 700;
        }

        /* ── FOOTER ── */
        .footer-area {
            border-top: 1.5px solid #F0F0F0;
            padding: 12px 35px;
            text-align: center;
            margin-top: 14px;
        }
        .footer-thanks {
            font-size: 10px;
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 3px;
        }
        .footer-info {
            font-size: 8px;
            color: #AAA;
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
    elseif ($invoice['status'] === 'sent' || $invoice['status'] === 'pending') { $badgeClass = 'badge-sent'; $badgeText = 'ENVIADA'; }
}
?>

<!-- HEADER -->
<div class="header">
    <table><tr>
        <td style="width:50%;"><div class="header-logo"><img src="<?= $logoUrl ?>" alt="GridBase"></div></td>
        <td>
            <div class="header-title"><?= $docName ?></div>
            <div class="header-num">Nº <?= htmlspecialchars($docNum) ?></div>
        </td>
    </tr></table>
</div>
<div class="green-bar"></div>

<!-- BODY -->
<div class="body-area">

    <!-- 3-COL INFO -->
    <table class="info-table"><tr>
        <td style="width:30%; padding-right:12px;">
            <div class="col-label">Emisor</div>
            <?php if (!empty($company['name'])): ?><div class="col-name"><?= htmlspecialchars($company['name']) ?></div><?php endif; ?>
            <div class="col-text">
                <?php if (!empty($company['address'])): ?><?= htmlspecialchars($company['address']) ?><br><?php endif; ?>
                <?php if (!empty($company['city'])): ?><?= htmlspecialchars($company['city']) ?><?= !empty($company['country']) ? ', ' . htmlspecialchars($company['country']) : '' ?><br><?php endif; ?>
                <?php if (!empty($company['email'])): ?><?= htmlspecialchars($company['email']) ?><br><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><?= htmlspecialchars($company['phone']) ?><br><?php endif; ?>
                <?php if (!empty($company['tax_id'])): ?>RNC: <?= htmlspecialchars($company['tax_id']) ?><?php endif; ?>
            </div>
        </td>
        <td style="width:35%; padding-right:12px;">
            <div class="col-label">Facturar A</div>
            <div class="col-client-name"><?= htmlspecialchars($client['company_name'] ?: $client['contact_name']) ?></div>
            <div class="col-client-text">
                <?php if (!empty($client['company_name']) && !empty($client['contact_name'])): ?>Attn: <?= htmlspecialchars($client['contact_name']) ?><br><?php endif; ?>
                <?php if (!empty($client['address_line1'])): ?><?= htmlspecialchars($client['address_line1']) ?><br><?php endif; ?>
                <?php if (!empty($client['city'])): ?><?= htmlspecialchars($client['city']) ?><?= !empty($client['country']) ? ', ' . htmlspecialchars($client['country']) : '' ?><br><?php endif; ?>
                <?php if (!empty($client['email'])): ?><?= htmlspecialchars($client['email']) ?><br><?php endif; ?>
                <?php if (!empty($client['phone'])): ?>Tel: <?= htmlspecialchars($client['phone']) ?><br><?php endif; ?>
                <?php if (!empty($client['tax_id'])): ?>RNC: <?= htmlspecialchars($client['tax_id']) ?><?php endif; ?>
            </div>
        </td>
        <td style="width:35%;">
            <div class="col-label">Detalles</div>
            <table class="det-table" style="margin-top:6px;">
                <tr><td class="det-label">Emisión</td><td class="det-value"><?= !empty($invoice['issue_date']) ? date('d/m/Y', strtotime($invoice['issue_date'])) : '' ?></td></tr>
                <tr><td class="det-label"><?= $dateLabel ?></td><td class="det-value"><?= !empty($dateField) ? date('d/m/Y', strtotime($dateField)) : '' ?></td></tr>
                <?php if (!empty($company['tax_id'])): ?>
                <tr><td class="det-label">RNC</td><td class="det-value"><?= htmlspecialchars($company['tax_id']) ?></td></tr>
                <?php endif; ?>
                <?php if (!$isQuote): ?>
                <tr><td class="det-label">Estado</td><td class="det-value"><span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></td></tr>
                <?php endif; ?>
            </table>
        </td>
    </tr></table>

    <!-- TOTAL BOX -->
    <div class="total-box">
        <table><tr>
            <td><div class="total-box-label"><?= $isQuote ? 'Valor Total' : 'Monto Total a Pagar' ?></div></td>
            <td class="text-right"><div class="total-box-amount"><span class="total-box-currency"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?></span> $<?= number_format($invoice['total'] ?? 0, 2) ?></div></td>
        </tr></table>
    </div>

    <!-- ITEMS -->
    <table class="items">
        <thead><tr>
            <th style="width:26px;">#</th>
            <th>Descripción</th>
            <th class="text-center" style="width:50px;">Cant.</th>
            <th class="text-right" style="width:70px;">Precio</th>
            <th class="text-right" style="width:75px;">Monto</th>
        </tr></thead>
        <tbody>
            <?php foreach (($invoice['items'] ?? $items ?? []) as $index => $item): ?>
            <tr>
                <td class="text-center"><span class="item-idx"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></span></td>
                <td><span class="item-name"><?= htmlspecialchars($item['description']) ?></span></td>
                <td class="text-center"><?= number_format($item['quantity'], 0) ?></td>
                <td class="text-right">$<?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-right" style="font-weight:700;">$<?= number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- NOTES + TOTALS -->
    <table class="bottom-table"><tr>
        <td style="width:55%;" class="notes-area">
            <?php if (!empty($invoice['notes'])): ?>
                <div class="notes-title">Notas</div>
                <div class="notes-body"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($invoice['terms'])): ?>
                <div class="notes-title" style="margin-top:8px;">Términos y Condiciones</div>
                <div class="notes-body"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></div>
            <?php endif; ?>
        </td>
        <td style="width:45%;">
            <table class="totals">
                <tr><td class="t-label">Subtotal</td><td class="t-value">$<?= number_format($invoice['subtotal'] ?? 0, 2) ?></td></tr>
                <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
                <tr><td class="t-label">Descuento</td><td class="t-value" style="color:#00DF83;">-$<?= number_format($invoice['discount_amount'], 2) ?></td></tr>
                <?php endif; ?>
                <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
                <tr class="t-sep"><td class="t-label">ITBIS (<?= number_format($invoice['tax_rate'] ?? 0, 0) ?>%)</td><td class="t-value">$<?= number_format($invoice['tax_amount'], 2) ?></td></tr>
                <?php endif; ?>
                <tr class="t-grand"><td class="t-label" style="text-align:right;color:#fff;">TOTAL</td><td class="t-value"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> $<?= number_format($invoice['total'] ?? 0, 2) ?></td></tr>
                <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
                <tr class="t-paid"><td class="t-label">Pagado</td><td class="t-value" style="color:#059669;">-$<?= number_format($invoice['amount_paid'], 2) ?></td></tr>
                <tr class="t-balance"><td class="t-label" style="color:#991B1B;">Saldo</td><td class="t-value">$<?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td></tr>
                <?php endif; ?>
            </table>
        </td>
    </tr></table>

    <!-- FOOTER -->
    <div class="footer-area">
        <div class="footer-thanks">¡Gracias por su preferencia!</div>
        <div class="footer-info">
            <?= htmlspecialchars($company['name'] ?? 'Gridbase') ?>
            <?php if (!empty($company['website'])) echo " &bull; " . htmlspecialchars($company['website']); ?>
            <?php if (!empty($company['email'])) echo " &bull; " . htmlspecialchars($company['email']); ?>
            <?php if (!empty($company['phone'])) echo " &bull; " . htmlspecialchars($company['phone']); ?>
        </div>
    </div>

</div>

</body>
</html>
