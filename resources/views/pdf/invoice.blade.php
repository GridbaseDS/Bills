<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isQuote ?? false ? 'Cotización' : 'Factura' }}</title>
    <style>
        @page {
            margin: 0;
            size: a4 portrait;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #333333;
            line-height: 1.3;
            background: #FFFFFF;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 180px;
            height: 100%;
            background: #0B484C;
            padding: 20px 14px;
        }
        .sidebar-logo { margin-bottom: 12px; text-align: center; }
        .sidebar-logo img { max-width: 140px; height: auto; }
        .sidebar-divider { width: 26px; height: 2px; background: #00DF83; margin: 8px 0; }
        .sidebar-label {
            font-size: 6.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #00DF83;
            margin-bottom: 2px;
            font-weight: bold;
        }
        .sidebar-value {
            font-size: 8px;
            color: rgba(255,255,255,0.6);
            margin-bottom: 1px;
            line-height: 1.35;
        }
        .sidebar-value strong { font-size: 9px; color: #FFFFFF; }
        .sidebar-section { margin-bottom: 10px; }
        .sidebar-sep { margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-dot {
            display: inline-block;
            width: 4px; height: 4px;
            background: #00DF83;
            border-radius: 50%;
            margin-right: 4px;
        }

        .main-content {
            margin-left: 180px;
            padding: 18px 22px 15px 22px;
        }

        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #0B484C;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0;
        }
        .doc-number-line {
            font-size: 8px;
            color: #7E7E7E;
            margin-bottom: 6px;
        }
        .doc-number-line strong { color: #0B484C; }

        .total-due-box {
            background: #F6F5F2;
            border: 1px solid rgba(0,0,0,0.06);
            border-left: 3px solid #0B484C;
            border-radius: 5px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .total-due-label { font-size: 7px; text-transform: uppercase; color: #7E7E7E; letter-spacing: 0.8px; margin-bottom: 1px; }
        .total-due-amount { font-size: 18px; font-weight: bold; color: #0B484C; }
        .total-due-currency { font-size: 10px; color: #7E7E7E; font-weight: normal; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .items-table thead tr { background: #0B484C; }
        .items-table th {
            color: #FFFFFF;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 5px 6px;
            text-align: left;
        }
        .items-table th.text-right { text-align: right; }
        .items-table tbody tr { border-bottom: 1px solid rgba(0,0,0,0.05); }
        .items-table tbody tr:nth-child(even) { background: #FAFAF8; }
        .items-table td { padding: 5px 6px; vertical-align: top; }
        .item-number {
            display: inline-block;
            width: 16px; height: 16px;
            background: #0B484C;
            color: #FFFFFF;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            line-height: 16px;
            border-radius: 3px;
        }
        .item-desc { font-weight: bold; color: #1a1a1a; font-size: 9px; }
        .item-detail { font-size: 7.5px; color: #7E7E7E; margin-top: 1px; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        .totals-wrapper { width: 100%; margin-bottom: 6px; }
        .totals-table { width: 210px; float: right; border-collapse: collapse; }
        .totals-table td { padding: 2px 6px; font-size: 8px; }
        .totals-table .total-label { text-align: right; color: #7E7E7E; }
        .totals-table .total-value { text-align: right; font-weight: bold; width: 80px; color: #333; }
        .grand-total-row { background: #0B484C; }
        .grand-total-row td {
            color: #FFFFFF !important;
            font-size: 10px !important;
            font-weight: bold !important;
            padding: 5px 6px !important;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 100px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-paid { background: rgba(0,223,131,0.12); color: #0B484C; }
        .badge-overdue { background: rgba(251,113,133,0.12); color: #d32f2f; }
        .badge-draft { background: rgba(0,0,0,0.06); color: #7E7E7E; }
        .badge-sent { background: rgba(56,189,248,0.12); color: #0277bd; }

        .terms-section { clear: both; padding-top: 6px; border-top: 1px solid rgba(0,0,0,0.06); margin-top: 4px; }
        .terms-title { font-size: 7px; font-weight: bold; text-transform: uppercase; color: #0B484C; letter-spacing: 0.8px; margin-bottom: 2px; }
        .terms-text { font-size: 7.5px; color: #7E7E7E; line-height: 1.4; }

        .footer {
            position: fixed;
            bottom: 8px;
            right: 22px;
            left: 200px;
            text-align: center;
            font-size: 6.5px;
            color: #999;
            border-top: 1px solid rgba(0,0,0,0.06);
            padding-top: 3px;
        }
        .footer-accent { color: #0B484C; font-weight: bold; }
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
$logoUrl = 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png';
?>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= $logoUrl ?>" alt="GridBase">
    </div>
    <div class="sidebar-divider"></div>

    <div class="sidebar-section">
        <div class="sidebar-label">Facturado a</div>
        <div class="sidebar-value"><strong><?= htmlspecialchars($client['company_name'] ?: $client['contact_name']) ?></strong></div>
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

    <div class="sidebar-sep">
        <div class="sidebar-label">Emisor</div>
        <?php if (!empty($company['name'])): ?>
            <div class="sidebar-value" style="margin-top: 4px;">
                <span class="sidebar-dot"></span>
                <span style="font-size:7px;color:rgba(255,255,255,.4);">Empresa</span><br>
                <span style="margin-left:8px;color:#fff;"><?= htmlspecialchars($company['name']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($company['tax_id'])): ?>
            <div class="sidebar-value">
                <span class="sidebar-dot"></span>
                <span style="font-size:7px;color:rgba(255,255,255,.4);">RNC</span><br>
                <span style="margin-left:8px;color:#fff;"><?= htmlspecialchars($company['tax_id']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($company['email'])): ?>
            <div class="sidebar-value">
                <span class="sidebar-dot"></span>
                <span style="font-size:7px;color:rgba(255,255,255,.4);">Email</span><br>
                <span style="margin-left:8px;color:#fff;"><?= htmlspecialchars($company['email']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($company['phone'])): ?>
            <div class="sidebar-value">
                <span class="sidebar-dot"></span>
                <span style="font-size:7px;color:rgba(255,255,255,.4);">Tel</span><br>
                <span style="margin-left:8px;color:#fff;"><?= htmlspecialchars($company['phone']) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">
    <div class="doc-title"><?= $docName ?></div>
    <div class="doc-number-line">
        N° <strong><?= htmlspecialchars($docNum) ?></strong>
        · Emisión: <?= !empty($invoice['issue_date']) ? date('d/m/Y', strtotime($invoice['issue_date'])) : '' ?>
        · <?= $dateLabel ?>: <strong><?= !empty($dateField) ? date('d/m/Y', strtotime($dateField)) : '' ?></strong>
        <?php if (!$isQuote && !empty($invoice['status'])): ?>
            <?php
            $badgeClass = 'badge-draft'; $badgeText = ucfirst($invoice['status']);
            if ($invoice['status'] === 'paid') { $badgeClass = 'badge-paid'; $badgeText = 'PAGADA'; }
            elseif ($invoice['status'] === 'overdue') { $badgeClass = 'badge-overdue'; $badgeText = 'VENCIDA'; }
            elseif ($invoice['status'] === 'sent') { $badgeClass = 'badge-sent'; $badgeText = 'ENVIADA'; }
            elseif ($invoice['status'] === 'draft') { $badgeText = 'BORRADOR'; }
            ?>
            <span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
        <?php endif; ?>
    </div>

    <div class="total-due-box">
        <div class="total-due-label">Monto Total</div>
        <div class="total-due-amount">
            <span class="total-due-currency"><?= htmlspecialchars($invoice['currency'] ?? 'USD') ?></span>
            $<?= number_format($invoice['total'] ?? 0, 2) ?>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Descripción</th>
                <th class="text-right" style="width:50px;">Cant</th>
                <th class="text-right" style="width:65px;">Precio</th>
                <th class="text-right" style="width:70px;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($invoice['items'] ?? $items ?? []) as $index => $item): ?>
            <tr>
                <td class="text-center"><span class="item-number"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></span></td>
                <td>
                    <div class="item-desc"><?= htmlspecialchars($item['description']) ?></div>
                </td>
                <td class="text-right"><?= number_format($item['quantity'], 0) ?></td>
                <td class="text-right">$<?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-right" style="font-weight:bold;">$<?= number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-wrapper">
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal</td>
                <td class="total-value">$<?= number_format($invoice['subtotal'] ?? 0, 2) ?></td>
            </tr>
            <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
            <tr>
                <td class="total-label">Descuento</td>
                <td class="total-value" style="color:#00DF83;">-$<?= number_format($invoice['discount_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
            <tr>
                <td class="total-label">ITBIS (<?= number_format($invoice['tax_rate'] ?? 0, 0) ?>%)</td>
                <td class="total-value">$<?= number_format($invoice['tax_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="grand-total-row">
                <td style="text-align:right;">Total</td>
                <td style="text-align:right;"><?= htmlspecialchars($invoice['currency'] ?? 'USD') ?> $<?= number_format($invoice['total'] ?? 0, 2) ?></td>
            </tr>
            <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
            <tr>
                <td class="total-label" style="padding-top:5px;">Pagado</td>
                <td class="total-value" style="padding-top:5px;color:#00DF83;">-$<?= number_format($invoice['amount_paid'], 2) ?></td>
            </tr>
            <tr>
                <td class="total-label" style="font-weight:bold;color:#0B484C;">Balance</td>
                <td class="total-value" style="font-weight:bold;color:#d32f2f;">$<?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <div class="clear"></div>

    <?php if (!empty($invoice['notes']) || !empty($invoice['terms'])): ?>
    <div class="terms-section">
        <?php if (!empty($invoice['notes'])): ?>
            <div class="terms-title">Notas</div>
            <div class="terms-text"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($invoice['terms'])): ?>
            <div class="terms-title" style="margin-top:6px;">Términos y Condiciones</div>
            <div class="terms-text"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="footer">
    <span class="footer-accent"><?= htmlspecialchars($company['name'] ?? 'Gridbase') ?></span>
    <?php if (!empty($company['website'])) echo " · " . htmlspecialchars($company['website']); ?>
    <?php if (!empty($company['email'])) echo " · " . htmlspecialchars($company['email']); ?>
    <?php if (!empty($company['phone'])) echo " · " . htmlspecialchars($company['phone']); ?>
</div>

</body>
</html>
