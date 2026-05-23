import sys, io, os, re
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
xsd_dir = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica'

# For type 31, get the RAW Emisor section to understand nesting
for t in [31]:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    with open(path, encoding='utf-8') as f:
        c = f.read()
    
    # Emisor section - raw
    idx = c.find('name="Emisor"')
    block = c[idx:idx+3000]
    end = block.find('</xs:sequence>') + 15
    print(f'=== TYPE {t} Emisor RAW ===')
    for line in block[:end].split('\n'):
        s = line.strip()
        if s and ('element' in s or 'sequence' in s or 'complexType' in s):
            print(f'  {s}')
    
    # Totales - raw first-level elements
    print(f'\n=== TYPE {t} Totales RAW ===')
    idx = c.find('name="Totales"')
    block = c[idx:idx+5000]
    # Count nesting depth to find first-level elements
    depth = 0
    for line in block.split('\n'):
        s = line.strip()
        if '<xs:sequence>' in s:
            depth += 1
        if '</xs:sequence>' in s:
            depth -= 1
            if depth <= 0:
                break
        if depth == 1 and 'element' in s and 'name=' in s:
            match = re.search(r'name="([^"]+)"[^>]*minOccurs="(\d+)"', s)
            if match:
                name, minO = match.groups()
                req = 'REQ' if minO == '1' else 'opt'
                print(f'  [{req}] {name}')
    
    # Item - raw first-level  
    print(f'\n=== TYPE {t} Item RAW (first-level) ===')
    idx = c.find('name="Item"')
    block = c[idx:idx+5000]
    depth = 0
    for line in block.split('\n'):
        s = line.strip()
        if '<xs:sequence>' in s:
            depth += 1
        if '</xs:sequence>' in s:
            depth -= 1
            if depth <= 0:
                break
        if depth == 1 and 'element' in s and 'name=' in s:
            match = re.search(r'name="([^"]+)"[^>]*minOccurs="(\d+)"', s)
            if match:
                name, minO = match.groups()
                req = 'REQ' if minO == '1' else 'opt'
                print(f'  [{req}] {name}')
