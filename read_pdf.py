import sys, io, os, re
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
xsd_dir = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica'

for t in [33, 34]:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    with open(path, encoding='utf-8') as f:
        c = f.read()
    
    idx = c.find('name="IdDoc"')
    block = c[idx:idx+3000]
    end = block.find('</xs:sequence>')
    if end > 0:
        block = block[:end + 15]
    
    print(f'=== TYPE {t} IdDoc RAW ===')
    print(block[:2000])
    print()
