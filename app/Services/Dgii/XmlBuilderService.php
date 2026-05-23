<?php

namespace App\Services\Dgii;

use App\Models\Invoice;
use App\Models\Setting;
use DOMDocument;
use Exception;

class XmlBuilderService
{
    /**
     * Builds the raw unsigned XML for a Comprobante Fiscal Electrónico (e-CF)
     * according to the DGII schema specifications.
     *
     * @param Invoice $invoice Laravel Invoice model instance with loaded client and items.
     * @param array $settings System settings array containing company details.
     * @return string Unsigned XML content.
     * @throws Exception
     */
    public function buildInvoiceXml(Invoice $invoice, array $settings): string
    {
        $invoice->load(['client', 'items']);

        if (!$invoice->is_ecf || !$invoice->ecf_type || !$invoice->encf) {
            throw new Exception("La factura {$invoice->invoice_number} no está configurada como Comprobante Electrónico (e-CF).");
        }

        // 1. Emitter details
        $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
        $razonSocialEmisor = trim($settings['company_name'] ?? 'GridBase');
        $direccionEmisor = trim($settings['company_address'] ?? 'Santo Domingo, Rep. Dom.');
        $correoEmisor = trim($settings['company_email'] ?? '');

        if (empty($rncEmisor)) {
            throw new Exception("El RNC del Emisor es obligatorio en los Ajustes del sistema.");
        }

        // 2. Buyer details
        $client = $invoice->client;
        $rncComprador = preg_replace('/[^0-9]/', '', $client->tax_id ?? '');
        $razonSocialComprador = trim($client->company_name ?: $client->contact_name);

        // Types that require buyer RNC: 31, 33, 34, 41, 44, 45, 46
        $buyerRncRequired = [31, 33, 34, 41, 44, 45, 46];
        if (in_array((int)$invoice->ecf_type, $buyerRncRequired) && empty($rncComprador)) {
            throw new Exception("El RNC del Comprador es obligatorio para e-CF tipo {$invoice->ecf_type}.");
        }

        // 3. Document identification
        $tipoECF = (int)$invoice->ecf_type;
        $eNCF = $invoice->encf;
        $fechaVencimientoSecuencia = $settings['dgii_ncf_expiry_date'] ?? '2027-12-31';
        
        // TerminoPago: Contado (1), Crédito (2)
        $isCredito = $invoice->due_date && $invoice->due_date > $invoice->issue_date;
        $tipoPago = $isCredito ? 2 : 1; 

        // 4. Calculate XML structural totals
        $subtotal = (float)$invoice->subtotal;
        $discountTotal = (float)($invoice->discount_amount ?? 0);
        $totalITBIS = (float)($invoice->tax_amount ?? 0);
        $montoTotal = (float)$invoice->total;

        $montoGravadoTotal = 0.00;
        $montoExento = 0.00;

        if ($invoice->tax_rate > 0) {
            $montoGravadoTotal = $subtotal - $discountTotal;
        } else {
            $montoExento = $subtotal - $discountTotal;
        }

        // Create DOM Document
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        // Root element — NO namespace per XSD (no targetNamespace)
        $root = $dom->createElement('ECF');
        $dom->appendChild($root);

        // --- ENCABEZADO ---
        $encabezado = $dom->createElement('Encabezado');
        $root->appendChild($encabezado);

        // Version (Fixed to 1.0)
        $version = $dom->createElement('Version', '1.0');
        $encabezado->appendChild($version);

        // IdDoc (Document Identification) - structure varies per type
        $idDoc = $dom->createElement('IdDoc');
        $encabezado->appendChild($idDoc);

        $idDoc->appendChild($dom->createElement('TipoeCF', $tipoECF));
        $idDoc->appendChild($dom->createElement('eNCF', $eNCF));

        // FechaVencimientoSecuencia: NOT in types 32, 34
        if (!in_array($tipoECF, [32, 34])) {
            $fvs = $fechaVencimientoSecuencia;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fvs)) {
                $fvs = date('d-m-Y', strtotime($fvs));
            }
            $idDoc->appendChild($dom->createElement('FechaVencimientoSecuencia', $fvs));
        }

        // IndicadorNotaCredito: ONLY for type 34 (after eNCF, no FVS)
        if ($tipoECF === 34) {
            $idDoc->appendChild($dom->createElement('IndicadorNotaCredito', $invoice->nota_credito_indicator ?? 1));
        }

        // IndicadorMontoGravado: ONLY types 31, 32, 33, 34, 45 (per XSD analysis)
        if (in_array($tipoECF, [31, 32, 33, 34, 45])) {
            $idDoc->appendChild($dom->createElement('IndicadorMontoGravado', 0));
        }

        // TipoIngresos: types 31,32,44,45,46 (required), 33,34 (optional). NOT in 41,43,47
        $tipoIngresosRequired = [31, 32, 44, 45, 46];
        $tipoIngresosOptional = [33, 34];
        if (in_array($tipoECF, $tipoIngresosRequired)) {
            $idDoc->appendChild($dom->createElement('TipoIngresos', $invoice->tipo_ingresos ?? '01'));
        } elseif (in_array($tipoECF, $tipoIngresosOptional) && !empty($invoice->tipo_ingresos)) {
            $idDoc->appendChild($dom->createElement('TipoIngresos', $invoice->tipo_ingresos));
        }

        // TipoPago: all types have it
        $idDoc->appendChild($dom->createElement('TipoPago', $tipoPago ?: 1));

        // FechaLimitePago: when credit
        if ($tipoPago === 2 && $invoice->due_date) {
            $idDoc->appendChild($dom->createElement('FechaLimitePago', $invoice->due_date->format('d-m-Y')));
        }

        // Emisor (Seller) - same for all types
        $emisor = $dom->createElement('Emisor');
        $encabezado->appendChild($emisor);

        $emisor->appendChild($dom->createElement('RNCEmisor', $rncEmisor));
        $emisor->appendChild($dom->createElement('RazonSocialEmisor', htmlspecialchars($razonSocialEmisor, ENT_XML1)));
        
        if (!empty($direccionEmisor)) {
            $emisor->appendChild($dom->createElement('DireccionEmisor', htmlspecialchars(substr($direccionEmisor, 0, 100), ENT_XML1)));
        } else {
            $emisor->appendChild($dom->createElement('DireccionEmisor', 'Santo Domingo'));
        }

        if (!empty($correoEmisor)) {
            $emisor->appendChild($dom->createElement('CorreoEmisor', htmlspecialchars($correoEmisor, ENT_XML1)));
        }

        $emisor->appendChild($dom->createElement('FechaEmision', $invoice->issue_date->format('d-m-Y')));

        // Comprador (Client) - NOT present for type 43 (Gastos Menores)
        if ($tipoECF !== 43) {
            $comprador = $dom->createElement('Comprador');
            $encabezado->appendChild($comprador);

            if ($tipoECF === 47) {
                // Type 47: uses IdentificadorExtranjero, NOT RNCComprador
                $comprador->appendChild($dom->createElement('IdentificadorExtranjero', $rncComprador ?: '00000000000'));
            } else {
                if (!empty($rncComprador)) {
                    $comprador->appendChild($dom->createElement('RNCComprador', $rncComprador));
                }
            }
            $comprador->appendChild($dom->createElement('RazonSocialComprador', htmlspecialchars($razonSocialComprador, ENT_XML1)));

            if ($client->email && !in_array($tipoECF, [47])) {
                $comprador->appendChild($dom->createElement('CorreoComprador', htmlspecialchars(substr($client->email, 0, 80), ENT_XML1)));
            }

            $direccionCliente = trim(($client->address_line1 ?? '') . ' ' . ($client->address_line2 ?? '') . ' ' . ($client->city ?? ''));
            if (!empty($direccionCliente) && !in_array($tipoECF, [47])) {
                $comprador->appendChild($dom->createElement('DireccionComprador', htmlspecialchars(substr($direccionCliente, 0, 100), ENT_XML1)));
            }
        }

        // Totales - structure varies significantly per type
        $totales = $dom->createElement('Totales');
        $encabezado->appendChild($totales);

        // Types with full ITBIS breakdown: 31, 32, 33, 34, 45
        // Types with only MontoExento: 43, 47
        // Types with MontoExento + ImpuestosAdicionales: 44
        // Type 46: MontoGravadoTotal (different set)
        // Type 41: full ITBIS + retenciones

        if (in_array($tipoECF, [43, 47])) {
            // Simple: MontoExento > MontoTotal
            $totales->appendChild($dom->createElement('MontoExento', number_format($subtotal - $discountTotal, 2, '.', '')));
            $totales->appendChild($dom->createElement('MontoTotal', number_format($montoTotal, 2, '.', '')));
        } elseif ($tipoECF === 44) {
            // Regímenes Especiales: MontoExento > MontoTotal (no ITBIS)
            $totales->appendChild($dom->createElement('MontoExento', number_format($subtotal - $discountTotal, 2, '.', '')));
            $totales->appendChild($dom->createElement('MontoTotal', number_format($subtotal - $discountTotal, 2, '.', '')));
        } elseif ($tipoECF === 46) {
            // Exportaciones: MontoGravadoTotal > MontoTotal
            $totales->appendChild($dom->createElement('MontoGravadoTotal', number_format($subtotal - $discountTotal, 2, '.', '')));
            $totales->appendChild($dom->createElement('MontoTotal', number_format($montoTotal, 2, '.', '')));
        } else {
            // Types 31, 32, 33, 34, 41, 45: full ITBIS breakdown
            if ($montoGravadoTotal > 0) {
                $totales->appendChild($dom->createElement('MontoGravadoTotal', number_format($montoGravadoTotal, 2, '.', '')));
                $itbisRate = (int)round($invoice->tax_rate);
                if ($itbisRate === 18) {
                    $totales->appendChild($dom->createElement('MontoGravadoI1', number_format($montoGravadoTotal, 2, '.', '')));
                } elseif ($itbisRate === 16) {
                    $totales->appendChild($dom->createElement('MontoGravadoI2', number_format($montoGravadoTotal, 2, '.', '')));
                }
            }

            if ($montoExento > 0) {
                $totales->appendChild($dom->createElement('MontoExento', number_format($montoExento, 2, '.', '')));
            }

            if ($montoGravadoTotal > 0) {
                $itbisRate = (int)round($invoice->tax_rate);
                if ($itbisRate === 18) {
                    $totales->appendChild($dom->createElement('ITBIS1', '18'));
                } elseif ($itbisRate === 16) {
                    $totales->appendChild($dom->createElement('ITBIS2', '16'));
                }
            }

            if ($totalITBIS > 0) {
                $totales->appendChild($dom->createElement('TotalITBIS', number_format($totalITBIS, 2, '.', '')));
                $itbisRate = (int)round($invoice->tax_rate);
                if ($itbisRate === 18) {
                    $totales->appendChild($dom->createElement('TotalITBIS1', number_format($totalITBIS, 2, '.', '')));
                } elseif ($itbisRate === 16) {
                    $totales->appendChild($dom->createElement('TotalITBIS2', number_format($totalITBIS, 2, '.', '')));
                }
            }

            $totales->appendChild($dom->createElement('MontoTotal', number_format($montoTotal, 2, '.', '')));
        }

        // --- DETALLES ITEMS ---
        $detallesItems = $dom->createElement('DetallesItems');
        $root->appendChild($detallesItems);

        $lineNum = 1;
        foreach ($invoice->items as $item) {
            $itemNode = $dom->createElement('Item');
            $detallesItems->appendChild($itemNode);

            $itemNode->appendChild($dom->createElement('NumeroLinea', $lineNum));
            
            // IndicadorFacturacion: 1=ITBIS 18%, 2=ITBIS 16%, 3=Exento, 4=Exento
            // For types 43, 44, 47: always exento (3)
            if (in_array($tipoECF, [43, 44, 47])) {
                $indicadorFact = 3;
            } else {
                $indicadorFact = $invoice->tax_rate > 0 ? 1 : 3;
            }
            $itemNode->appendChild($dom->createElement('IndicadorFacturacion', $indicadorFact));
            
            $itemNode->appendChild($dom->createElement('NombreItem', htmlspecialchars(substr($item->description, 0, 80), ENT_XML1)));
            
            // IndicadorBienoServicio: 1=Bien, 2=Servicio
            $itemNode->appendChild($dom->createElement('IndicadorBienoServicio', 2));
            
            $itemNode->appendChild($dom->createElement('CantidadItem', number_format((float)$item->quantity, 2, '.', '')));
            $itemNode->appendChild($dom->createElement('UnidadMedida', 43));
            $itemNode->appendChild($dom->createElement('PrecioUnitarioItem', number_format((float)$item->unit_price, 4, '.', '')));
            $itemNode->appendChild($dom->createElement('MontoItem', number_format((float)$item->amount, 2, '.', '')));

            $lineNum++;
        }

        // InformacionReferencia for Notes (33, 34)
        if (in_array($tipoECF, [33, 34])) {
            $infoReferencia = $dom->createElement('InformacionReferencia');
            $root->appendChild($infoReferencia);
            
            $ncfModificado = $invoice->modified_ncf ?? 'E310000000000';
            $infoReferencia->appendChild($dom->createElement('NCFModificado', $ncfModificado));
            $infoReferencia->appendChild($dom->createElement('FechaNCFModificado', $invoice->issue_date->format('d-m-Y')));
            
            // CodigoModificacion: 1 = Anula, 2 = Corrige Texto, 3 = Corrige Montos
            $infoReferencia->appendChild($dom->createElement('CodigoModificacion', $invoice->modification_code ?? 1));
            
            if (!empty($invoice->modification_reason)) {
                $infoReferencia->appendChild($dom->createElement('RazonModificacion', htmlspecialchars(substr($invoice->modification_reason, 0, 90), ENT_XML1)));
            }
        }

        // FechaHoraFirma: DD-MM-YYYY HH:MM:SS per XSD DateTimeValidationType
        $fechaHoraFirma = $dom->createElement('FechaHoraFirma', date('d-m-Y H:i:s'));
        $root->appendChild($fechaHoraFirma);

        return $dom->saveXML();
    }

    /**
     * Builds the RFCE (Resumen Factura de Consumo Electronica) XML
     * for FC<250k invoices, per RFCE 32 v.1.0.xsd.
     * This summary must be sent to fc.dgii.gov.do before uploading the FC to the portal.
     *
     * @param Invoice $invoice The FC<250k invoice (already signed, with security_code)
     * @param array $settings System settings
     * @return string RFCE XML content
     */
    public function buildRfceXml(Invoice $invoice, array $settings): string
    {
        $invoice->load(['client', 'items']);

        $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
        $razonSocialEmisor = trim($settings['company_name'] ?? 'GridBase');
        $client = $invoice->client;
        $rncComprador = preg_replace('/[^0-9]/', '', $client->tax_id ?? '');
        $razonSocialComprador = trim($client->company_name ?: $client->contact_name);

        $isCredito = $invoice->due_date && $invoice->due_date > $invoice->issue_date;
        $tipoPago = $isCredito ? 2 : 1;

        $subtotal = (float)$invoice->subtotal;
        $discountTotal = (float)($invoice->discount_amount ?? 0);
        $totalITBIS = (float)($invoice->tax_amount ?? 0);
        $montoTotal = (float)$invoice->total;
        $montoGravadoTotal = $invoice->tax_rate > 0 ? ($subtotal - $discountTotal) : 0;
        $montoExento = $invoice->tax_rate > 0 ? 0 : ($subtotal - $discountTotal);

        // RFCE uses dd-mm-yyyy date format per XSD
        $fechaEmision = $invoice->issue_date->format('d-m-Y');

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        // Root: <RFCE> — no namespace per XSD
        $root = $dom->createElement('RFCE');
        $dom->appendChild($root);

        $encabezado = $dom->createElement('Encabezado');
        $root->appendChild($encabezado);

        $encabezado->appendChild($dom->createElement('Version', '1.0'));

        // IdDoc
        $idDoc = $dom->createElement('IdDoc');
        $encabezado->appendChild($idDoc);
        $idDoc->appendChild($dom->createElement('TipoeCF', '32'));
        $idDoc->appendChild($dom->createElement('eNCF', $invoice->encf));
        $idDoc->appendChild($dom->createElement('TipoIngresos', $invoice->tipo_ingresos ?? '01'));
        $idDoc->appendChild($dom->createElement('TipoPago', $tipoPago));

        // Emisor
        $emisor = $dom->createElement('Emisor');
        $encabezado->appendChild($emisor);
        $emisor->appendChild($dom->createElement('RNCEmisor', $rncEmisor));
        $emisor->appendChild($dom->createElement('RazonSocialEmisor', htmlspecialchars($razonSocialEmisor, ENT_XML1)));
        $emisor->appendChild($dom->createElement('FechaEmision', $fechaEmision));

        // Comprador
        $comprador = $dom->createElement('Comprador');
        $encabezado->appendChild($comprador);
        if (!empty($rncComprador)) {
            $comprador->appendChild($dom->createElement('RNCComprador', $rncComprador));
        }
        if (!empty($razonSocialComprador)) {
            $comprador->appendChild($dom->createElement('RazonSocialComprador', htmlspecialchars($razonSocialComprador, ENT_XML1)));
        }

        // Totales
        $totales = $dom->createElement('Totales');
        $encabezado->appendChild($totales);

        if ($montoGravadoTotal > 0) {
            $totales->appendChild($dom->createElement('MontoGravadoTotal', number_format($montoGravadoTotal, 2, '.', '')));
            $itbisRate = (int)round($invoice->tax_rate);
            if ($itbisRate === 18) {
                $totales->appendChild($dom->createElement('MontoGravadoI1', number_format($montoGravadoTotal, 2, '.', '')));
            } elseif ($itbisRate === 16) {
                $totales->appendChild($dom->createElement('MontoGravadoI2', number_format($montoGravadoTotal, 2, '.', '')));
            }
        }
        if ($montoExento > 0) {
            $totales->appendChild($dom->createElement('MontoExento', number_format($montoExento, 2, '.', '')));
        }
        if ($totalITBIS > 0) {
            $totales->appendChild($dom->createElement('TotalITBIS', number_format($totalITBIS, 2, '.', '')));
            $itbisRate = (int)round($invoice->tax_rate);
            if ($itbisRate === 18) {
                $totales->appendChild($dom->createElement('TotalITBIS1', number_format($totalITBIS, 2, '.', '')));
            } elseif ($itbisRate === 16) {
                $totales->appendChild($dom->createElement('TotalITBIS2', number_format($totalITBIS, 2, '.', '')));
            }
        }
        $totales->appendChild($dom->createElement('MontoTotal', number_format($montoTotal, 2, '.', '')));

        // CodigoSeguridadeCF — first 6 chars of the signed e-CF signature
        $securityCode = $invoice->security_code ?? '000000';
        $encabezado->appendChild($dom->createElement('CodigoSeguridadeCF', $securityCode));

        return $dom->saveXML();
    }
}
