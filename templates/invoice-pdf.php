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
            padding: 40px 30px;
        }
        
        /* Typography */
        .company-name {
            font-size: 26px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .document-title {
            font-size: 34px;
            font-weight: 800;
            color: #111827;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 1px;
        }
        .info-label {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6B7280;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .accent { color: #00D690; }

        /* Tables */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }
        .layout-table td {
            vertical-align: top;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th {
            background-color: #F9FAFB;
            color: #374151;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 16px 14px;
            text-align: left;
            border-bottom: 2px solid #E5E7EB;
        }
        .items-table td {
            padding: 18px 14px;
            border-bottom: 1px solid #F3F4F6;
            color: #4B5563;
            font-size: 14px;
        }
        .items-table tr:last-child td {
            border-bottom: 2px solid #E5E7EB;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 14px 14px;
            color: #374151;
            font-size: 15px;
        }
        .total-row {
            font-size: 22px;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #111827;
            padding-top: 20px;
            padding-bottom: 20px;
            color: #111827;
        }

        /* Utilities */
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-paid { background-color: #D1FAE5; color: #065F46; border: 1px solid #34D399; }
        
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 12px;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 20px;
        }
        
        /* Spacers */
        .spacer-10 { height: 10px; }
        .spacer-20 { height: 20px; }
        .spacer-30 { height: 30px; }
        .spacer-40 { height: 40px; }
        .spacer-50 { height: 50px; }
    </style>
</head>
<body>

<!-- HEADER -->
<div style="border-bottom: 3px solid #00D690; padding-bottom: 30px;">
    <table class="layout-table">
        <tr>
            <td style="width: 50%;">
                <?php
                $logoPath = __DIR__ . '/../assets/img/logo.png';
                if (file_exists($logoPath)):
                    $logoData = base64_encode(file_get_contents($logoPath));
                ?>
                    <img src="data:image/png;base64,<?= $logoData ?>" alt="GridBase" style="height: 60px;">
                <?php else: ?>
                    <div style="font-size: 32px; font-weight: 800; color: #00D690; letter-spacing: -1px;">
                        Grid<span style="color: #111827;">Base</span>
                    </div>
                <?php endif; ?>
            </td>
            <td style="width: 50%; text-align: right; color: #4B5563; font-size: 13px; line-height: 1.6;">
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
                    <div style="margin-top: 6px; font-weight: bold; color: #111827;">RNC/Cédula: <?= htmlspecialchars($company['company_tax_id']) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<div class="spacer-40"></div>

<!-- DOCUMENT TITLE -->
<?php 
$isQuote = isset($document_type) && $document_type === 'quote';
$docName = $isQuote ? 'Cotización' : 'Factura';
$docNum  = $isQuote ? $invoice['quote_number'] : $invoice['invoice_number'];
$dateLabel = $isQuote ? 'Válida Hasta' : 'Fecha de Vencimiento';
$dateField = $isQuote ? $invoice['expiry_date'] : $invoice['due_date'];
?>

<table class="layout-table">
    <tr>
        <td style="vertical-align: middle;">
            <div class="document-title">
                <?= $docName ?> <span class="accent">#<?= htmlspecialchars($docNum) ?></span>
            </div>
        </td>
        <td style="text-align: right; vertical-align: middle;">
            <?php if (!$isQuote && $invoice['status'] === 'paid'): ?>
                <span class="badge badge-paid">PAGADA</span>
            <?php endif; ?>
        </td>
    </tr>
</table>

<div class="spacer-40"></div>

<!-- INFO SECTION -->
<table class="layout-table">
    <tr>
        <!-- Billed To -->
        <td style="width: 50%; padding-right: 20px;">
            <div class="info-label">Facturado a</div>
            <div style="font-size: 18px; font-weight: bold; margin-bottom: 6px; color: #111827;">
                <?= htmlspecialchars($invoice['company_name'] ?: $invoice['contact_name']) ?>
            </div>
            <?php if ($invoice['company_name']): ?>
                <div style="color: #4B5563; margin-bottom: 6px; font-size: 15px;"><?= htmlspecialchars($invoice['contact_name']) ?></div>
            <?php endif; ?>
            
            <div style="color: #4B5563; line-height: 1.6; font-size: 15px;">
                <?php if(!empty($invoice['address_line1'])): ?>
                    <?= htmlspecialchars($invoice['address_line1']) ?><br>
                <?php endif; ?>
                <?php if(!empty($invoice['city'])): ?>
                    <?= htmlspecialchars($invoice['city']) ?>, <?= htmlspecialchars($invoice['country']) ?><br>
                <?php endif; ?>
                <?php if(!empty($invoice['client_tax_id'])): ?>
                    <div style="margin-top: 10px;"><strong>RNC/Cédula:</strong> <?= htmlspecialchars($invoice['client_tax_id']) ?></div>
                <?php endif; ?>
            </div>
        </td>
        <!-- Invoice Details -->
        <td style="width: 50%;">
            <div style="background-color: #F9FAFB; padding: 25px; border-radius: 8px; border: 1px solid #E5E7EB;">
                <table class="layout-table">
                    <tr>
                        <td style="width: 50%; padding-bottom: 20px;">
                            <div class="info-label">Fecha de Emisión</div>
                            <div style="font-size: 16px; color: #111827;"><strong><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></strong></div>
                        </td>
                        <td style="width: 50%; padding-bottom: 20px;">
                            <div class="info-label"><?= $dateLabel ?></div>
                            <div style="font-size: 16px; color: #111827;"><strong><?= date('d/m/Y', strtotime($dateField)) ?></strong></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border-top: 2px solid #E5E7EB; padding-top: 20px;">
                            <div class="info-label">Monto Total (<?= htmlspecialchars($invoice['currency']) ?>)</div>
                            <div style="font-size: 32px; font-weight: bold; color: #00D690; margin-top: 5px;">
                                <?= number_format($invoice['total'], 2) ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="spacer-50"></div>

<!-- ITEMS TABLE -->
<table class="items-table">
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
                <span style="color: #111827; font-size: 15px; font-weight: 500;"><?= nl2br(htmlspecialchars($item['description'])) ?></span>
            </td>
            <td class="text-center"><?= number_format($item['quantity'], 2) ?></td>
            <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
            <td class="text-right" style="font-weight: bold; color: #111827;"><?= number_format($item['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="spacer-40"></div>

<!-- NOTES & TOTALS -->
<table class="layout-table">
    <tr>
        <td style="width: 50%; padding-right: 40px;">
            <?php if(!empty($invoice['notes'])): ?>
                <div class="info-label">Notas</div>
                <div style="margin-top: 10px; margin-bottom: 25px; color: #4B5563; background: #F9FAFB; padding: 20px; border-radius: 8px; border-left: 4px solid #D1D5DB; font-size: 14px; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($invoice['terms'])): ?>
                <div class="info-label">Términos y Condiciones</div>
                <div style="margin-top: 10px; color: #6B7280; font-size: 13px; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($invoice['terms'])) ?>
                </div>
            <?php endif; ?>
        </td>
        
        <td style="width: 50%;">
            <table class="totals-table">
                <tr>
                    <td class="text-right" style="color: #6B7280;">Subtotal:</td>
                    <td class="text-right" style="width: 140px; font-weight: 500; color: #111827;"><?= number_format($invoice['subtotal'], 2) ?></td>
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
                    <td class="text-right" style="padding-top: 25px; color: #6B7280;">Monto Pagado:</td>
                    <td class="text-right" style="padding-top: 25px; color: #10B981; font-weight: 500;">-<?= number_format($invoice['amount_paid'], 2) ?></td>
                </tr>
                <tr>
                    <td class="text-right" style="font-weight: bold; font-size: 18px; color: #111827;">Balance Pendiente:</td>
                    <td class="text-right" style="font-weight: bold; font-size: 18px; color: #EF4444;"><?= htmlspecialchars($invoice['currency']) ?> <?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </td>
    </tr>
</table>

<div class="footer">
    <?= htmlspecialchars($company['company_name'] ?? 'Gridbase') ?> 
    <?php if(!empty($company['company_website'])) echo " &bull; " . htmlspecialchars($company['company_website']); ?>
    <?php if(!empty($company['company_email'])) echo " &bull; " . htmlspecialchars($company['company_email']); ?>
</div>

</body>
</html>
