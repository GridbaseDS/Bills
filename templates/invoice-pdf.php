<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #333333;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #00D690;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo-container {
            float: left;
            width: 50%;
        }
        .company-details {
            float: right;
            width: 50%;
            text-align: right;
            font-size: 11px;
            color: #666666;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1A1D26;
            margin-bottom: 5px;
        }
        .clear { clear: both; }
        .document-title {
            font-size: 28px;
            font-weight: bold;
            color: #1A1D26;
            text-transform: uppercase;
            margin-bottom: 20px;
            letter-spacing: 2px;
        }
        .info-section {
            width: 100%;
            margin-bottom: 30px;
        }
        .client-info {
            float: left;
            width: 50%;
        }
        .invoice-info {
            float: right;
            width: 40%;
        }
        .info-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #94A3B8;
            margin-bottom: 3px;
        }
        .info-value {
            margin-bottom: 15px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .table th {
            background-color: #F0FAF6;
            color: #0F6B5A;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #B2E0D4;
        }
        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #E2E8F0;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .totals-section {
            float: right;
            width: 40%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 10px;
        }
        .total-row {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #1A1D26;
        }
        .accent { color: #00D690; }
        .notes-section {
            float: left;
            width: 55%;
            font-size: 11px;
            color: #666666;
        }
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #94A3B8;
            border-top: 1px solid #E2E8F0;
            padding-top: 10px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid { background-color: #ECFDF5; color: #10B981; border: 1px solid #10B981; }
        .badge-draft { background-color: #F8F9FB; color: #64748B; border: 1px solid #64748B; }
        .badge-overdue { background-color: #FEF2F2; color: #EF4444; border: 1px solid #EF4444; }
    </style>
</head>
<body>

<div class="header">
    <div class="logo-container">
        <?php
        $logoPath = __DIR__ . '/../assets/img/logo.png';
        if (file_exists($logoPath)):
            $logoData = base64_encode(file_get_contents($logoPath));
        ?>
            <img src="data:image/png;base64,<?= $logoData ?>" alt="GridBase" style="height: 50px;">
        <?php else: ?>
            <div style="font-size: 26px; font-weight: bold; color: #00D690;">
                Grid<span style="color: #1A1D26;">Base</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="company-details">
        <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'Gridbase Digital Solutions') ?></div>
        <?php if(!empty($company['company_address'])): ?>
            <div><?= htmlspecialchars($company['company_address']) ?></div>
        <?php endif; ?>
        <?php if(!empty($company['company_city'])): ?>
            <div><?= htmlspecialchars($company['company_city']) ?><?= !empty($company['company_country']) ? ', ' . htmlspecialchars($company['company_country']) : '' ?></div>
        <?php endif; ?>
        <?php if(!empty($company['company_email'])): ?>
            <div><?= htmlspecialchars($company['company_email']) ?></div>
        <?php endif; ?>
        <?php if(!empty($company['company_tax_id'])): ?>
            <div>RNC/Cédula: <?= htmlspecialchars($company['company_tax_id']) ?></div>
        <?php endif; ?>
    </div>
    <div class="clear"></div>
</div>

<?php 
$isQuote = isset($document_type) && $document_type === 'quote';
$docName = $isQuote ? 'Cotización' : 'Factura';
$docNum  = $isQuote ? $invoice['quote_number'] : $invoice['invoice_number'];
$dateLabel = $isQuote ? 'Válida Hasta' : 'Fecha de Vencimiento';
$dateField = $isQuote ? $invoice['expiry_date'] : $invoice['due_date'];
?>

<div class="document-title">
    <?= $docName ?> <span class="accent">#<?= htmlspecialchars($docNum) ?></span>
    
    <?php if (!$isQuote && $invoice['status'] === 'paid'): ?>
        <span class="badge badge-paid" style="float: right; margin-top: 5px;">PAGADA</span>
    <?php endif; ?>
</div>

<div class="info-section">
    <div class="client-info">
        <div class="info-label">Facturado a</div>
        <div class="info-value">
            <strong><?= htmlspecialchars($invoice['company_name'] ?: $invoice['contact_name']) ?></strong><br>
            <?php if ($invoice['company_name']): ?>
                <?= htmlspecialchars($invoice['contact_name']) ?><br>
            <?php endif; ?>
            <?php if(!empty($invoice['address_line1'])): ?>
                <?= htmlspecialchars($invoice['address_line1']) ?><br>
            <?php endif; ?>
            <?php if(!empty($invoice['city'])): ?>
                <?= htmlspecialchars($invoice['city']) ?>, <?= htmlspecialchars($invoice['country']) ?><br>
            <?php endif; ?>
            <?php if(!empty($invoice['client_tax_id'])): ?>
                RNC/Cédula: <?= htmlspecialchars($invoice['client_tax_id']) ?><br>
            <?php endif; ?>
        </div>
    </div>
    <div class="invoice-info">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: right; padding-right: 15px;">
                    <div class="info-label">Fecha de Emisión</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></div>
                </td>
                <td style="text-align: right;">
                    <div class="info-label"><?= $dateLabel ?></div>
                    <div class="info-value"><strong><?= date('d/m/Y', strtotime($dateField)) ?></strong></div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right; padding-top: 15px;">
                    <div class="info-label">Monto Total (<?= htmlspecialchars($invoice['currency']) ?>)</div>
                    <div class="info-value" style="font-size: 20px; font-weight: bold; color: #00D690;">
                        <?= number_format($invoice['total'], 2) ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>
</div>

<table class="table">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 50%;">Descripción</th>
            <th class="text-right" style="width: 15%;">Cant.</th>
            <th class="text-right" style="width: 15%;">Precio</th>
            <th class="text-right" style="width: 15%;">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($invoice['items'] as $index => $item): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
            <td class="text-right"><?= number_format($item['quantity'], 2) ?></td>
            <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
            <td class="text-right"><?= number_format($item['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="notes-section">
    <?php if(!empty($invoice['notes'])): ?>
        <div class="info-label">Notas</div>
        <p style="margin-top: 5px;"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
    <?php endif; ?>
    
    <?php if(!empty($invoice['terms'])): ?>
        <div class="info-label" style="margin-top: 15px;">Términos y Condiciones</div>
        <p style="margin-top: 5px;"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></p>
    <?php endif; ?>
</div>

<div class="totals-section">
    <table class="totals-table">
        <tr>
            <td class="text-right">Subtotal:</td>
            <td class="text-right" style="width: 100px;"><?= number_format($invoice['subtotal'], 2) ?></td>
        </tr>
        <?php if ($invoice['discount_amount'] > 0): ?>
        <tr>
            <td class="text-right">Descuento:</td>
            <td class="text-right">-<?= number_format($invoice['discount_amount'], 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['tax_amount'] > 0): ?>
        <tr>
            <td class="text-right">Impuesto (<?= number_format($invoice['tax_rate'], 1) ?>%):</td>
            <td class="text-right"><?= number_format($invoice['tax_amount'], 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td class="text-right">Total:</td>
            <td class="text-right accent"><?= htmlspecialchars($invoice['currency']) ?> <?= number_format($invoice['total'], 2) ?></td>
        </tr>
        <?php if (!$isQuote && $invoice['amount_paid'] > 0): ?>
        <tr>
            <td class="text-right" style="padding-top: 15px;">Monto Pagado:</td>
            <td class="text-right" style="padding-top: 15px;">-<?= number_format($invoice['amount_paid'], 2) ?></td>
        </tr>
        <tr>
            <td class="text-right" style="font-weight: bold;">Balance Pendiente:</td>
            <td class="text-right" style="font-weight: bold;"><?= htmlspecialchars($invoice['currency']) ?> <?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
<div class="clear"></div>

<div class="footer">
    <?= htmlspecialchars($company['company_name'] ?? 'Gridbase') ?> 
    <?php if(!empty($company['company_website'])) echo " | " . htmlspecialchars($company['company_website']); ?>
    <?php if(!empty($company['company_email'])) echo " | " . htmlspecialchars($company['company_email']); ?>
</div>

</body>
</html>
