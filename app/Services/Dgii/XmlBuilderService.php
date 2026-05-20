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

        if ($invoice->ecf_type == 31 && empty($rncComprador)) {
            throw new Exception("El RNC del Comprador es estrictamente obligatorio para Facturas de Crédito Fiscal Electrónicas (Tipo 31).");
        }

        // For B2C (Type 32 - Consumo), comprador RNC can be empty.
        // If empty and amount > 250,000, DGII requires RNC/Cédula, but we assume validation occurs at creation.

        // 3. Document identification
        $tipoECF = $invoice->ecf_type; // 31 or 32
        $eNCF = $invoice->encf;
        $fechaVencimientoSecuencia = $settings['dgii_ncf_expiry_date'] ?? '2027-12-31'; // standard fallback
        
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

        // Root element
        $root = $dom->createElement('ECF');
        $root->setAttribute('xmlns', 'http://dgii.gov.do/e-CF');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $dom->appendChild($root);

        // --- ENCABEZADO ---
        $encabezado = $dom->createElement('Encabezado');
        $root->appendChild($encabezado);

        // Version (Fixed to 1.0)
        $version = $dom->createElement('Version', '1.0');
        $encabezado->appendChild($version);

        // IdDoc (Document Identification)
        $idDoc = $dom->createElement('IdDoc');
        $encabezado->appendChild($idDoc);

        $idDoc->appendChild($dom->createElement('TipoeCF', $tipoECF));
        $idDoc->appendChild($dom->createElement('eNCF', $eNCF));
        $idDoc->appendChild($dom->createElement('FechaVencimientoSecuencia', $fechaVencimientoSecuencia));
        $idDoc->appendChild($dom->createElement('TipoIngresos', '01')); // 01 = Ingresos por operaciones (No financieros)
        $idDoc->appendChild($dom->createElement('TipoPago', $tipoPago));

        if ($tipoPago === 2) {
            $idDoc->appendChild($dom->createElement('FechaLimitePago', $invoice->due_date));
        }

        // Emisor (Seller)
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

        $emisor->appendChild($dom->createElement('FechaEmision', $invoice->issue_date));

        // Comprador (Client)
        $comprador = $dom->createElement('Comprador');
        $encabezado->appendChild($comprador);

        if (!empty($rncComprador)) {
            $comprador->appendChild($dom->createElement('RNCComprador', $rncComprador));
        }
        $comprador->appendChild($dom->createElement('RazonSocialComprador', htmlspecialchars($razonSocialComprador, ENT_XML1)));

        if ($client->email) {
            $comprador->appendChild($dom->createElement('CorreoComprador', htmlspecialchars(substr($client->email, 0, 80), ENT_XML1)));
        }

        $direccionCliente = trim(($client->address_line1 ?? '') . ' ' . ($client->address_line2 ?? '') . ' ' . ($client->city ?? ''));
        if (!empty($direccionCliente)) {
            $comprador->appendChild($dom->createElement('DireccionComprador', htmlspecialchars(substr($direccionCliente, 0, 100), ENT_XML1)));
        }

        // Totales
        $totales = $dom->createElement('Totales');
        $encabezado->appendChild($totales);

        if ($montoGravadoTotal > 0) {
            $totales->appendChild($dom->createElement('MontoGravadoTotal', number_format($montoGravadoTotal, 2, '.', '')));
            
            // ITBIS 1 (18%) standard mapping for typical invoices in DR digital services
            $itbisRate = (int)round($invoice->tax_rate);
            if ($itbisRate === 18) {
                $totales->appendChild($dom->createElement('MontoGravadoI1', number_format($montoGravadoTotal, 2, '.', '')));
                $totales->appendChild($dom->createElement('ITBIS1', '18'));
                $totales->appendChild($dom->createElement('TotalITBIS1', number_format($totalITBIS, 2, '.', '')));
            } else if ($itbisRate === 16) {
                $totales->appendChild($dom->createElement('MontoGravadoI2', number_format($montoGravadoTotal, 2, '.', '')));
                $totales->appendChild($dom->createElement('ITBIS2', '16'));
                $totales->appendChild($dom->createElement('TotalITBIS2', number_format($totalITBIS, 2, '.', '')));
            }
        }

        if ($montoExento > 0) {
            $totales->appendChild($dom->createElement('MontoExento', number_format($montoExento, 2, '.', '')));
        }

        if ($totalITBIS > 0) {
            $totales->appendChild($dom->createElement('TotalITBIS', number_format($totalITBIS, 2, '.', '')));
        }

        $totales->appendChild($dom->createElement('MontoTotal', number_format($montoTotal, 2, '.', '')));

        // --- DETALLES ITEMS ---
        $detallesItems = $dom->createElement('DetallesItems');
        $root->appendChild($detallesItems);

        $lineNum = 1;
        foreach ($invoice->items as $item) {
            $itemNode = $dom->createElement('Item');
            $detallesItems->appendChild($itemNode);

            $itemNode->appendChild($dom->createElement('NumeroLinea', $lineNum));
            
            // IndicadorFacturacion: 1 = ITBIS 18%, 2 = ITBIS 16%, 3 = ITBIS 0%, 4 = Exento
            $indicadorFact = $invoice->tax_rate > 0 ? 1 : 4;
            $itemNode->appendChild($dom->createElement('IndicadorFacturacion', $indicadorFact));
            
            $itemNode->appendChild($dom->createElement('NombreItem', htmlspecialchars(substr($item->description, 0, 80), ENT_XML1)));
            
            // IndicadorBienoServicio: 1 = Bien, 2 = Servicio (GridBase mostly does services)
            $itemNode->appendChild($dom->createElement('IndicadorBienoServicio', 2));
            
            $itemNode->appendChild($dom->createElement('CantidadItem', number_format((float)$item->quantity, 2, '.', '')));
            
            // Defaulting UnidadMedida: 43 = Unidad, 44 = Elemento (Using 43 as standard)
            $itemNode->appendChild($dom->createElement('UnidadMedida', 43));
            
            $itemNode->appendChild($dom->createElement('PrecioUnitarioItem', number_format((float)$item->unit_price, 4, '.', '')));
            
            // If invoice has global discount, we distribute discount or ignore if already factored
            $itemNode->appendChild($dom->createElement('MontoItem', number_format((float)$item->amount, 2, '.', '')));

            $lineNum++;
        }

        // FechaHoraFirma (format: YYYY-MM-DDTHH:MM:SS)
        $fechaHoraFirma = $dom->createElement('FechaHoraFirma', date('Y-m-d\TH:i:s'));
        $root->appendChild($fechaHoraFirma);

        return $dom->saveXML();
    }
}
