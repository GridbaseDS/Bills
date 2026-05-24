import sys, io, os, re
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
xsd_dir = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica'

for t in [31, 32, 33, 34, 41, 43, 44, 45, 46, 47]:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    with open(path, encoding='utf-8') as f:
        c = f.read()
    idx = c.find('name="IdDoc"')
    block = c[idx:idx+5000]
    has_flp = 'FechaLimitePago' in block
    has_tp = 'TipoPago' in block
    print(f'Type {t}: TipoPago={has_tp} FechaLimitePago={has_flp}')
