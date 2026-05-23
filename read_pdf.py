import sys, io, os
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
xsd_dir = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica'

for t in [47, 44]:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    with open(path, encoding='utf-8') as f:
        c = f.read()
    idx = c.find('name="Item"')
    block = c[idx:idx+2000]
    print(f'=== TYPE {t} Item ===')
    for line in block.split('\n'):
        if 'element' in line.lower() and 'name=' in line:
            print(line.strip())
    print()
    # Check IndicadorFacturacion type
    idx2 = c.find('IndicadorFacturacionType">')
    if idx2 >= 0:
        end = c.find('</xs:simpleType>', idx2) + 20
        print(f'  IndicadorFacturacionType: {c[idx2:end]}')
    print()
