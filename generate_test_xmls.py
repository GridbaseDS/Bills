import json
import os
import re
from xml.etree.ElementTree import Element, SubElement, tostring
from datetime import datetime

def is_empty(val):
    return val == "#e" or val is None or val == ""

# Map flat columns to their parent XML node
node_mapping = {
    'Version': 'Encabezado',
    
    # IdDoc
    'TipoeCF': 'Encabezado/IdDoc',
    'ENCF': 'Encabezado/IdDoc',
    'eNCF': 'Encabezado/IdDoc',
    'FechaVencimientoSecuencia': 'Encabezado/IdDoc',
    'IndicadorNotaCredito': 'Encabezado/IdDoc',
    'IndicadorEnvioDiferido': 'Encabezado/IdDoc',
    'IndicadorMontoGravado': 'Encabezado/IdDoc',
    'TipoIngresos': 'Encabezado/IdDoc',
    'TipoPago': 'Encabezado/IdDoc',
    'FechaLimitePago': 'Encabezado/IdDoc',
    'TerminoPago': 'Encabezado/IdDoc',
    'TipoCuentaPago': 'Encabezado/IdDoc',
    'NumeroCuentaPago': 'Encabezado/IdDoc',
    'BancoPago': 'Encabezado/IdDoc',
    'FechaDesde': 'Encabezado/IdDoc',
    'FechaHasta': 'Encabezado/IdDoc',
    'TotalPaginas': 'Encabezado/IdDoc',

    # Emisor
    'RNCEmisor': 'Encabezado/Emisor',
    'RazonSocialEmisor': 'Encabezado/Emisor',
    'NombreComercial': 'Encabezado/Emisor',
    'Sucursal': 'Encabezado/Emisor',
    'DireccionEmisor': 'Encabezado/Emisor',
    'Municipio': 'Encabezado/Emisor',
    'Provincia': 'Encabezado/Emisor',
    'CorreoEmisor': 'Encabezado/Emisor',
    'WebSite': 'Encabezado/Emisor',
    'ActividadEconomica': 'Encabezado/Emisor',
    'CodigoVendedor': 'Encabezado/Emisor',
    'NumeroFacturaInterna': 'Encabezado/Emisor',
    'NumeroPedidoInterno': 'Encabezado/Emisor',
    'ZonaVenta': 'Encabezado/Emisor',
    'RutaVenta': 'Encabezado/Emisor',
    'InformacionAdicionalEmisor': 'Encabezado/Emisor',
    'FechaEmision': 'Encabezado/Emisor',

    # Comprador
    'RNCComprador': 'Encabezado/Comprador',
    'IdentificadorExtranjero': 'Encabezado/Comprador',
    'RazonSocialComprador': 'Encabezado/Comprador',
    'ContactoComprador': 'Encabezado/Comprador',
    'CorreoComprador': 'Encabezado/Comprador',
    'DireccionComprador': 'Encabezado/Comprador',
    'MunicipioComprador': 'Encabezado/Comprador',
    'ProvinciaComprador': 'Encabezado/Comprador',
    'FechaEntrega': 'Encabezado/Comprador',
    'ContactoEntrega': 'Encabezado/Comprador',
    'DireccionEntrega': 'Encabezado/Comprador',
    'TelefonoAdicional': 'Encabezado/Comprador',
    'FechaOrdenCompra': 'Encabezado/Comprador',
    'NumeroOrdenCompra': 'Encabezado/Comprador',
    'CodigoInternoComprador': 'Encabezado/Comprador',
    'ResponsablePago': 'Encabezado/Comprador',
    'InformacionAdicionalComprador': 'Encabezado/Comprador',

    # Totales
    'MontoGravadoTotal': 'Encabezado/Totales',
    'MontoGravadoI1': 'Encabezado/Totales',
    'MontoGravadoI2': 'Encabezado/Totales',
    'MontoGravadoI3': 'Encabezado/Totales',
    'MontoExento': 'Encabezado/Totales',
    'ITBIS1': 'Encabezado/Totales',
    'ITBIS2': 'Encabezado/Totales',
    'ITBIS3': 'Encabezado/Totales',
    'TotalITBIS': 'Encabezado/Totales',
    'TotalITBIS1': 'Encabezado/Totales',
    'TotalITBIS2': 'Encabezado/Totales',
    'TotalITBIS3': 'Encabezado/Totales',
    'MontoImpuestoAdicional': 'Encabezado/Totales',
    'MontoTotal': 'Encabezado/Totales',
    'MontoNoFacturable': 'Encabezado/Totales',
    'MontoPeriodo': 'Encabezado/Totales',
    'SaldoAnterior': 'Encabezado/Totales',
    'MontoAvancePago': 'Encabezado/Totales',
    'ValorPagar': 'Encabezado/Totales',
    'TotalITBISRetenido': 'Encabezado/Totales',
    'TotalISRRetencion': 'Encabezado/Totales',
    'TotalITBISPercepcion': 'Encabezado/Totales',
    'TotalISRPercepcion': 'Encabezado/Totales',
    
    # InformacionReferencia
    'NCFModificado': 'InformacionReferencia',
    'RNCOtroContribuyente': 'InformacionReferencia',
    'FechaNCFModificado': 'InformacionReferencia',
    'CodigoModificacion': 'InformacionReferencia',
    'RazonModificacion': 'InformacionReferencia',
}

item_fields = [
    'NumeroLinea', 'IndicadorFacturacion', 'IndicadorAgenteRetencionoPercepcion',
    'MontoITBISRetenido', 'MontoISRRetenido', 'NombreItem', 'IndicadorBienoServicio',
    'DescripcionItem', 'CantidadItem', 'UnidadMedida', 'CantidadReferencia',
    'UnidadReferencia', 'GradosAlcohol', 'PrecioUnitarioReferencia', 'FechaElaboracion',
    'FechaVencimientoItem', 'PesoNetoKilogramo', 'PesoNetoMineria', 'TipoAfiliacion',
    'Liquidacion', 'PrecioUnitarioItem', 'DescuentoMonto', 'RecargoMonto', 'MontoItem'
]

def format_value(k, val):
    if isinstance(val, float):
        if k in ['TipoeCF', 'NumeroLinea', 'IndicadorFacturacion', 'IndicadorBienoServicio']:
            return str(int(val))
        if val.is_integer():
            return f"{int(val)}.00"
        return f"{val:.2f}"
    return str(val)

def build_xml(row, index, is_rfce=False):
    root_name = 'RFCE' if is_rfce else 'ECF'
    ecf = Element(root_name, {'xmlns': 'http://dgii.gov.do/e-CF'})
    
    encabezado = SubElement(ecf, 'Encabezado')
    
    nodes = {
        'Encabezado': encabezado,
        'Encabezado/IdDoc': SubElement(encabezado, 'IdDoc'),
        'Encabezado/Emisor': SubElement(encabezado, 'Emisor'),
        'Encabezado/Comprador': SubElement(encabezado, 'Comprador'),
        'Encabezado/Totales': SubElement(encabezado, 'Totales')
    }
    
    # Formas de Pago
    formas_pago = []
    
    # Items
    items = {}

    info_ref = None

    for k, v in row.items():
        if is_empty(v):
            continue
            
        # Parse arrays
        match = re.match(r'^([A-Za-z]+)\[(\d+)\]$', k)
        if match:
            base_k = match.group(1)
            idx = int(match.group(2))
            
            if base_k in ['FormaPago', 'MontoPago']:
                while len(formas_pago) < idx:
                    formas_pago.append({})
                formas_pago[idx-1][base_k] = v
                continue
                
            if base_k in item_fields:
                if idx not in items:
                    items[idx] = {}
                items[idx][base_k] = v
                continue
                
        # Normal fields
        if k in node_mapping:
            path = node_mapping[k]
            if path == 'InformacionReferencia':
                if info_ref is None:
                    info_ref = Element('InformacionReferencia')
                SubElement(info_ref, k).text = format_value(k, v)
            else:
                if k == 'ENCF':
                    k = 'eNCF'
                SubElement(nodes[path], k).text = format_value(k, v)
                
    # Insert TablaFormasPago if exists
    if formas_pago:
        tabla_fp = SubElement(nodes['Encabezado/IdDoc'], 'TablaFormasPago')
        for fp in formas_pago:
            if 'FormaPago' in fp and not is_empty(fp['FormaPago']):
                fp_el = SubElement(tabla_fp, 'FormaDePago')
                SubElement(fp_el, 'FormaPago').text = format_value('FormaPago', fp.get('FormaPago'))
                SubElement(fp_el, 'MontoPago').text = format_value('MontoPago', fp.get('MontoPago', 0))

    # Insert Items
    if items:
        det_items = SubElement(ecf, 'DetallesItems')
        for idx in sorted(items.keys()):
            it = items[idx]
            item_el = SubElement(det_items, 'Item')
            for ik in item_fields:
                if ik in it and not is_empty(it[ik]):
                    SubElement(item_el, ik).text = format_value(ik, it[ik])
                    
    if info_ref is not None:
        ecf.append(info_ref)
        
    # Signature placeholder
    SubElement(ecf, 'FechaHoraFirma').text = datetime.now().strftime("%Y-%m-%dT%H:%M:%S")
    
    xml_str = tostring(ecf, encoding='utf-8', xml_declaration=True).decode('utf-8')
    xml_str = xml_str.replace("<?xml version='1.0' encoding='utf-8'?>", '<?xml version="1.0" encoding="utf-8"?>')
    
    out_dir = "storage/app/dgii_tests"
    os.makedirs(out_dir, exist_ok=True)
    
    prefix = "rfce_" if is_rfce else "ecf_"
    out_path = os.path.join(out_dir, f"{prefix}{index:02d}.xml")
    
    with open(out_path, "w", encoding="utf-8") as f:
        f.write(xml_str)
        
    print(f"Generated {out_path}")

if __name__ == "__main__":
    if os.path.exists('dgii_test_ecf.json'):
        with open('dgii_test_ecf.json', 'r', encoding='utf-8') as f:
            ecfs = json.load(f)
            for i, row in enumerate(ecfs):
                build_xml(row, i+1, False)
                
    if os.path.exists('dgii_test_rfce.json'):
        with open('dgii_test_rfce.json', 'r', encoding='utf-8') as f:
            rfces = json.load(f)
            for i, row in enumerate(rfces):
                build_xml(row, i+1, True)
