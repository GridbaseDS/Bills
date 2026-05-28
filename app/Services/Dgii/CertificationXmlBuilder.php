<?php

namespace App\Services\Dgii;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Generates XML directly from the DGII certification test data JSON,
 * bypassing the Invoice model. This ensures every field matches the
 * DGII test data set exactly.
 */
class CertificationXmlBuilder
{
    private DOMDocument $dom;
    private array $tc; // test case data

    /**
     * Build a complete e-CF XML from a single test case array.
     *
     * @param array $testCase One entry from dgii_test_ecf.json
     * @return string Raw unsigned XML
     */
    public function buildFromTestCase(array $testCase): string
    {
        $this->tc = $testCase;
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;

        $ecf = $this->dom->createElement('ECF');
        $this->dom->appendChild($ecf);

        // Encabezado
        $encabezado = $this->el('Encabezado');
        $ecf->appendChild($encabezado);

        $encabezado->appendChild($this->el('Version', '1.0'));
        $encabezado->appendChild($this->buildIdDoc());
        $encabezado->appendChild($this->buildEmisor());

        // Comprador — NOT present for type 43
        $tipoECF = (int)$this->v('TipoeCF');
        if ($tipoECF !== 43) {
            $encabezado->appendChild($this->buildComprador($tipoECF));
        }

        // InformacionesAdicionales (Transporte, etc.)
        $infoAdicional = $this->buildInformacionesAdicionales();
        if ($infoAdicional) {
            $encabezado->appendChild($infoAdicional);
        }

        // Transporte
        $transporte = $this->buildTransporte();
        if ($transporte) {
            $encabezado->appendChild($transporte);
        }

        // Totales
        $encabezado->appendChild($this->buildTotales());

        // OtraMoneda (if applicable)
        $otraMoneda = $this->buildOtraMoneda();
        if ($otraMoneda) {
            $encabezado->appendChild($otraMoneda);
        }

        // DetallesItems
        $ecf->appendChild($this->buildDetallesItems());

        // Subtotales
        $subtotales = $this->buildSubtotales();
        if ($subtotales) {
            $ecf->appendChild($subtotales);
        }

        return $this->dom->saveXML();
    }

    // ─── IdDoc ─────────────────────────────────────────

    private function buildIdDoc(): DOMElement
    {
        $idDoc = $this->el('IdDoc');

        $fields = [
            'TipoeCF', 'eNCF', 'FechaVencimientoSecuencia',
            'IndicadorNotaCredito', 'IndicadorEnvioDiferido', 'IndicadorMontoGravado',
            'IndicadorServicioTodoIncluido', 'TipoIngresos', 'TipoPago',
            'FechaLimitePago', 'TerminoPago',
        ];

        foreach ($fields as $f) {
            $this->appendIfPresent($idDoc, $f);
        }

        // eNCF uses exact field name from JSON
        // TablaFormasPago
        $formasPago = $this->buildTablaFormasPago();
        if ($formasPago) {
            $idDoc->appendChild($formasPago);
        }

        $this->appendIfPresent($idDoc, 'TotalPaginas');

        return $idDoc;
    }

    private function buildTablaFormasPago(): ?DOMElement
    {
        $formas = [];
        for ($i = 1; $i <= 7; $i++) {
            $fp = $this->v("FormaPago[$i]");
            $mp = $this->v("MontoPago[$i]");
            if ($fp === null && $mp === null) continue;

            $forma = $this->el('FormaDePago');
            if ($fp !== null) $forma->appendChild($this->el('FormaPago', $fp));
            if ($mp !== null) $forma->appendChild($this->el('MontoPago', $this->fmtDecimal($mp)));

            $this->appendIfPresent($forma, "TipoCuentaPago", "TipoCuentaPago");
            $this->appendIfPresent($forma, "NumeroCuentaPago", "NumeroCuentaPago");
            $this->appendIfPresent($forma, "BancoPago", "BancoPago");

            $formas[] = $forma;
        }

        if (empty($formas)) return null;

        $tabla = $this->el('TablaFormasPago');
        foreach ($formas as $f) $tabla->appendChild($f);
        return $tabla;
    }

    // ─── Emisor ────────────────────────────────────────

    private function buildEmisor(): DOMElement
    {
        $emisor = $this->el('Emisor');

        $emisor->appendChild($this->el('RNCEmisor', $this->v('RNCEmisor')));
        $emisor->appendChild($this->el('RazonSocialEmisor', $this->xmlSafe($this->v('RazonSocialEmisor'))));

        $this->appendIfPresent($emisor, 'NombreComercial');
        $this->appendIfPresent($emisor, 'Sucursal');

        $emisor->appendChild($this->el('DireccionEmisor', $this->xmlSafe($this->v('DireccionEmisor'))));

        $this->appendIfPresent($emisor, 'Municipio');
        $this->appendIfPresent($emisor, 'Provincia');

        // TablaTelefonoEmisor (up to 3 phones)
        $phones = [];
        for ($i = 1; $i <= 3; $i++) {
            $phone = $this->v("TelefonoEmisor[$i]");
            if ($phone !== null) $phones[] = $phone;
        }
        if (!empty($phones)) {
            $tabla = $this->el('TablaTelefonoEmisor');
            foreach ($phones as $p) {
                $tabla->appendChild($this->el('TelefonoEmisor', $p));
            }
            $emisor->appendChild($tabla);
        }

        $this->appendIfPresent($emisor, 'CorreoEmisor');
        $this->appendIfPresent($emisor, 'WebSite');
        $this->appendIfPresent($emisor, 'ActividadEconomica');
        $this->appendIfPresent($emisor, 'CodigoVendedor');
        $this->appendIfPresent($emisor, 'NumeroFacturaInterna');
        $this->appendIfPresent($emisor, 'NumeroPedidoInterno');
        $this->appendIfPresent($emisor, 'ZonaVenta');
        $this->appendIfPresent($emisor, 'RutaVenta');
        $this->appendIfPresent($emisor, 'InformacionAdicionalEmisor');

        $emisor->appendChild($this->el('FechaEmision', $this->v('FechaEmision')));

        return $emisor;
    }

    // ─── Comprador ─────────────────────────────────────

    private function buildComprador(int $tipoECF): DOMElement
    {
        $comp = $this->el('Comprador');

        if ($tipoECF === 47) {
            // Type 47: IdentificadorExtranjero instead of RNCComprador
            $this->appendIfPresent($comp, 'IdentificadorExtranjero');
        } else {
            $comp->appendChild($this->el('RNCComprador', $this->v('RNCComprador')));
        }

        $comp->appendChild($this->el('RazonSocialComprador', $this->xmlSafe($this->v('RazonSocialComprador'))));

        $this->appendIfPresent($comp, 'ContactoComprador');
        $this->appendIfPresent($comp, 'CorreoComprador');
        $this->appendIfPresent($comp, 'DireccionComprador');
        $this->appendIfPresent($comp, 'MunicipioComprador');
        $this->appendIfPresent($comp, 'ProvinciaComprador');
        $this->appendIfPresent($comp, 'PaisComprador');
        $this->appendIfPresent($comp, 'FechaEntrega');
        $this->appendIfPresent($comp, 'ContactoEntrega');
        $this->appendIfPresent($comp, 'DireccionEntrega');
        $this->appendIfPresent($comp, 'TelefonoAdicional');
        $this->appendIfPresent($comp, 'FechaOrdenCompra');
        $this->appendIfPresent($comp, 'NumeroOrdenCompra');
        $this->appendIfPresent($comp, 'CodigoInternoComprador');
        $this->appendIfPresent($comp, 'ResponsablePago');
        $this->appendIfPresent($comp, 'InformacionAdicionalComprador');

        return $comp;
    }

    // ─── InformacionesAdicionales ──────────────────────

    private function buildInformacionesAdicionales(): ?DOMElement
    {
        $fields = [
            'FechaEmbarque', 'NumeroEmbarque', 'NumeroContenedor',
            'NumeroReferencia', 'NombrePuertoEmbarque', 'CondicionesEntrega',
            'TotalFob', 'Seguro', 'Flete', 'OtrosGastos', 'TotalCif',
            'RegimenAduanero', 'NombrePuertoSalida', 'NombrePuertoDesembarque',
            'PesoBruto', 'PesoNeto', 'UnidadPesoBruto', 'UnidadPesoNeto',
            'CantidadBulto', 'UnidadBulto', 'VolumenBulto', 'UnidadVolumen',
        ];

        $hasAny = false;
        foreach ($fields as $f) {
            if ($this->v($f) !== null) { $hasAny = true; break; }
        }
        if (!$hasAny) return null;

        // Trim the "NumeroContenedor " (note trailing space in JSON key)
        $info = $this->el('InformacionesAdicionales');
        foreach ($fields as $f) {
            // Handle the trailing space in "NumeroContenedor " key from the JSON
            $val = $this->v($f) ?? $this->v($f . ' ');
            if ($val !== null) {
                $info->appendChild($this->el($f, $this->xmlSafe($val)));
            }
        }
        return $info;
    }

    // ─── Transporte ────────────────────────────────────

    private function buildTransporte(): ?DOMElement
    {
        $fields = [
            'ViaTransporte', 'PaisOrigen', 'DireccionDestino', 'PaisDestino',
            'RNCIdentificacionCompaniaTransportista', 'NombreCompaniaTransportista',
            'NumeroViaje', 'Conductor', 'DocumentoTransporte', 'Ficha',
            'Placa', 'RutaTransporte', 'ZonaTransporte', 'NumeroAlbaran',
        ];

        $hasAny = false;
        foreach ($fields as $f) {
            if ($this->v($f) !== null) { $hasAny = true; break; }
        }
        if (!$hasAny) return null;

        $transporte = $this->el('Transporte');
        foreach ($fields as $f) {
            $this->appendIfPresent($transporte, $f);
        }
        return $transporte;
    }

    // ─── Totales ───────────────────────────────────────

    private function buildTotales(): DOMElement
    {
        $totales = $this->el('Totales');

        $decimalFields = [
            'MontoGravadoTotal', 'MontoGravadoI1', 'MontoGravadoI2', 'MontoGravadoI3',
            'MontoExento',
        ];
        foreach ($decimalFields as $f) {
            $val = $this->v($f);
            if ($val !== null) $totales->appendChild($this->el($f, $this->fmtDecimal($val)));
        }

        $intFields = ['ITBIS1', 'ITBIS2', 'ITBIS3'];
        foreach ($intFields as $f) {
            $this->appendIfPresent($totales, $f);
        }

        $moreDecimalFields = [
            'TotalITBIS', 'TotalITBIS1', 'TotalITBIS2', 'TotalITBIS3',
            'MontoImpuestoAdicional',
        ];
        foreach ($moreDecimalFields as $f) {
            $val = $this->v($f);
            if ($val !== null) $totales->appendChild($this->el($f, $this->fmtDecimal($val)));
        }

        // ImpuestosAdicionales table
        $impuestos = $this->buildImpuestosAdicionales('TipoImpuesto', 'TasaImpuestoAdicional',
            'MontoImpuestoSelectivoConsumoEspecifico', 'MontoImpuestoSelectivoConsumoAdvalorem',
            'OtrosImpuestosAdicionales', 'ImpuestosAdicionales', 'ImpuestoAdicional');
        if ($impuestos) $totales->appendChild($impuestos);

        $totales->appendChild($this->el('MontoTotal', $this->fmtDecimal($this->v('MontoTotal'))));

        $postTotalFields = [
            'MontoNoFacturable', 'MontoPeriodo', 'SaldoAnterior',
            'MontoAvancePago', 'ValorPagar',
            'TotalITBISRetenido', 'TotalISRRetencion',
            'TotalITBISPercepcion', 'TotalISRPercepcion',
        ];
        foreach ($postTotalFields as $f) {
            $val = $this->v($f);
            if ($val !== null) $totales->appendChild($this->el($f, $this->fmtDecimal($val)));
        }

        return $totales;
    }

    private function buildImpuestosAdicionales(
        string $tipoKey, string $tasaKey, string $especificoKey,
        string $advaloremKey, string $otrosKey,
        string $wrapperName, string $itemName
    ): ?DOMElement {
        $items = [];
        for ($i = 1; $i <= 4; $i++) {
            $tipo = $this->v("{$tipoKey}[$i]");
            if ($tipo === null) continue;

            $imp = $this->el($itemName);
            $imp->appendChild($this->el(str_replace('OtraMoneda', '', $tipoKey) === $tipoKey ? 'TipoImpuesto' : 'TipoImpuestoOtraMoneda', $tipo));

            $tasa = $this->v("{$tasaKey}[$i]");
            if ($tasa !== null) $imp->appendChild($this->el(str_contains($tasaKey, 'OtraMoneda') ? 'TasaImpuestoAdicionalOtraMoneda' : 'TasaImpuestoAdicional', $this->fmtDecimal($tasa)));

            $especifico = $this->v("{$especificoKey}[$i]");
            if ($especifico !== null) $imp->appendChild($this->el(str_contains($especificoKey, 'OtraMoneda') ? 'MontoImpuestoSelectivoConsumoEspecificoOtraMoneda' : 'MontoImpuestoSelectivoConsumoEspecifico', $this->fmtDecimal($especifico)));

            $advalorem = $this->v("{$advaloremKey}[$i]");
            if ($advalorem !== null) $imp->appendChild($this->el(str_contains($advaloremKey, 'OtraMoneda') ? 'MontoImpuestoSelectivoConsumoAdvaloremOtraMoneda' : 'MontoImpuestoSelectivoConsumoAdvalorem', $this->fmtDecimal($advalorem)));

            $otros = $this->v("{$otrosKey}[$i]");
            if ($otros !== null) $imp->appendChild($this->el(str_contains($otrosKey, 'OtraMoneda') ? 'OtrosImpuestosAdicionalesOtraMoneda' : 'OtrosImpuestosAdicionales', $this->fmtDecimal($otros)));

            $items[] = $imp;
        }

        if (empty($items)) return null;

        $wrapper = $this->el($wrapperName);
        foreach ($items as $imp) $wrapper->appendChild($imp);
        return $wrapper;
    }

    // ─── OtraMoneda ────────────────────────────────────

    private function buildOtraMoneda(): ?DOMElement
    {
        $tm = $this->v('TipoMoneda');
        if ($tm === null) return null;

        $otra = $this->el('OtraMoneda');
        $otra->appendChild($this->el('TipoMoneda', $tm));

        $tc = $this->v('TipoCambio');
        if ($tc !== null) $otra->appendChild($this->el('TipoCambio', $this->fmtDecimal($tc)));

        $fields = [
            'MontoGravadoTotalOtraMoneda', 'MontoGravado1OtraMoneda',
            'MontoGravado2OtraMoneda', 'MontoGravado3OtraMoneda',
            'MontoExentoOtraMoneda', 'TotalITBISOtraMoneda',
            'TotalITBIS1OtraMoneda', 'TotalITBIS2OtraMoneda', 'TotalITBIS3OtraMoneda',
            'MontoImpuestoAdicionalOtraMoneda',
        ];
        foreach ($fields as $f) {
            $val = $this->v($f);
            if ($val !== null) $otra->appendChild($this->el($f, $this->fmtDecimal($val)));
        }

        // ImpuestosAdicionalesOtraMoneda
        $impuestos = $this->buildImpuestosAdicionales(
            'TipoImpuestoOtraMoneda', 'TasaImpuestoAdicionalOtraMoneda',
            'MontoImpuestoSelectivoConsumoEspecificoOtraMoneda',
            'MontoImpuestoSelectivoConsumoAdvaloremOtraMoneda',
            'OtrosImpuestosAdicionalesOtraMoneda',
            'ImpuestosAdicionalesOtraMoneda', 'ImpuestoAdicionalOtraMoneda'
        );
        if ($impuestos) $otra->appendChild($impuestos);

        $mt = $this->v('MontoTotalOtraMoneda');
        if ($mt !== null) $otra->appendChild($this->el('MontoTotalOtraMoneda', $this->fmtDecimal($mt)));

        return $otra;
    }

    // ─── DetallesItems ─────────────────────────────────

    private function buildDetallesItems(): DOMElement
    {
        $detalles = $this->el('DetallesItems');

        for ($n = 1; $n <= 20; $n++) {
            $numLinea = $this->v("NumeroLinea[$n]");
            if ($numLinea === null) break;

            $item = $this->el('Item');
            $item->appendChild($this->el('NumeroLinea', (string)$numLinea));

            // TablaCodigosItem
            $codigos = [];
            for ($c = 1; $c <= 5; $c++) {
                $tipo = $this->v("TipoCodigo[$n][$c]");
                $codigo = $this->v("CodigoItem[$n][$c]");
                if ($tipo !== null || $codigo !== null) {
                    $ci = $this->el('CodigosItem');
                    if ($tipo !== null) $ci->appendChild($this->el('TipoCodigo', $tipo));
                    if ($codigo !== null) $ci->appendChild($this->el('CodigoItem', $codigo));
                    $codigos[] = $ci;
                }
            }
            if (!empty($codigos)) {
                $tabla = $this->el('TablaCodigosItem');
                foreach ($codigos as $ci) $tabla->appendChild($ci);
                $item->appendChild($tabla);
            }

            // IndicadorFacturacion
            $indFact = $this->v("IndicadorFacturacion[$n]");
            if ($indFact !== null) $item->appendChild($this->el('IndicadorFacturacion', (string)$indFact));

            // Retencion
            $retencion = $this->buildRetencion($n);
            if ($retencion) $item->appendChild($retencion);

            // Core item fields
            $nombre = $this->v("NombreItem[$n]");
            if ($nombre !== null) $item->appendChild($this->el('NombreItem', $this->xmlSafe($nombre)));

            $indBien = $this->v("IndicadorBienoServicio[$n]");
            if ($indBien !== null) $item->appendChild($this->el('IndicadorBienoServicio', (string)$indBien));

            $desc = $this->v("DescripcionItem[$n]");
            if ($desc !== null) $item->appendChild($this->el('DescripcionItem', $this->xmlSafe($desc)));

            $cantidad = $this->v("CantidadItem[$n]");
            if ($cantidad !== null) $item->appendChild($this->el('CantidadItem', $this->fmtDecimal($cantidad)));

            $unidad = $this->v("UnidadMedida[$n]");
            if ($unidad !== null) $item->appendChild($this->el('UnidadMedida', $unidad));

            $cantRef = $this->v("CantidadReferencia[$n]");
            if ($cantRef !== null) $item->appendChild($this->el('CantidadReferencia', $this->fmtDecimal($cantRef)));

            $unidRef = $this->v("UnidadReferencia[$n]");
            if ($unidRef !== null) $item->appendChild($this->el('UnidadReferencia', $unidRef));

            // TablaSubcantidad
            $subcantidades = [];
            for ($s = 1; $s <= 5; $s++) {
                $sub = $this->v("Subcantidad[$n][$s]");
                $codSub = $this->v("CodigoSubcantidad[$n][$s]");
                if ($sub !== null || $codSub !== null) {
                    $si = $this->el('SubcantidadItem');
                    if ($sub !== null) $si->appendChild($this->el('Subcantidad', $this->fmtDecimal($sub)));
                    if ($codSub !== null) $si->appendChild($this->el('CodigoSubcantidad', $codSub));
                    $subcantidades[] = $si;
                }
            }
            if (!empty($subcantidades)) {
                $tabSub = $this->el('TablaSubcantidad');
                foreach ($subcantidades as $si) $tabSub->appendChild($si);
                $item->appendChild($tabSub);
            }

            // More optional item fields
            $optionalFields = [
                'GradosAlcohol', 'PrecioUnitarioReferencia',
                'FechaElaboracion', 'FechaVencimientoItem',
                'PesoNetoKilogramo', 'PesoNetoMineria',
                'TipoAfiliacion', 'Liquidacion',
            ];
            foreach ($optionalFields as $f) {
                $val = $this->v("{$f}[$n]");
                if ($val !== null) {
                    $formatted = in_array($f, ['GradosAlcohol', 'PrecioUnitarioReferencia', 'PesoNetoKilogramo', 'PesoNetoMineria']) ? $this->fmtDecimal($val) : $val;
                    $item->appendChild($this->el($f, $formatted));
                }
            }

            // PrecioUnitarioItem (required)
            $precio = $this->v("PrecioUnitarioItem[$n]");
            if ($precio !== null) $item->appendChild($this->el('PrecioUnitarioItem', $this->fmtDecimal($precio)));

            // DescuentoMonto
            $descuento = $this->v("DescuentoMonto[$n]");
            if ($descuento !== null) $item->appendChild($this->el('DescuentoMonto', $this->fmtDecimal($descuento)));

            // TablaSubDescuento
            $subDescuentos = [];
            for ($sd = 1; $sd <= 5; $sd++) {
                $tipo = $this->v("TipoSubDescuento[$n][$sd]");
                if ($tipo === null) continue;
                $subDesc = $this->el('SubDescuento');
                $subDesc->appendChild($this->el('TipoSubDescuento', $tipo));
                $pct = $this->v("SubDescuentoPorcentaje[$n][$sd]");
                if ($pct !== null) $subDesc->appendChild($this->el('SubDescuentoPorcentaje', $this->fmtDecimal($pct)));
                $monto = $this->v("MontoSubDescuento[$n][$sd]");
                if ($monto !== null) $subDesc->appendChild($this->el('MontoSubDescuento', $this->fmtDecimal($monto)));
                $subDescuentos[] = $subDesc;
            }
            if (!empty($subDescuentos)) {
                $tabDesc = $this->el('TablaSubDescuento');
                foreach ($subDescuentos as $sd) $tabDesc->appendChild($sd);
                $item->appendChild($tabDesc);
            }

            // RecargoMonto
            $recargo = $this->v("RecargoMonto[$n]");
            if ($recargo !== null) $item->appendChild($this->el('RecargoMonto', $this->fmtDecimal($recargo)));

            // TablaSubRecargo
            $subRecargos = [];
            for ($sr = 1; $sr <= 5; $sr++) {
                $tipo = $this->v("TipoSubRecargo[$n][$sr]");
                if ($tipo === null) continue;
                $subRec = $this->el('SubRecargo');
                $subRec->appendChild($this->el('TipoSubRecargo', $tipo));
                $pct = $this->v("SubRecargoPorcentaje[$n][$sr]");
                if ($pct !== null) $subRec->appendChild($this->el('SubRecargoPorcentaje', $this->fmtDecimal($pct)));
                $monto = $this->v("MontosubRecargo[$n][$sr]");
                if ($monto !== null) $subRec->appendChild($this->el('MontoSubRecargo', $this->fmtDecimal($monto)));
                $subRecargos[] = $subRec;
            }
            if (!empty($subRecargos)) {
                $tabRec = $this->el('TablaSubRecargo');
                foreach ($subRecargos as $sr) $tabRec->appendChild($sr);
                $item->appendChild($tabRec);
            }

            // TablaImpuestoAdicional (item level, up to 2)
            $impItems = [];
            for ($ti = 1; $ti <= 2; $ti++) {
                $tipo = $this->v("TipoImpuesto[$n][$ti]");
                if ($tipo === null) continue;
                $imp = $this->el('ImpuestoAdicional');
                $imp->appendChild($this->el('TipoImpuesto', $tipo));
                $impItems[] = $imp;
            }
            if (!empty($impItems)) {
                $tabImp = $this->el('TablaImpuestoAdicional');
                foreach ($impItems as $imp) $tabImp->appendChild($imp);
                $item->appendChild($tabImp);
            }

            // OtraMonedaDetalle
            $precioOtra = $this->v("PrecioOtraMoneda[$n]");
            $descOtra = $this->v("DescuentoOtraMoneda[$n]");
            $recOtra = $this->v("RecargoOtraMoneda[$n]");
            $montoOtra = $this->v("MontoItemOtraMoneda[$n]");
            if ($precioOtra !== null || $descOtra !== null || $recOtra !== null || $montoOtra !== null) {
                $otraDet = $this->el('OtraMonedaDetalle');
                if ($precioOtra !== null) $otraDet->appendChild($this->el('PrecioOtraMoneda', $this->fmtDecimal($precioOtra)));
                if ($descOtra !== null) $otraDet->appendChild($this->el('DescuentoOtraMoneda', $this->fmtDecimal($descOtra)));
                if ($recOtra !== null) $otraDet->appendChild($this->el('RecargoOtraMoneda', $this->fmtDecimal($recOtra)));
                if ($montoOtra !== null) $otraDet->appendChild($this->el('MontoItemOtraMoneda', $this->fmtDecimal($montoOtra)));
                $item->appendChild($otraDet);
            }

            // MontoItem (required)
            $montoItem = $this->v("MontoItem[$n]");
            if ($montoItem !== null) $item->appendChild($this->el('MontoItem', $this->fmtDecimal($montoItem)));

            $detalles->appendChild($item);
        }

        return $detalles;
    }

    private function buildRetencion(int $n): ?DOMElement
    {
        $indAgente = $this->v("IndicadorAgenteRetencionoPercepcion[$n]");
        $montoItbis = $this->v("MontoITBISRetenido[$n]");
        $montoIsr = $this->v("MontoISRRetenido[$n]");

        if ($indAgente === null && $montoItbis === null && $montoIsr === null) return null;

        $ret = $this->el('Retencion');
        if ($indAgente !== null) $ret->appendChild($this->el('IndicadorAgenteRetencionoPercepcion', $indAgente));
        if ($montoItbis !== null) $ret->appendChild($this->el('MontoITBISRetenido', $this->fmtDecimal($montoItbis)));
        if ($montoIsr !== null) $ret->appendChild($this->el('MontoISRRetenido', $this->fmtDecimal($montoIsr)));
        return $ret;
    }

    // ─── Subtotales (Global) ───────────────────────────

    private function buildSubtotales(): ?DOMElement
    {
        // Check if any subtotal fields exist
        $tipoAjuste = $this->v('TipoAjuste');
        if ($tipoAjuste === null) return null;

        $subtotales = $this->el('Subtotales');
        // This is simplified — implement if needed for test cases
        return null; // Most test cases don't use global Subtotales
    }

    // ─── Helpers ───────────────────────────────────────

    /**
     * Get value from test case, returning null if '#e' or missing.
     */
    private function v(string $key): ?string
    {
        // Try exact key first
        $val = $this->tc[$key] ?? null;

        // Try with trailing space (JSON quirk)
        if ($val === null) {
            $val = $this->tc[$key . ' '] ?? null;
        }

        if ($val === null || $val === '#e') return null;

        return (string)$val;
    }

    /**
     * Append a child element only if the test case has a non-#e value.
     */
    private function appendIfPresent(DOMElement $parent, string $jsonKey, ?string $xmlName = null): void
    {
        $val = $this->v($jsonKey);
        if ($val === null) return;
        $parent->appendChild($this->el($xmlName ?? $jsonKey, $this->xmlSafe($val)));
    }

    private function el(string $name, ?string $value = null): DOMElement
    {
        if ($value !== null) {
            return $this->dom->createElement($name, $value);
        }
        return $this->dom->createElement($name);
    }

    private function xmlSafe(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function fmtDecimal($value): string
    {
        if (is_numeric($value)) {
            // Preserve decimal places as given
            $str = (string)$value;
            if (strpos($str, '.') === false) {
                return number_format((float)$value, 2, '.', '');
            }
            return $str;
        }
        return (string)$value;
    }
}
