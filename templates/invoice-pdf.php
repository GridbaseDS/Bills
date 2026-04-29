<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php
    $isQuote   = isset($document_type) && $document_type === 'quote';
    $docName   = $isQuote ? 'Cotización' : 'Factura';
    $docNum    = $isQuote ? ($invoice['quote_number'] ?? '') : ($invoice['invoice_number'] ?? '');
    $dateLabel = $isQuote ? 'Válida Hasta' : 'Fecha de Vencimiento';
    $dateField = $isQuote ? ($invoice['expiry_date'] ?? '') : ($invoice['due_date'] ?? '');
    $status    = $invoice['status'] ?? 'draft';
    ?>
    <title><?= $docName ?> <?= htmlspecialchars($docNum) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 40px 50px;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #1f2124;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Utilidades de Texto */
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .color-primary { color: #0B484C; }
        .color-accent { color: #00DF83; }
        .color-gray { color: #7E7E7E; }

        /* Cabecera de la Factura */
        .header-table {
            margin-bottom: 40px;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-logo {
            font-size: 32px;
            font-weight: 700;
            color: #0B484C;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .company-logo span {
            color: #00DF83;
        }

        .company-details {
            color: #7E7E7E;
            font-size: 13px;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 42px;
            color: #0B484C;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .invoice-meta {
            font-size: 13px;
            color: #1f2124;
            line-height: 1.6;
        }

        .invoice-meta strong {
            color: #0B484C;
            font-weight: 600;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }
        .status-paid    { background: #D1FAE5; color: #065F46; border: 1px solid #34D399; }
        .status-pending { background: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; }
        .status-overdue { background: #FEE2E2; color: #991B1B; border: 1px solid #F87171; }
        .status-draft   { background: #F3F4F6; color: #374151; border: 1px solid #9CA3AF; }

        /* Sección de Cliente (Facturar A) */
        .bill-to-table {
            margin-bottom: 40px;
            width: 100%;
        }

        .bill-to-box {
            background-color: #F6F5F2;
            padding: 20px;
            border-radius: 9px;
            border-left: 4px solid #0B484C;
        }

        .bill-to-title {
            font-size: 16px;
            font-weight: 600;
            color: #0B484C;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .client-details {
            font-size: 14px;
            line-height: 1.6;
            color: #1f2124;
        }

        .client-name {
            font-size: 16px;
            font-weight: 600;
            color: #0B484C;
            margin-bottom: 4px;
        }

        /* Tabla de Artículos */
        .items-table {
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #0B484C;
            color: #FFFFFF;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #00DF83;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            color: #1f2124;
            vertical-align: middle;
            font-size: 13px;
        }

        .items-table tr:nth-child(even) td {
            background-color: #fcfcfc;
        }

        .item-name {
            font-weight: 600;
            color: #0B484C;
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
        }

        .item-desc {
            font-size: 12px;
            color: #7E7E7E;
            line-height: 1.4;
        }

        /* Totales */
        .totals-container {
            width: 100%;
        }

        .totals-table {
            width: 45%;
            float: right;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 10px 15px;
            color: #1f2124;
            font-size: 14px;
        }

        .totals-table .total-border td {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .totals-table .total-label {
            text-align: right;
            font-weight: 500;
            color: #7E7E7E;
        }

        .totals-table .total-amount {
            text-align: right;
            font-weight: 600;
            color: #1f2124;
        }

        .totals-table .grand-total td {
            background-color: #0B484C;
            color: #FFFFFF;
            font-size: 18px;
            font-weight: 700;
            padding: 15px;
        }

        .totals-table .grand-total .total-amount {
            color: #00DF83;
        }

        /* Balance pendiente */
        .totals-table .balance-row td {
            background-color: #FEF2F2;
            font-weight: 700;
            padding: 12px 15px;
        }
        .totals-table .balance-row .total-label { color: #991B1B; }
        .totals-table .balance-row .total-amount { color: #DC2626; }

        /* Limpiar flotados */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Notas y Pie de página */
        .notes-section {
            margin-top: 20px;
            width: 50%;
            float: left;
        }

        .notes-title {
            font-weight: 600;
            color: #0B484C;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .notes-text {
            font-size: 12px;
            color: #7E7E7E;
            line-height: 1.5;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 12px;
            color: #7E7E7E;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding-top: 20px;
            clear: both;
        }

        .footer-thank-you {
            color: #0B484C;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <!-- Cabecera -->
    <table class="header-table">
        <tr>
            <td width="50%">
                <?php
                $logoPath = __DIR__ . '/../assets/img/logo.png';
                if (file_exists($logoPath)):
                    $logoData = base64_encode(file_get_contents($logoPath));
                ?>
                    <div style="margin-bottom: 5px;">
                        <img src="data:image/png;base64,<?= $logoData ?>" alt="Logo" style="height: 55px;">
                    </div>
                <?php else: ?>
                    <div class="company-logo">GridBase<span>.</span></div>
                <?php endif; ?>
                <div class="company-details">
                    <?= htmlspecialchars($company['company_name'] ?? 'Gridbase Digital Solutions') ?><br>
                    <?php if(!empty($company['company_address'])): ?>
                        <?= htmlspecialchars($company['company_address']) ?><br>
                    <?php endif; ?>
                    <?php if(!empty($company['company_city'])): ?>
                        <?= htmlspecialchars($company['company_city']) ?><?= !empty($company['company_country']) ? ', ' . htmlspecialchars($company['company_country']) : '' ?><br>
                    <?php endif; ?>
                    <?php if(!empty($company['company_email'])): ?>
                        <?= htmlspecialchars($company['company_email']) ?><br>
                    <?php endif; ?>
                    <?php if(!empty($company['company_phone'])): ?>
                        <?= htmlspecialchars($company['company_phone']) ?>
                    <?php endif; ?>
                </div>
            </td>
            <td width="50%" class="text-right">
                <div class="invoice-title"><?= $docName ?></div>
                <div class="invoice-meta">
                    <strong><?= $docName ?> Nº:</strong> <?= htmlspecialchars($docNum) ?><br>
                    <strong>Fecha de Emisión:</strong> <?= date('d/m/Y', strtotime($invoice['issue_date'])) ?><br>
                    <strong><?= $dateLabel ?>:</strong> <?= date('d/m/Y', strtotime($dateField)) ?><br>
                    <?php if(!empty($company['company_tax_id'])): ?>
                        <strong>RNC:</strong> <?= htmlspecialchars($company['company_tax_id']) ?>
                    <?php endif; ?>
                </div>
                <?php if (!$isQuote): ?>
                    <?php
                    $badgeClass = 'status-draft';
                    $badgeText  = 'Borrador';
                    if ($status === 'paid')    { $badgeClass = 'status-paid';    $badgeText = 'Pagada'; }
                    if ($status === 'sent' || $status === 'pending') { $badgeClass = 'status-pending'; $badgeText = 'Pendiente de Pago'; }
                    if ($status === 'partial') { $badgeClass = 'status-pending'; $badgeText = 'Pago Parcial'; }
                    if ($status === 'overdue') { $badgeClass = 'status-overdue'; $badgeText = 'Vencida'; }
                    ?>
                    <span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Información del Cliente -->
    <table class="bill-to-table">
        <tr>
            <td width="100%">
                <div class="bill-to-box">
                    <div class="bill-to-title">Facturar A:</div>
                    <div class="client-details">
                        <div class="client-name"><?= htmlspecialchars($invoice['company_name'] ?: $invoice['contact_name']) ?></div>
                        <?php if ($invoice['company_name'] && $invoice['contact_name']): ?>
                            Atención: <?= htmlspecialchars($invoice['contact_name']) ?><br>
                        <?php endif; ?>
                        <?php if(!empty($invoice['address_line1'])): ?>
                            <?= htmlspecialchars($invoice['address_line1']) ?><br>
                        <?php endif; ?>
                        <?php if(!empty($invoice['city'])): ?>
                            <?= htmlspecialchars($invoice['city']) ?><?= !empty($invoice['country']) ? ', ' . htmlspecialchars($invoice['country']) : '' ?><br>
                        <?php endif; ?>
                        <?php if(!empty($invoice['client_tax_id'])): ?>
                            RNC/Cédula: <?= htmlspecialchars($invoice['client_tax_id']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabla de Artículos -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="45%">Descripción del Servicio</th>
                <th width="15%" class="text-center">Cant.</th>
                <th width="20%" class="text-right">Precio Unit.</th>
                <th width="20%" class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice['items'] as $item): ?>
            <tr>
                <td>
                    <span class="item-name"><?= htmlspecialchars($item['description']) ?></span>
                </td>
                <td class="text-center"><?= number_format($item['quantity'], 2) ?></td>
                <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-right"><?= number_format($item['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Sección Inferior: Notas y Totales -->
    <div class="totals-container clearfix">

        <!-- Notas (Lado Izquierdo) -->
        <div class="notes-section">
            <?php if(!empty($invoice['notes'])): ?>
                <div class="notes-title">Notas</div>
                <div class="notes-text">
                    <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
                </div>
                <br>
            <?php endif; ?>

            <?php if(!empty($invoice['terms'])): ?>
                <div class="notes-title">Términos y Condiciones</div>
                <div class="notes-text">
                    <?= nl2br(htmlspecialchars($invoice['terms'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabla de Totales (Lado Derecho) -->
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal:</td>
                <td class="total-amount"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> <?= number_format($invoice['subtotal'], 2) ?></td>
            </tr>
            <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
            <tr class="total-border">
                <td class="total-label">ITBIS (<?= number_format($invoice['tax_rate'], 1) ?>%):</td>
                <td class="total-amount"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> <?= number_format($invoice['tax_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
            <tr>
                <td class="total-label">Descuento:</td>
                <td class="total-amount color-accent">-<?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> <?= number_format($invoice['discount_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="grand-total">
                <td class="total-label" style="color: #ffffff;">TOTAL A PAGAR:</td>
                <td class="total-amount"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> <?= number_format($invoice['total'], 2) ?></td>
            </tr>
            <?php if (!$isQuote && ($invoice['amount_paid'] ?? 0) > 0): ?>
            <tr>
                <td class="total-label">Monto Pagado:</td>
                <td class="total-amount" style="color: #059669;">-<?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> <?= number_format($invoice['amount_paid'], 2) ?></td>
            </tr>
            <tr class="balance-row">
                <td class="total-label">Saldo Pendiente:</td>
                <td class="total-amount"><?= htmlspecialchars($invoice['currency'] ?? 'DOP') ?> <?= number_format($invoice['total'] - $invoice['amount_paid'], 2) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <div class="footer-thank-you">¡Gracias por hacer negocios con nosotros!</div>
        <p>
            <?= htmlspecialchars($company['company_name'] ?? 'Gridbase') ?>
            <?php if(!empty($company['company_website'])): ?> &bull; <?= htmlspecialchars($company['company_website']) ?><?php endif; ?>
            <?php if(!empty($company['company_email'])): ?> &bull; <?= htmlspecialchars($company['company_email']) ?><?php endif; ?>
        </p>
    </div>

</body>
</html>
