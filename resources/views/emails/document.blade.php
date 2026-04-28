<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Gridbase Bills' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F4F4F7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

<!-- Wrapper -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F4F4F7; padding: 30px 0;">
    <tr>
        <td align="center">
            <!-- Main Container -->
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                
                <!-- Header -->
                <tr>
                    <td style="background: linear-gradient(135deg, #1A1D26 0%, #2D3140 100%); padding: 30px 40px; text-align: center;">
                        @if(!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ $companyName ?? 'GridBase' }}" style="height: 40px; max-width: 200px;">
                        @else
                            <span style="font-size: 24px; font-weight: bold; color: #D4832F;">Grid<span style="color: #ffffff;">Base</span></span>
                        @endif
                    </td>
                </tr>

                <!-- Orange accent bar -->
                <tr>
                    <td style="background: #D4832F; height: 4px; font-size: 0; line-height: 0;">&nbsp;</td>
                </tr>

                <!-- Greeting -->
                <tr>
                    <td style="padding: 35px 40px 15px 40px;">
                        <p style="font-size: 18px; font-weight: 700; color: #1A1D26; margin: 0 0 8px 0;">
                            Hola {{ $clientName }},
                        </p>
                        <p style="font-size: 14px; color: #666666; line-height: 1.6; margin: 0;">
                            {{ $isQuote ? 'Te enviamos la cotización' : 'Te enviamos la factura' }} <strong style="color: #1A1D26;">{{ $docNumber }}</strong> de <strong style="color: #1A1D26;">{{ $companyName ?? 'GridBase' }}</strong>.
                            {{ $isQuote ? 'Revisa los detalles a continuación.' : 'Adjunta encontrarás el PDF con todos los detalles.' }}
                        </p>
                    </td>
                </tr>

                <!-- Document Summary Card -->
                <tr>
                    <td style="padding: 0 40px 25px 40px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #FAFAFA; border-radius: 10px; border: 1px solid #EEEEEE; overflow: hidden;">
                            
                            <!-- Card Header -->
                            <tr>
                                <td colspan="2" style="background: #1A1D26; padding: 14px 20px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="color: #D4832F; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                                {{ $isQuote ? '📋 Cotización' : '📄 Factura' }} {{ $docNumber }}
                                            </td>
                                            <td align="right">
                                                @if(!$isQuote && !empty($status))
                                                    @php
                                                        $bgColor = '#555'; $textColor = '#fff';
                                                        if ($status === 'paid') { $bgColor = '#E8F5E9'; $textColor = '#2E7D32'; }
                                                        elseif ($status === 'overdue') { $bgColor = '#FFEBEE'; $textColor = '#C62828'; }
                                                        elseif ($status === 'sent') { $bgColor = '#E3F2FD'; $textColor = '#1565C0'; }
                                                        $statusLabel = match($status) { 'paid' => 'PAGADA', 'overdue' => 'VENCIDA', 'sent' => 'ENVIADA', 'draft' => 'BORRADOR', default => strtoupper($status) };
                                                    @endphp
                                                    <span style="background: {{ $bgColor }}; color: {{ $textColor }}; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px;">{{ $statusLabel }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Dates Row -->
                            <tr>
                                <td style="padding: 16px 20px 8px 20px; width: 50%;">
                                    <span style="font-size: 10px; text-transform: uppercase; color: #999; letter-spacing: 0.5px;">Fecha Emisión</span><br>
                                    <span style="font-size: 13px; color: #333; font-weight: 600;">{{ $issueDate }}</span>
                                </td>
                                <td align="right" style="padding: 16px 20px 8px 20px; width: 50%;">
                                    <span style="font-size: 10px; text-transform: uppercase; color: #999; letter-spacing: 0.5px;">{{ $isQuote ? 'Válida Hasta' : 'Vencimiento' }}</span><br>
                                    <span style="font-size: 13px; color: #333; font-weight: 600;">{{ $dueDate }}</span>
                                </td>
                            </tr>

                            <!-- Divider -->
                            <tr>
                                <td colspan="2" style="padding: 0 20px;">
                                    <div style="border-top: 1px solid #E8E8E8;"></div>
                                </td>
                            </tr>

                            <!-- Items Summary -->
                            @foreach($items as $index => $item)
                            <tr>
                                <td style="padding: 10px 20px; font-size: 13px; color: #333;">
                                    <span style="display: inline-block; width: 22px; height: 22px; background: #D4832F; color: #fff; font-size: 10px; font-weight: bold; text-align: center; line-height: 22px; border-radius: 3px; margin-right: 8px;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    {{ $item['description'] }}
                                </td>
                                <td align="right" style="padding: 10px 20px; font-size: 13px; color: #333; font-weight: 600;">
                                    ${{ number_format($item['amount'] ?? ($item['quantity'] * $item['unit_price']), 2) }}
                                </td>
                            </tr>
                            @endforeach

                            <!-- Totals Divider -->
                            <tr>
                                <td colspan="2" style="padding: 0 20px;">
                                    <div style="border-top: 2px solid #E0E0E0;"></div>
                                </td>
                            </tr>

                            <!-- Subtotal -->
                            <tr>
                                <td style="padding: 10px 20px; font-size: 12px; color: #888;">Subtotal</td>
                                <td align="right" style="padding: 10px 20px; font-size: 12px; color: #333;">${{ number_format($subtotal, 2) }}</td>
                            </tr>

                            @if($discountAmount > 0)
                            <tr>
                                <td style="padding: 4px 20px; font-size: 12px; color: #888;">Descuento</td>
                                <td align="right" style="padding: 4px 20px; font-size: 12px; color: #2E7D32;">-${{ number_format($discountAmount, 2) }}</td>
                            </tr>
                            @endif

                            @if($taxAmount > 0)
                            <tr>
                                <td style="padding: 4px 20px; font-size: 12px; color: #888;">ITBIS ({{ number_format($taxRate, 0) }}%)</td>
                                <td align="right" style="padding: 4px 20px; font-size: 12px; color: #333;">${{ number_format($taxAmount, 2) }}</td>
                            </tr>
                            @endif

                            <!-- Grand Total -->
                            <tr>
                                <td colspan="2" style="padding: 0;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="background: #D4832F; padding: 14px 20px; color: #ffffff; font-size: 16px; font-weight: 800;">
                                                Total
                                            </td>
                                            <td align="right" style="background: #D4832F; padding: 14px 20px; color: #ffffff; font-size: 16px; font-weight: 800;">
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
                    <td style="padding: 0 40px 20px 40px;">
                        <p style="font-size: 12px; color: #999; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">Notas</p>
                        <p style="font-size: 13px; color: #555; margin: 0; line-height: 1.5; background: #FFF9F0; padding: 12px 16px; border-radius: 6px; border-left: 3px solid #D4832F;">{!! nl2br(e($notes)) !!}</p>
                    </td>
                </tr>
                @endif

                <!-- CTA Note -->
                <tr>
                    <td style="padding: 10px 40px 30px 40px; text-align: center;">
                        <p style="font-size: 13px; color: #888; margin: 0;">
                            📎 El PDF de {{ $isQuote ? 'la cotización' : 'la factura' }} está adjunto a este correo.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background: #F8F8FA; padding: 20px 40px; border-top: 1px solid #EEEEEE; text-align: center;">
                        <p style="font-size: 12px; color: #999; margin: 0 0 4px 0; font-weight: 600;">
                            {{ $companyName ?? 'Gridbase Digital Solutions' }}
                        </p>
                        <p style="font-size: 11px; color: #BBB; margin: 0; line-height: 1.6;">
                            @if(!empty($companyPhone)) {{ $companyPhone }} &bull; @endif
                            @if(!empty($companyEmail)) {{ $companyEmail }} &bull; @endif
                            @if(!empty($companyWebsite)) {{ $companyWebsite }} @endif
                        </p>
                        <p style="font-size: 10px; color: #CCC; margin: 10px 0 0 0;">
                            Este correo fue generado automáticamente por Gridbase Bills.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
