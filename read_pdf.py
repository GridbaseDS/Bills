import sys, io, os, re
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

xsd_dir = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica'
types = [31, 32, 33, 34, 41, 43, 44, 45, 46, 47]

for t in types:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    if not os.path.exists(path):
        continue
    with open(path, encoding='utf-8') as f:
        content = f.read()
    
    # Find the Item element definition and get ALL children
    idx = content.find('name="Item"')
    if idx >= 0:
        # Need to find the full Item definition - it's nested
        start = idx
        # Find the closing of Item's complex type sequence
        depth = 0
        pos = start
        items_block = ""
        # Just get a large chunk
        end_pos = min(len(content), start + 3000)
        block = content[start:end_pos]
        # Find all element names at the first level
        lines = block.split('\n')
        elements = []
        for line in lines:
            m = re.search(r'<xs:element\s+name="(\w+)"', line)
            if m:
                elements.append(m.group(1))
        elements = [e for e in elements if e not in ['Item', 'CodigosItem', 'FormaDePago', 'TablaCodigosItem', 'SubDescuento', 'SubcantidadItem', 'SubMontoItem', 'ImpuestoAdicional', 'Retencion']]
        print(f'TYPE {t} Item: {" > ".join(elements)}')
