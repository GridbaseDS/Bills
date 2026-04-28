<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Gridbase Bills' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #081A15; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

<!-- Wrapper -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #081A15; padding: 30px 0;">
    <tr>
        <td align="center">
            <!-- Main Container -->
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #0F2A23; border-radius: 12px; overflow: hidden; border: 1px solid rgba(180, 231, 23, 0.08);">
                
                <!-- Header with Logo -->
                <tr>
                    <td style="background: #081A15; padding: 28px 40px; text-align: center; border-bottom: 1px solid rgba(180, 231, 23, 0.08);">
                        @if(!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ $companyName ?? 'GridBase' }}" style="height: 38px; max-width: 200px;">
                        @else
                            <span style="font-size: 22px; font-weight: bold; color: #B4E717;">Grid<span style="color: #F0F5F0;">Base</span></span>
                        @endif
                    </td>
                </tr>

                <!-- Lime accent bar -->
                <tr>
                    <td style="height: 3px; font-size: 0; line-height: 0; background: linear-gradient(90deg, #B4E717, #00D690);">&nbsp;</td>
                </tr>

                <!-- Greeting -->
                <tr>
                    <td style="padding: 32px 40px 14px 40px;">
                        <p style="font-size: 17px; font-weight: 700; color: #F0F5F0; margin: 0 0 8px 0;">
                            Hola {{ $clientName }},
                        </p>
                        <p style="font-size: 13px; color: #8BA899; line-height: 1.7; margin: 0;">
                            {{ $isQuote ? 'Te enviamos la cotización' : 'Te enviamos la factura' }} <strong style="color: #B4E717;">{{ $docNumber }}</strong> de <strong style="color: #F0F5F0;">{{ $companyName ?? 'GridBase' }}</strong>.
                            {{ $isQuote ? 'Revisa los detalles a continuación.' : 'Adjunta encontrarás el PDF con todos los detalles.' }}
                        </p>
                    </td>
                </tr>

                <!-- Document Summary Card -->
                <tr>
                    <td style="padding: 0 40px 22px 40px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #0B1F1A; border-radius: 10px; border: 1px solid rgba(180, 231, 23, 0.08); overflow: hidden;">
                            
                            <!-- Card Header -->
                            <tr>
                                <td colspan="2" style="background: #081A15; padding: 13px 18px; border-bottom: 1px solid rgba(180, 231, 23, 0.08);">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="color: #B4E717; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                                {{ $isQuote ? '📋 Cotización' : '📄 Factura' }} {{ $docNumber }}
                                            </td>
                                            <td align="right">
                                                @if(!$isQuote && !empty($status))
                                                    @php
                                                        $bgColor = 'rgba(92,122,106,.2)'; $textColor = '#8BA899';
                                                        if ($status === 'paid') { $bgColor = 'rgba(52,211,153,.15)'; $textColor = '#34D399'; }
                                                        elseif ($status === 'overdue') { $bgColor = 'rgba(251,113,133,.15)'; $textColor = '#FB7185'; }
                                                        elseif ($status === 'sent') { $bgColor = 'rgba(56,189,248,.15)'; $textColor = '#38BDF8'; }
                                                        $statusLabel = match($status) { 'paid' => 'PAGADA', 'overdue' => 'VENCIDA', 'sent' => 'ENVIADA', 'draft' => 'BORRADOR', default => strtoupper($status) };
                                                    @endphp
                                                    <span style="background: {{ $bgColor }}; color: {{ $textColor }}; font-size: 9px; font-weight: 700; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px;">{{ $statusLabel }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Dates Row -->
                            <tr>
                                <td style="padding: 14px 18px 6px 18px; width: 50%;">
                                    <span style="font-size: 9px; text-transform: uppercase; color: #5C7A6A; letter-spacing: 0.8px;">Fecha Emisión</span><br>
                                    <span style="font-size: 13px; color: #E0E8E0; font-weight: 600;">{{ $issueDate }}</span>
                                </td>
                                <td align="right" style="padding: 14px 18px 6px 18px; width: 50%;">
                                    <span style="font-size: 9px; text-transform: uppercase; color: #5C7A6A; letter-spacing: 0.8px;">{{ $isQuote ? 'Válida Hasta' : 'Vencimiento' }}</span><br>
                                    <span style="font-size: 13px; color: #E0E8E0; font-weight: 600;">{{ $dueDate }}</span>
                                </td>
                            </tr>

                            <!-- Divider -->
                            <tr>
                                <td colspan="2" style="padding: 0 18px;">
                                    <div style="border-top: 1px solid rgba(180, 231, 23, 0.06);"></div>
                                </td>
                            </tr>

                            <!-- Items Summary -->
                            @foreach($items as $index => $item)
                            <tr>
                                <td style="padding: 9px 18px; font-size: 13px; color: #E0E8E0;">
                                    <span style="display: inline-block; width: 20px; height: 20px; background: linear-gradient(135deg, #B4E717, #9ACC10); color: #0B1F1A; font-size: 9px; font-weight: bold; text-align: center; line-height: 20px; border-radius: 3px; margin-right: 8px;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    {{ $item['description'] }}
                                </td>
                                <td align="right" style="padding: 9px 18px; font-size: 13px; color: #F0F5F0; font-weight: 600;">
                                    ${{ number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) }}
                                </td>
                            </tr>
                            @endforeach

                            <!-- Totals Divider -->
                            <tr>
                                <td colspan="2" style="padding: 0 18px;">
                                    <div style="border-top: 1px solid rgba(180, 231, 23, 0.1);"></div>
                                </td>
                            </tr>

                            <!-- Subtotal -->
                            <tr>
                                <td style="padding: 9px 18px; font-size: 11px; color: #5C7A6A;">Subtotal</td>
                                <td align="right" style="padding: 9px 18px; font-size: 11px; color: #E0E8E0;">${{ number_format($subtotal, 2) }}</td>
                            </tr>

                            @if($discountAmount > 0)
                            <tr>
                                <td style="padding: 3px 18px; font-size: 11px; color: #5C7A6A;">Descuento</td>
                                <td align="right" style="padding: 3px 18px; font-size: 11px; color: #00D690;">-${{ number_format($discountAmount, 2) }}</td>
                            </tr>
                            @endif

                            @if($taxAmount > 0)
                            <tr>
                                <td style="padding: 3px 18px; font-size: 11px; color: #5C7A6A;">ITBIS ({{ number_format($taxRate, 0) }}%)</td>
                                <td align="right" style="padding: 3px 18px; font-size: 11px; color: #E0E8E0;">${{ number_format($taxAmount, 2) }}</td>
                            </tr>
                            @endif

                            <!-- Grand Total -->
                            <tr>
                                <td colspan="2" style="padding: 0;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="background: linear-gradient(135deg, #B4E717, #9ACC10); padding: 13px 18px; color: #0B1F1A; font-size: 15px; font-weight: 800;">
                                                Total
                                            </td>
                                            <td align="right" style="background: linear-gradient(135deg, #B4E717, #9ACC10); padding: 13px 18px; color: #0B1F1A; font-size: 15px; font-weight: 800;">
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
                        <p style="font-size: 10px; color: #5C7A6A; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.8px;">Notas</p>
                        <p style="font-size: 12px; color: #8BA899; margin: 0; line-height: 1.6; background: rgba(180, 231, 23, 0.04); padding: 10px 14px; border-radius: 6px; border-left: 3px solid #B4E717;">{!! nl2br(e($notes)) !!}</p>
                    </td>
                </tr>
                @endif

                <!-- Attachment Note -->
                <tr>
                    <td style="padding: 8px 40px 28px 40px; text-align: center;">
                        <p style="font-size: 12px; color: #5C7A6A; margin: 0;">
                            📎 El PDF de {{ $isQuote ? 'la cotización' : 'la factura' }} está adjunto a este correo.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background: #081A15; padding: 18px 40px; border-top: 1px solid rgba(180, 231, 23, 0.08); text-align: center;">
                        <p style="font-size: 11px; color: #B4E717; margin: 0 0 3px 0; font-weight: 600;">
                            {{ $companyName ?? 'Gridbase Digital Solutions' }}
                        </p>
                        <p style="font-size: 10px; color: #5C7A6A; margin: 0; line-height: 1.6;">
                            @if(!empty($companyPhone)) {{ $companyPhone }} · @endif
                            @if(!empty($companyEmail)) {{ $companyEmail }} · @endif
                            @if(!empty($companyWebsite)) {{ $companyWebsite }} @endif
                        </p>
                        <p style="font-size: 9px; color: #3D5A4B; margin: 8px 0 0 0;">
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
