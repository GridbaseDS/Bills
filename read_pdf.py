import fitz, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
doc = fitz.open(r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica\Descripcion-tecnica-de-facturacion-electronica.pdf')
for page in doc:
    text = page.get_text()
    tl = text.lower()
    if 'aprobacion' in tl or 'acecf' in tl or 'aprobacioncomercial' in tl:
        print(f'=== PAGE {page.number + 1} ===')
        print(text[:4000])
        print('---')
