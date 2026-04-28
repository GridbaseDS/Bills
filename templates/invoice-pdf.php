<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #374151;
            line-height: 1.6;
            margin: 0;
            padding: 30px;
        }
        .header {
            width: 100%;
            border-bottom: 3px solid #00D690;
            padding-bottom: 25px;
            margin-bottom: 40px;
        }
        .logo-container {
            float: left;
            width: 50%;
        }
        .company-details {
            float: right;
            width: 50%;
            text-align: right;
            font-size: 13px;
            color: #4B5563;
            line-height: 1.5;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .clear { clear: both; }
        .document-title {
            font-size: 32px;
            font-weight: 800;
            color: #111827;
            text-transform: uppercase;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }
        .info-section {
            width: 100%;
            margin-bottom: 40px;
        }
        .client-info {
            float: left;
            width: 50%;
        }
        .invoice-info {
            float: right;
            width: 45%;
        }
        .info-label {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6B7280;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 15px;
            color: #111827;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .table th {
            background-color: #F9FAFB;
            color: #374151;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 14px 12px;
            text-align: left;
            border-bottom: 2px solid #E5E7EB;
        }
        .table td {
            padding: 14px 12px;
            border-bottom: 1px solid #F3F4F6;
            color: #4B5563;
            font-size: 14px;
        }
        .table tr:last-child td {
            border-bottom: 2px solid #E5E7EB;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .totals-section {
            float: right;
            width: 45%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 10px 12px;
            color: #374151;
            font-size: 15px;
        }
        .total-row {
            font-size: 20px;
            font-weight: bold;
            border-top: 2px solid #111827;
        }
        .total-row td {
            padding-top: 15px;
            padding-bottom: 15px;
            color: #111827;
        }
        .accent { color: #00D690; }
        .notes-section {
            float: left;
            width: 50%;
            font-size: 13px;
            color: #4B5563;
            padding-right: 20px;
            line-height: 1.6;
        }
        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 12px;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 15px;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-paid { background-color: #D1FAE5; color: #065F46; border: 1px solid #34D399; }
        .badge-draft { background-color: #F3F4F6; color: #374151; border: 1px solid #9CA3AF; }
        .badge-overdue { background-color: #FEE2E2; color: #991B1B; border: 1px solid #F87171; }
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
            <img src="data:image/png;base64,<?= $logoData ?>" alt="GridBase" style="height: 55px;">
        <?php else: ?>
            <div style="font-size: 30px; font-weight: 800; color: #00D690; letter-spacing: -1px;">
                Grid<span style="color: #111827;">Base</span>
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
            <div style="margin-top: 4px; font-weight: bold;">RNC/Cédula: <?= htmlspecialchars($company['company_tax_id']) ?></div>
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
        <span class="badge badge-paid" style="float: right; margin-top: 4px;">PAGADA</span>
    <?php endif; ?>
</div>

<div class="info-section">
    <div class="client-info">
        <div class="info-label">Facturado a</div>
        <div class="info-value">
            <div style="font-size: 18px; font-weight: bold; margin-bottom: 4px; color: #111827;">
                <?= htmlspecialchars($invoice['company_name'] ?: $invoice['contact_name']) ?>
            </div>
            <?php if ($invoice['company_name']): ?>
                <div style="color: #4B5563; margin-bottom: 4px;"><?= htmlspecialchars($invoice['contact_name']) ?></div>
            <?php endif; ?>
            
            <div style="color: #4B5563;">
                <?php if(!empty($invoice['address_line1'])): ?>
                    <?= htmlspecialchars($invoice['address_line1']) ?><br>
                <?php endif; ?>
                <?php if(!empty($invoice['city'])): ?>
                    <?= htmlspecialchars($invoice['city']) ?>, <?= htmlspecialchars($invoice['country']) ?><br>
                <?php endif; ?>
                <?php if(!empty($invoice['client_tax_id'])): ?>
                    <div style="margin-top: 6px;"><strong>RNC/Cédula:</strong> <?= htmlspecialchars($invoice['client_tax_id']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="invoice-info">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: right; padding-right: 25px; border-right: 2px solid #F3F4F6;">
                    <div class="info-label">Fecha de Emisión</div>
                    <div class="info-value" style="margin-bottom: 0;"><strong><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></strong></div>
                </td>
                <td style="text-align: right; padding-left: 25px;">
                    <div class="info-label"><?= $dateLabel ?></div>
                    <div class="info-value" style="margin-bottom: 0;"><strong><?= date('d/m/Y', strtotime($dateField)) ?></strong></div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right; padding-top: 25px;">
                    <div class="info-label">Monto Total (<?= htmlspecialchars($invoice['currency']) ?>)</div>
                    <div class="info-value" style="font-size: 28px; font-weight: bold; color: #00D690; margin-bottom: 0;">
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
            <th style="width: 45%;">Descripción</th>
            <th class="text-center" style="width: 15%;">Cant.</th>
            <th class="text-right" style="width: 15%;">Precio</th>
            <th class="text-right" style="width: 20%;">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($invoice['items'] as $index => $item): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td>
                <span style="color: #111827;"><?= nl2br(htmlspecialchars($item['description'])) ?></span>
            </td>
            <td class="text-center"><?= number_format($item['quantity'], 2) ?></td>
            <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
            <td class="text-right" style="font-weight: 500; color: #111827;"><?= number_format($item['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="notes-section">
    <?php if(!empty($invoice['notes'])): ?>
        <div class="info-label">Notas</div>
        <div style="margin-top: 8px; margin-bottom: 20px; color: #4B5563; background: #F9FAFB; padding: 15px; border-radius: 6px; border-left: 3px solid #D1D5DB;">
            <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($invoice['terms'])): ?>
        <div class="info-label">Términos y Condiciones</div>
        <div style="margin-top: 8px; color: #6B7280; font-size: 12px;">
            <?= nl2br(htmlspecialchars($invoice['terms'])) ?>
        </div>
    <?php endif; ?>
</div>

<div class="totals-section">
    <table class="totals-table">
        <tr>
            <td class="text-right" style="color: #6B7280;">Subtotal:</td>
            <td class="text-right" style="width: 120px; font-weight: 500; color: #111827;"><?= number_format($invoice['subtotal'], 2) ?></td>
        </tr>
        <?php if ($invoice['discount_amount'] > 0): ?>
        <tr>
            <td class="text-right" style="color: #6B7280;">Descuento:</td>
            <td class="text-right" style="color: #EF4444; font-weight: 500;">-<?= number_format($invoice['discount_amount'], 2) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($invoice['tax_amount'] > 0): ?>
        <tr>
            <td class="text-right" style="color: #6B7280;">Impuesto (<?= number_format($invoice['tax_rate'], 1) ?>%):</td>
            <td class="text-right" style="font-weight: 500; color: #111827;"><?= number_format($invoice['tax_amount'], 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
            <td class="text-right">Total:</td>
            <td class="text-right accent"><?= htmlspecialchars($invoice['currency']) ?> <?= number_format($invoice['total'], 2) ?></td>
        </tr>
        <?php if (!$isQuote && $invoice['amount_paid'] > 0): ?>
        <tr>
            <td class="text-right" style="padding-top: 20px; color: #6B7280;">Monto Pagado:</td>
            <td class="text-right" style="padding-top: 20px; color: #10B981; font-weight: 500;">-<?= number_format($invoice['amount_paid'], 2) ?></td>
        </tr>
        <tr>
            <td class="text-right" style="font-weight: bold; font-size: 16px; color: #111827;">Balance Pendiente:</td>
            <td class="text-right" style="font-weight: bold; font-size: 16px; color: #EF4444;"><?= htmlspecialchars($invoice['currency']) ?> <?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
<div class="clear"></div>

<div class="footer">
    <?= htmlspecialchars($company['company_name'] ?? 'Gridbase') ?> 
    <?php if(!empty($company['company_website'])) echo " &bull; " . htmlspecialchars($company['company_website']); ?>
    <?php if(!empty($company['company_email'])) echo " &bull; " . htmlspecialchars($company['company_email']); ?>
</div>

</body>
</html>
