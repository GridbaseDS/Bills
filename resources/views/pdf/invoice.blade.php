<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isQuote ?? false ? 'Cotización' : 'Factura' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 40px 50px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #1f2124;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        table { width: 100%; border-collapse: collapse; }

        /* ── Utilidades ── */
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .color-accent { color: #00DF83; }
        .clear { clear: both; }

        /* ── Cabecera ── */
        .header-table { margin-bottom: 35px; }
        .header-table td { vertical-align: top; }

        .company-logo {
            font-size: 28px;
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .company-logo span { color: #00DF83; }

        .company-details {
            color: #7E7E7E;
            font-size: 11px;
            line-height: 1.7;
        }

        .invoice-title {
            font-size: 36px;
            color: #0B484C;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .invoice-meta {
            font-size: 11px;
            color: #1f2124;
            line-height: 1.8;
        }
        .invoice-meta strong {
            color: #0B484C;
            font-weight: 600;
        }

        /* ── Status Badge ── */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        .badge-paid    { background: rgba(0,223,131,0.12); color: #0B484C; }
        .badge-overdue { background: rgba(251,113,133,0.12); color: #d32f2f; }
        .badge-draft   { background: rgba(0,0,0,0.06); color: #7E7E7E; }
        .badge-sent    { background: rgba(56,189,248,0.12); color: #0277bd; }

        /* ── Facturar A ── */
        .bill-to-table { margin-bottom: 35px; width: 100%; }

        .bill-to-box {
            background-color: #F6F5F2;
            padding: 18px 22px;
            border-radius: 8px;
            border-left: 4px solid #0B484C;
        }
        .bill-to-title {
            font-size: 13px;
            font-weight: 700;
            color: #0B484C;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .client-details {
            font-size: 12px;
            line-height: 1.7;
            color: #1f2124;
        }
        .client-name {
            font-size: 14px;
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 4px;
        }

        /* ── Tabla de Artículos ── */
        .items-table { margin-bottom: 25px; }

        .items-table th {
            background-color: #0B484C;
            color: #FFFFFF;
            padding: 10px 12px;
            text-align: left;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #00DF83;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            color: #1f2124;
            vertical-align: middle;
            font-size: 12px;
        }

        .items-table tr:nth-child(even) td {
            background-color: #FAFAF8;
        }

        .item-number {
            display: inline-block;
            width: 18px; height: 18px;
            background: #0B484C;
            color: #FFFFFF;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            line-height: 18px;
            border-radius: 3px;
        }

        .item-name {
            font-weight: 700;
            color: #0B484C;
            font-size: 12px;
        }

        /* ── Totales ── */
        .totals-wrapper { width: 100%; margin-bottom: 10px; }

        .notes-section {
            width: 50%;
            float: left;
            padding-right: 30px;
        }
        .notes-title {
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 5px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .notes-text {
            font-size: 11px;
            color: #7E7E7E;
            line-height: 1.6;
        }

        .totals-table {
            width: 45%;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 12px;
            color: #1f2124;
            font-size: 12px;
        }
        .totals-table .total-label {
            text-align: right;
            font-weight: 500;
            color: #7E7E7E;
        }
        .totals-table .total-value {
            text-align: right;
            font-weight: 600;
            color: #1f2124;
            width: 100px;
        }
        .totals-table .total-border td {
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }

        .grand-total-row td {
            background-color: #0B484C;
            color: #FFFFFF !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            padding: 12px !important;
        }
        .grand-total-row .total-value {
            color: #00DF83 !important;
        }

        .balance-row td {
            background-color: #FEF2F2;
            font-weight: 700;
        }
        .balance-row .total-label { color: #991B1B; }
        .balance-row .total-value { color: #DC2626; }

        /* ── Footer ── */
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #7E7E7E;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            padding-top: 18px;
            clear: both;
        }
        .footer-thank-you {
            color: #0B484C;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<?php
$isQuote   = isset($is_quote) && $is_quote;
$docName   = $isQuote ? 'Cotización' : 'Factura';
$docNum    = $isQuote ? ($invoice['quote_number'] ?? '') : ($invoice['invoice_number'] ?? '');
$dateLabel = $isQuote ? 'Válida Hasta' : 'Fecha de Vencimiento';
$dateField = $isQuote ? ($invoice['expiry_date'] ?? $invoice['due_date'] ?? '') : ($invoice['due_date'] ?? '');
$logoUrl   = 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png';
?>

    <!-- ═══════════ CABECERA ═══════════ -->
    <table class="header-table">
        <tr>
            <td width="50%">
                <div class="company-logo" style="margin-bottom:8px;">
                    <img src="<?= $logoUrl ?>" alt="GridBase" style="height: 45px;">
                </div>
                <div class="company-details">
                    <?php if (!empty($company['name'])): ?>
                        <?= htmlspecialchars($company['name']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['address'])): ?>
                        <?= htmlspecialchars($company['address']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['city'])): ?>
                        <?= htmlspecialchars($company['city']) ?><?= !empty($company['country']) ? ', ' . htmlspecialchars($company['country']) : '' ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['email'])): ?>
                        <?= htmlspecialchars($company['email']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['phone'])): ?>
                        <?= htmlspecialchars($company['phone']) ?>
                    <?php endif; ?>
                </div>
            </td>
            <td width="50%" class="text-right">
                <div class="invoice-title"><?= $docName ?></div>
                <div class="invoice-meta">
                    <strong><?= $docName ?> Nº:</strong> <?= htmlspecialchars($docNum) ?><br>
                    <strong>Fecha de Emisión:</strong> <?= !empty($invoice['issue_date']) ? date('d/m/Y', strtotime($invoice['issue_date'])) : '' ?><br>
                    <strong><?= $dateLabel ?>:</strong> <?= !empty($dateField) ? date('d/m/Y', strtotime($dateField)) : '' ?><br>
                    <?php if (!empty($company['tax_id'])): ?>
                        <strong>RNC:</strong> <?= htmlspecialchars($company['tax_id']) ?>
                    <?php endif; ?>
                </div>
                <?php if (!$isQuote && !empty($invoice['status'])): ?>
                    <?php
                    $badgeClass = 'badge-draft'; $badgeText = 'BORRADOR';
                    if ($invoice['status'] === 'paid')    { $badgeClass = 'badge-paid';    $badgeText = 'PAGADA'; }
                    elseif ($invoice['status'] === 'overdue') { $badgeClass = 'badge-overdue'; $badgeText = 'VENCIDA'; }
                    elseif ($invoice['status'] === 'sent' || $invoice['status'] === 'pending') { $badgeClass = 'badge-sent'; $badgeText = 'ENVIADA'; }
                    ?>
                    <span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- ═══════════ FACTURAR A ═══════════ -->
    <table class="bill-to-table">
        <tr>
            <td width="100%">
                <div class="bill-to-box">
                    <div class="bill-to-title">Facturar A:</div>
                    <div class="client-details">
                        <div class="client-name"><?= htmlspecialchars($client['company_name'] ?: $client['contact_name']) ?></div>
                        <?php if (!empty($client['company_name']) && !empty($client['contact_name'])): ?>
                            Atención: <?= htmlspecialchars($client['contact_name']) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($client['phone'])): ?>
                            Tel: <?= htmlspecialchars($client['phone']) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($client['email'])): ?>
                            <?= htmlspecialchars($client['email']) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($client['address_line1'])): ?>
                            <?= htmlspecialchars($client['address_line1']) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($client['city'])): ?>
                            <?= htmlspecialchars($client['city']) ?><?= !empty($client['country']) ? ', ' . htmlspecialchars($client['country']) : '' ?><br>
                        <?php endif; ?>
                        <?php if (!empty($client['tax_id'])): ?>
                            RNC/Cédula: <?= htmlspecialchars($client['tax_id']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- ═══════════ TABLA DE ARTÍCULOS ═══════════ -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Descripción del Servicio</th>
                <th class="text-center" style="width:60px;">Cant.</th>
                <th class="text-right" style="width:90px;">Precio Unit.</th>
                <th class="text-right" style="width:90px;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($invoice['items'] ?? $items ?? []) as $index => $item): ?>
            <tr>
                <td class="text-center">
                    <span class="item-number"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></span>
                </td>
                <td>
                    <span class="item-name"><?= htmlspecialchars($item['description']) ?></span>
                </td>
                <td class="text-center"><?= number_format($item['quantity'], 0) ?></td>
                <td class="text-right">$<?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-right" style="font-weight:700;">$<?= number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ═══════════ NOTAS + TOTALES ═══════════ -->
    <div class="totals-wrapper">

        <!-- Notas (Lado Izquierdo) -->
        <div class="notes-section">
            <?php if (!empty($invoice['notes'])): ?>
                <div class="notes-title">Notas</div>
                <div class="notes-text"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
                <br>
            <?php endif; ?>
            <?php if (!empty($invoice['terms'])): ?>
                <div class="notes-title">Términos y Condiciones</div>
                <div class="notes-text"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></div>
            <?php endif; ?>
        </div>

        <!-- Tabla de Totales (Lado Derecho) -->
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal:</td>
                <td class="total-value">$<?= number_format($invoice['subtotal'] ?? 0, 2) ?></td>
            </tr>
            <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
            <tr class="total-border">
                <td class="total-label">ITBIS (<?= number_format($invoice['tax_rate'] ?? 0, 0) ?>%):</td>
                <td class="total-value">$<?= number_format($invoice['tax_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
            <tr>
                <td class="total-label">Descuento:</td>
                <td class="total-value color-accent">-$<?= number_format($invoice['discount_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="grand-total-row">
                <td class="total-label" style="text-align:right; color:#fff;">TOTAL A PAGAR:</td>
                <td class="total-value"><?= htmlspecialchars($invoice['currency'] ?? 'USD') ?> $<?= number_format($invoice['total'] ?? 0, 2) ?></td>
            </tr>
            <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
            <tr>
                <td class="total-label" style="padding-top:8px;">Monto Pagado:</td>
                <td class="total-value" style="padding-top:8px; color:#059669;">-$<?= number_format($invoice['amount_paid'], 2) ?></td>
            </tr>
            <tr class="balance-row">
                <td class="total-label">Saldo Pendiente:</td>
                <td class="total-value">$<?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <div class="clear"></div>

    <!-- ═══════════ FOOTER ═══════════ -->
    <div class="footer">
        <div class="footer-thank-you">¡Gracias por hacer negocios con nosotros!</div>
        <p>
            <?= htmlspecialchars($company['name'] ?? 'Gridbase') ?>
            <?php if (!empty($company['website'])) echo " &bull; " . htmlspecialchars($company['website']); ?>
            <?php if (!empty($company['email'])) echo " &bull; " . htmlspecialchars($company['email']); ?>
            <?php if (!empty($company['phone'])) echo " &bull; " . htmlspecialchars($company['phone']); ?>
        </p>
    </div>

</body>
</html>
