import sys, io, os, re
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
xsd_dir = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica'

for t in [31, 32, 41, 45]:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    with open(path, encoding='utf-8') as f:
        c = f.read()
    
    idx = c.find('name="IdDoc"')
    block = c[idx:idx+5000]
    # Find the FIRST xs:sequence after IdDoc
    seq_start = block.find('<xs:sequence>')
    # Find all elements at the first nesting level
    after_seq = block[seq_start:]
    
    print(f'TYPE {t} IdDoc:')
    depth = 0
    for line in after_seq.split('\n'):
        s = line.strip()
        if '<xs:sequence>' in s:
            depth += 1
            continue
        if '</xs:sequence>' in s:
            depth -= 1
            if depth <= 0:
                break
            continue
        if '<xs:complexType>' in s:
            depth += 1
            continue
        if '</xs:complexType>' in s:
            depth -= 1
            continue
        if depth == 1 and '<xs:element' in s and 'name=' in s:
            match = re.search(r'name="([^"]+)"', s)
            minO = re.search(r'minOccurs="(\d+)"', s)
            if match:
                name = match.group(1)
                minO_val = minO.group(1) if minO else '?'
                marker = ' <<<' if name == 'IndicadorMontoGravado' else ''
                req = 'REQ' if minO_val == '1' else 'opt'
                print(f'  [{req}] {name}{marker}')
    print()
