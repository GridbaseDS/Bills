<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Gridbase Bills' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F6F5F2; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

<!-- Wrapper -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F6F5F2; padding: 30px 0;">
    <tr>
        <td align="center">
            <!-- Main Container -->
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #FFFFFF; border-radius: 12px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
                
                <!-- Header with Logo -->
                <tr>
                    <td style="background: #0B484C; padding: 24px 40px; text-align: center;">
                        @if(!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ $companyName ?? 'GridBase' }}" style="height: 36px; max-width: 200px;">
                        @else
                            <span style="font-size: 22px; font-weight: bold; color: #FFFFFF;">Grid<span style="color: #00DF83;">Base</span></span>
                        @endif
                    </td>
                </tr>

                <!-- Green accent bar -->
                <tr>
                    <td style="height: 3px; font-size: 0; line-height: 0; background: #00DF83;">&nbsp;</td>
                </tr>

                <!-- Greeting -->
                <tr>
                    <td style="padding: 32px 40px 14px 40px;">
                        <p style="font-size: 17px; font-weight: 600; color: #0B484C; margin: 0 0 8px 0;">
                            Hola {{ $clientName }},
                        </p>
                        <p style="font-size: 13px; color: #555555; line-height: 1.7; margin: 0;">
                            {{ $isQuote ? 'Te enviamos la cotización' : 'Te enviamos la factura' }} <strong style="color: #0B484C;">{{ $docNumber }}</strong> de <strong style="color: #0B484C;">{{ $companyName ?? 'GridBase' }}</strong>.
                            {{ $isQuote ? 'Revisa los detalles a continuación.' : 'Adjunta encontrarás el PDF con todos los detalles.' }}
                        </p>
                    </td>
                </tr>

                <!-- Document Summary Card -->
                <tr>
                    <td style="padding: 0 40px 22px 40px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #F6F5F2; border-radius: 10px; border: 1px solid rgba(0,0,0,0.06); overflow: hidden;">
                            
                            <!-- Card Header -->
                            <tr>
                                <td colspan="2" style="background: #0B484C; padding: 13px 18px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="color: #FFFFFF; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                                {{ $isQuote ? '📋 Cotización' : '📄 Factura' }} {{ $docNumber }}
                                            </td>
                                            <td align="right">
                                                @if(!$isQuote && !empty($status))
                                                    @php
                                                        $bgColor = 'rgba(255,255,255,0.15)'; $textColor = '#FFFFFF';
                                                        if ($status === 'paid') { $bgColor = 'rgba(0,223,131,0.2)'; $textColor = '#00DF83'; }
                                                        elseif ($status === 'overdue') { $bgColor = 'rgba(251,113,133,0.2)'; $textColor = '#FB7185'; }
                                                        elseif ($status === 'sent') { $bgColor = 'rgba(56,189,248,0.2)'; $textColor = '#38BDF8'; }
                                                        $statusLabel = match($status) { 'paid' => 'PAGADA', 'overdue' => 'VENCIDA', 'sent' => 'PENDIENTE DE PAGO', 'partial' => 'PAGO PARCIAL', 'draft' => 'BORRADOR', default => strtoupper($status) };
                                                    @endphp
                                                    <span style="background: {{ $bgColor }}; color: {{ $textColor }}; font-size: 9px; font-weight: 700; padding: 4px 10px; border-radius: 100px; text-transform: uppercase; letter-spacing: 0.5px;">{{ $statusLabel }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Dates Row -->
                            <tr>
                                <td style="padding: 14px 18px 6px 18px; width: 50%;">
                                    <span style="font-size: 9px; text-transform: uppercase; color: #7E7E7E; letter-spacing: 0.8px;">Fecha Emisión</span><br>
                                    <span style="font-size: 13px; color: #333333; font-weight: 600;">{{ $issueDate }}</span>
                                </td>
                                <td align="right" style="padding: 14px 18px 6px 18px; width: 50%;">
                                    <span style="font-size: 9px; text-transform: uppercase; color: #7E7E7E; letter-spacing: 0.8px;">{{ $isQuote ? 'Válida Hasta' : 'Vencimiento' }}</span><br>
                                    <span style="font-size: 13px; color: #333333; font-weight: 600;">{{ $dueDate }}</span>
                                </td>
                            </tr>

                            <!-- Divider -->
                            <tr>
                                <td colspan="2" style="padding: 0 18px;">
                                    <div style="border-top: 1px solid rgba(0,0,0,0.06);"></div>
                                </td>
                            </tr>

                            <!-- Items Summary -->
                            @foreach($items as $index => $item)
                            <tr>
                                <td style="padding: 9px 18px; font-size: 13px; color: #333333;">
                                    <span style="display: inline-block; width: 20px; height: 20px; background: #0B484C; color: #FFFFFF; font-size: 9px; font-weight: bold; text-align: center; line-height: 20px; border-radius: 3px; margin-right: 8px;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    {{ $item['description'] }}
                                </td>
                                <td align="right" style="padding: 9px 18px; font-size: 13px; color: #1a1a1a; font-weight: 600;">
                                    ${{ number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) }}
                                </td>
                            </tr>
                            @endforeach

                            <!-- Totals Divider -->
                            <tr>
                                <td colspan="2" style="padding: 0 18px;">
                                    <div style="border-top: 1px solid rgba(0,0,0,0.08);"></div>
                                </td>
                            </tr>

                            <!-- Subtotal -->
                            <tr>
                                <td style="padding: 9px 18px; font-size: 11px; color: #7E7E7E;">Subtotal</td>
                                <td align="right" style="padding: 9px 18px; font-size: 11px; color: #333333;">${{ number_format($subtotal, 2) }}</td>
                            </tr>

                            @if($discountAmount > 0)
                            <tr>
                                <td style="padding: 3px 18px; font-size: 11px; color: #7E7E7E;">Descuento</td>
                                <td align="right" style="padding: 3px 18px; font-size: 11px; color: #00DF83;">-${{ number_format($discountAmount, 2) }}</td>
                            </tr>
                            @endif

                            @if($taxAmount > 0)
                            <tr>
                                <td style="padding: 3px 18px; font-size: 11px; color: #7E7E7E;">ITBIS ({{ number_format($taxRate, 0) }}%)</td>
                                <td align="right" style="padding: 3px 18px; font-size: 11px; color: #333333;">${{ number_format($taxAmount, 2) }}</td>
                            </tr>
                            @endif

                            <!-- Grand Total -->
                            <tr>
                                <td colspan="2" style="padding: 0;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="background: #0B484C; padding: 13px 18px; color: #FFFFFF; font-size: 15px; font-weight: 800;">
                                                Total
                                            </td>
                                            <td align="right" style="background: #0B484C; padding: 13px 18px; color: #FFFFFF; font-size: 15px; font-weight: 800;">
                                                {{ $currency }} ${{ number_format($total, 2) }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>

                @if(!empty($notes))
                <tr>
                    <td style="padding: 0 40px 18px 40px;">
                        <p style="font-size: 10px; color: #7E7E7E; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.8px;">Notas</p>
                        <p style="font-size: 12px; color: #555555; margin: 0; line-height: 1.6; background: #F6F5F2; padding: 10px 14px; border-radius: 6px; border-left: 3px solid #0B484C;">{!! nl2br(e($notes)) !!}</p>
                    </td>
                </tr>
                @endif

                <!-- Payment Button (only for invoices) -->
                @if(!$isQuote && !empty($paymentUrl))
                <tr>
                    <td style="padding: 22px 40px 28px 40px; text-align: center;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="background: linear-gradient(135deg, #00DF83 0%, #0B484C 100%); border-radius: 8px; text-align: center; padding: 20px;">
                                    <p style="font-size: 12px; color: #FFFFFF; margin: 0 0 12px 0; opacity: 0.95;">
                                        💳 Puede pagar esta factura de forma segura
                                    </p>
                                    <a href="{{ $paymentUrl }}" style="display: inline-block; background: #FFFFFF; color: #0B484C; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                        Pagar Ahora
                                    </a>
                                    @if(!empty($paymentExpiresAt))
                                    <p style="font-size: 10px; color: #FFFFFF; margin: 12px 0 0 0; opacity: 0.8;">
                                        Link válido hasta: {{ $paymentExpiresAt }}
                                    </p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        <p style="font-size: 11px; color: #7E7E7E; margin: 12px 0 0 0;">
                            También puede ingresar el código de factura en: <a href="{{ url('/buscar-factura') }}" style="color: #0B484C; text-decoration: none; font-weight: 600;">{{ url('/buscar-factura') }}</a>
                        </p>
                    </td>
                </tr>
                @endif

                <!-- Attachment Note -->
                <tr>
                    <td style="padding: 8px 40px 28px 40px; text-align: center;">
                        <p style="font-size: 12px; color: #7E7E7E; margin: 0;">
                            📎 El PDF de {{ $isQuote ? 'la cotización' : 'la factura' }} está adjunto a este correo.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background: #F6F5F2; padding: 18px 40px; border-top: 1px solid rgba(0,0,0,0.06); text-align: center;">
                        <p style="font-size: 11px; color: #0B484C; margin: 0 0 3px 0; font-weight: 600;">
                            {{ $companyName ?? 'Gridbase Digital Solutions' }}
                        </p>
                        <p style="font-size: 10px; color: #7E7E7E; margin: 0; line-height: 1.6;">
                            @if(!empty($companyPhone)) {{ $companyPhone }} · @endif
                            @if(!empty($companyEmail)) {{ $companyEmail }} · @endif
                            @if(!empty($companyWebsite)) {{ $companyWebsite }} @endif
                        </p>
                        <p style="font-size: 9px; color: #AAAAAA; margin: 8px 0 0 0;">
                            Generado por Gridbase Bills
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
