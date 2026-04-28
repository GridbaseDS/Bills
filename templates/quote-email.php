<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Cotización de <?= htmlspecialchars($company['company_name'] ?? 'Gridbase') ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F8F9FB; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #F8F9FB; padding-bottom: 40px; }
        .main { background: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 40px; }
        .header { background-color: #3B82F6; padding: 30px 40px; text-align: center; color: #ffffff; }
        .content { padding: 40px; color: #333333; line-height: 1.6; font-size: 15px; }
        .footer { text-align: center; padding: 20px; color: #94A3B8; font-size: 12px; }
        .invoice-details { background-color: #F8F9FB; padding: 20px; border-radius: 6px; margin: 20px 0; border: 1px solid #E2E8F0; }
        .amount { font-size: 24px; font-weight: bold; color: #3B82F6; margin: 10px 0; }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="main" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="header">
                            <h1 style="margin: 0; font-size: 24px;">Nueva Cotización</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
                            <p>Hola <?= htmlspecialchars($quote['contact_name']) ?>,</p>
                            <p>Aquí tienes la cotización <strong><?= htmlspecialchars($quote['quote_number']) ?></strong> según lo solicitado.</p>
                            
                            <div class="invoice-details">
                                <p style="margin: 0 0 5px 0; color: #64748B; font-size: 13px; text-transform: uppercase;">Monto Estimado</p>
                                <div class="amount"><?= htmlspecialchars($quote['currency']) ?> <?= number_format($quote['total'], 2) ?></div>
                                <p style="margin: 5px 0 0 0; color: #64748B; font-size: 13px;">Válida hasta: <strong><?= date('d/m/Y', strtotime($quote['expiry_date'])) ?></strong></p>
                            </div>

                            <p>Una copia en PDF con el desglose completo ha sido adjuntada.</p>
                            <p>Si deseas proceder, simplemente responde a este correo y la convertiremos en factura.</p>
                            
                            <p style="margin-top: 40px; margin-bottom: 0;">Gracias,<br><strong><?= htmlspecialchars($company['company_name'] ?? 'Gridbase') ?></strong></p>
                        </td>
                    </tr>
                </table>
                <div class="footer">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($company['company_name'] ?? 'Gridbase') ?>. Todos los derechos reservados.
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
