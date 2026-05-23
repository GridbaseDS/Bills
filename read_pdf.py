import sys, io, re
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
path = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica\e-CF 33 v.1.0.xsd'
with open(path, encoding='utf-8') as f:
    content = f.read()
# Find all top-level children of ECF: look for elements at indentation level 8 spaces (direct children of ECF/complexType/sequence)
lines = content.split('\n')
for i, line in enumerate(lines):
    if 'name="InformacionReferencia"' in line or 'name="Encabezado"' in line or 'name="DetallesItems"' in line or 'name="Paginacion"' in line or 'name="Subtotales"' in line or 'name="FechaHoraFirma"' in line or 'name="Descuentos' in line or 'name="Totales"' in line:
        print(f'L{i+1}: {line.strip()}')
