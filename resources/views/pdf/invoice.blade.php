<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isQuote ?? false ? 'Cotización' : 'Factura' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 30px 40px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #2D2D2D;
            background: #FFFFFF;
            line-height: 1.4;
        }

        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ── HEADER BAND ── */
        .header-band {
            background: #0B484C;
            padding: 22px 28px;
            color: #FFFFFF;
            margin: -30px -40px 0 -40px;
            width: calc(100% + 80px);
        }
        .header-band table td { vertical-align: middle; }

        .header-logo img { height: 38px; }
        .header-logo .fallback {
            font-size: 22px;
            font-weight: 700;
            color: #FFFFFF;
        }
        .header-logo .fallback span { color: #00DF83; }

        .header-right {
            text-align: right;
        }
        .header-doc-type {
            font-size: 28px;
            font-weight: 700;
            color: #FFFFFF;
            text-transform: uppercase;
            letter-spacing: 2px;
            line-height: 1;
        }
        .header-doc-num {
            font-size: 11px;
            color: #00DF83;
            margin-top: 4px;
            font-weight: 600;
        }

        /* ── GREEN ACCENT BAR ── */
        .accent-bar {
            height: 4px;
            background: #00DF83;
            margin: 0 -40px;
            width: calc(100% + 80px);
        }

        /* ── INFO ROW (Company + Client + Invoice Details) ── */
        .info-row {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .info-row td {
            padding: 0;
        }

        .info-block-label {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #0B484C;
            margin-bottom: 6px;
            border-bottom: 2px solid #00DF83;
            padding-bottom: 4px;
            display: inline-block;
        }

        .info-company-name {
            font-size: 11px;
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 3px;
        }
        .info-text {
            font-size: 9px;
            color: #666666;
            line-height: 1.55;
        }

        .info-client-name {
            font-size: 12px;
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 3px;
        }
        .info-client-detail {
            font-size: 9px;
            color: #444444;
            line-height: 1.55;
        }

        /* Invoice details mini table */
        .details-mini td {
            padding: 4px 0;
            font-size: 9px;
        }
        .details-mini .dl {
            color: #888888;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 7.5px;
            letter-spacing: 0.5px;
            padding-right: 10px;
        }
        .details-mini .dv {
            color: #2D2D2D;
            font-weight: 700;
            font-size: 10px;
            text-align: right;
        }

        /* ── TOTAL DUE HIGHLIGHT ── */
        .total-highlight {
            background: #F6F5F2;
            border: 1px solid #E8E6E1;
            border-left: 4px solid #0B484C;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 22px;
        }
        .total-highlight table td { vertical-align: middle; }
        .total-hl-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #7E7E7E;
        }
        .total-hl-amount {
            font-size: 24px;
            font-weight: 700;
            color: #0B484C;
            text-align: right;
        }
        .total-hl-currency {
            font-size: 10px;
            color: #7E7E7E;
            font-weight: 400;
        }

        /* ── STATUS BADGE ── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
        .badge-paid    { background: #D1FAE5; color: #065F46; }
        .badge-overdue { background: #FEE2E2; color: #991B1B; }
        .badge-draft   { background: #F3F4F6; color: #6B7280; }
        .badge-sent    { background: #DBEAFE; color: #1E40AF; }

        /* ── ITEMS TABLE ── */
        .items-table { margin-bottom: 18px; }

        .items-table thead tr { background: #0B484C; }
        .items-table th {
            color: #FFFFFF;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 3px solid #00DF83;
        }

        .items-table tbody tr { border-bottom: 1px solid #EEEEEE; }
        .items-table tbody tr:nth-child(even) { background: #FAFAF8; }

        .items-table td {
            padding: 10px;
            vertical-align: middle;
            font-size: 10px;
        }

        .item-idx {
            display: inline-block;
            width: 18px; height: 18px;
            background: #0B484C;
            color: #FFFFFF;
            font-size: 7px;
            font-weight: 700;
            text-align: center;
            line-height: 18px;
            border-radius: 3px;
        }

        .item-name {
            font-weight: 700;
            color: #0B484C;
            font-size: 10px;
        }

        /* ── BOTTOM SECTION ── */
        .bottom-row td {
            vertical-align: top;
        }
        .bottom-notes {
            padding-right: 25px;
        }
        .bottom-notes-title {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #0B484C;
            margin-bottom: 5px;
        }
        .bottom-notes-text {
            font-size: 9px;
            color: #7E7E7E;
            line-height: 1.55;
        }

        /* Totals */
        .totals-mini { border-collapse: collapse; }
        .totals-mini td {
            padding: 6px 10px;
            font-size: 10px;
        }
        .totals-mini .tl {
            text-align: right;
            color: #888888;
            font-weight: 500;
        }
        .totals-mini .tv {
            text-align: right;
            color: #2D2D2D;
            font-weight: 600;
            width: 90px;
        }
        .totals-mini .sep td {
            border-top: 1px solid #EEEEEE;
        }

        .grand-row td {
            background: #0B484C;
            color: #FFFFFF !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            padding: 10px !important;
        }
        .grand-row .tv {
            color: #00DF83 !important;
        }

        .paid-row td { padding-top: 10px !important; }
        .balance-row td {
            background: #FEF2F2;
            color: #DC2626 !important;
            font-weight: 700;
            padding: 8px 10px;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 30px;
            border-top: 2px solid #F6F5F2;
            padding-top: 16px;
            text-align: center;
        }
        .footer-thanks {
            font-size: 12px;
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 4px;
        }
        .footer-info {
            font-size: 9px;
            color: #999999;
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

<!-- ═══════════ DARK HEADER BAND ═══════════ -->
<div class="header-band">
    <table>
        <tr>
            <td style="width:50%;">
                <div class="header-logo">
                    <img src="<?= $logoUrl ?>" alt="GridBase">
                </div>
            </td>
            <td class="header-right">
                <div class="header-doc-type"><?= $docName ?></div>
                <div class="header-doc-num">Nº <?= htmlspecialchars($docNum) ?></div>
            </td>
        </tr>
    </table>
</div>
<div class="accent-bar"></div>

<!-- ═══════════ 3-COLUMN INFO ROW ═══════════ -->
<table class="info-row">
    <tr>
        <!-- Col 1: Emisor -->
        <td style="width:30%; padding-right:15px;">
            <div class="info-block-label">Emisor</div>
            <?php if (!empty($company['name'])): ?>
                <div class="info-company-name"><?= htmlspecialchars($company['name']) ?></div>
            <?php endif; ?>
            <div class="info-text">
                <?php if (!empty($company['address'])): ?><?= htmlspecialchars($company['address']) ?><br><?php endif; ?>
                <?php if (!empty($company['city'])): ?><?= htmlspecialchars($company['city']) ?><?= !empty($company['country']) ? ', ' . htmlspecialchars($company['country']) : '' ?><br><?php endif; ?>
                <?php if (!empty($company['email'])): ?><?= htmlspecialchars($company['email']) ?><br><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><?= htmlspecialchars($company['phone']) ?><br><?php endif; ?>
                <?php if (!empty($company['tax_id'])): ?>RNC: <?= htmlspecialchars($company['tax_id']) ?><?php endif; ?>
            </div>
        </td>

        <!-- Col 2: Facturar A -->
        <td style="width:35%; padding-right:15px;">
            <div class="info-block-label">Facturar A</div>
            <div class="info-client-name"><?= htmlspecialchars($client['company_name'] ?: $client['contact_name']) ?></div>
            <div class="info-client-detail">
                <?php if (!empty($client['company_name']) && !empty($client['contact_name'])): ?>
                    Attn: <?= htmlspecialchars($client['contact_name']) ?><br>
                <?php endif; ?>
                <?php if (!empty($client['address_line1'])): ?><?= htmlspecialchars($client['address_line1']) ?><br><?php endif; ?>
                <?php if (!empty($client['city'])): ?><?= htmlspecialchars($client['city']) ?><?= !empty($client['country']) ? ', ' . htmlspecialchars($client['country']) : '' ?><br><?php endif; ?>
                <?php if (!empty($client['email'])): ?><?= htmlspecialchars($client['email']) ?><br><?php endif; ?>
                <?php if (!empty($client['phone'])): ?>Tel: <?= htmlspecialchars($client['phone']) ?><br><?php endif; ?>
                <?php if (!empty($client['tax_id'])): ?>RNC: <?= htmlspecialchars($client['tax_id']) ?><?php endif; ?>
            </div>
        </td>

        <!-- Col 3: Detalles del Documento -->
        <td style="width:35%;">
            <div class="info-block-label">Detalles</div>
            <table class="details-mini">
                <tr>
                    <td class="dl">Emisión</td>
                    <td class="dv"><?= !empty($invoice['issue_date']) ? date('d/m/Y', strtotime($invoice['issue_date'])) : '' ?></td>
                </tr>
                <tr>
                    <td class="dl"><?= $dateLabel ?></td>
                    <td class="dv"><?= !empty($dateField) ? date('d/m/Y', strtotime($dateField)) : '' ?></td>
                </tr>
                <?php if (!empty($company['tax_id'])): ?>
                <tr>
                    <td class="dl">RNC</td>
                    <td class="dv"><?= htmlspecialchars($company['tax_id']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!$isQuote): ?>
                <tr>
                    <td class="dl">Estado</td>
                    <td class="dv"><span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                </tr>
                <?php endif; ?>
            </table>
        </td>
    </tr>
</table>

<!-- ═══════════ TOTAL HIGHLIGHT ═══════════ -->
<div class="total-highlight">
    <table>
        <tr>
            <td>
                <div class="total-hl-label"><?= $isQuote ? 'Valor Total' : 'Monto Total a Pagar' ?></div>
            </td>
            <td class="text-right">
                <div class="total-hl-amount">
                    <span class="total-hl-currency"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?></span>
                    $<?= number_format($invoice['total'] ?? 0, 2) ?>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ═══════════ ITEMS TABLE ═══════════ -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width:28px;">#</th>
            <th>Descripción</th>
            <th class="text-center" style="width:55px;">Cant.</th>
            <th class="text-right" style="width:80px;">Precio</th>
            <th class="text-right" style="width:85px;">Monto</th>
        </tr>
    </thead>
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

<!-- ═══════════ NOTES + TOTALS ═══════════ -->
<table class="bottom-row">
    <tr>
        <!-- Notes -->
        <td style="width:55%;" class="bottom-notes">
            <?php if (!empty($invoice['notes'])): ?>
                <div class="bottom-notes-title">Notas</div>
                <div class="bottom-notes-text"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($invoice['terms'])): ?>
                <div class="bottom-notes-title" style="margin-top:10px;">Términos y Condiciones</div>
                <div class="bottom-notes-text"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></div>
            <?php endif; ?>
        </td>

        <!-- Totals -->
        <td style="width:45%;">
            <table class="totals-mini">
                <tr>
                    <td class="tl">Subtotal</td>
                    <td class="tv">$<?= number_format($invoice['subtotal'] ?? 0, 2) ?></td>
                </tr>
                <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="tl">Descuento</td>
                    <td class="tv" style="color:#00DF83;">-$<?= number_format($invoice['discount_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
                <tr class="sep">
                    <td class="tl">ITBIS (<?= number_format($invoice['tax_rate'] ?? 0, 0) ?>%)</td>
                    <td class="tv">$<?= number_format($invoice['tax_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-row">
                    <td class="tl" style="text-align:right; color:#fff;">TOTAL</td>
                    <td class="tv"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> $<?= number_format($invoice['total'] ?? 0, 2) ?></td>
                </tr>
                <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
                <tr class="paid-row">
                    <td class="tl">Pagado</td>
                    <td class="tv" style="color:#059669;">-$<?= number_format($invoice['amount_paid'], 2) ?></td>
                </tr>
                <tr class="balance-row">
                    <td class="tl" style="color:#991B1B;">Saldo</td>
                    <td class="tv">$<?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </td>
    </tr>
</table>

<!-- ═══════════ FOOTER ═══════════ -->
<div class="footer">
    <div class="footer-thanks">¡Gracias por su preferencia!</div>
    <div class="footer-info">
        <?= htmlspecialchars($company['name'] ?? 'Gridbase') ?>
        <?php if (!empty($company['website'])) echo " &bull; " . htmlspecialchars($company['website']); ?>
        <?php if (!empty($company['email'])) echo " &bull; " . htmlspecialchars($company['email']); ?>
        <?php if (!empty($company['phone'])) echo " &bull; " . htmlspecialchars($company['phone']); ?>
    </div>
</div>

</body>
</html>
