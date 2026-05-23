import sys, io, os
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
xsd_dir = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica'

# IndicadorAgenteRetencionoPercepcion - check type 41
for t in [41]:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    with open(path, encoding='utf-8') as f:
        c = f.read()
    idx = c.find('IndicadorAgenteRetencionoPercepcionType">')
    if idx >= 0:
        end = c.find('</xs:simpleType>', idx) + 20
        print(f'TYPE {t} IndicadorAgenteRetencionoPercepcionType:')
        print(c[idx:end])

# Type 45 IndicadorMontoGravadoType
for t in [45]:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    with open(path, encoding='utf-8') as f:
        c = f.read()
    idx = c.find('IndicadorMontoGravadoType">')
    if idx >= 0:
        end = c.find('</xs:simpleType>', idx) + 20
        print(f'\nTYPE {t} IndicadorMontoGravadoType:')
        print(c[idx:end])

# Type 46 IndicadorFacturacionType
for t in [46]:
    path = os.path.join(xsd_dir, f'e-CF {t} v.1.0.xsd')
    with open(path, encoding='utf-8') as f:
        c = f.read()
    idx = c.find('IndicadorFacturacionType">')
    if idx >= 0:
        end = c.find('</xs:simpleType>', idx) + 20
        print(f'\nTYPE {t} IndicadorFacturacionType:')
        print(c[idx:end])
