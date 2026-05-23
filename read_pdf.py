import fitz, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
# Check the most recent technical description doc for updated expiry date
path = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica\Descripcion-tecnica-de-facturacion-electronica.pdf'
doc = fitz.open(path)
for page in doc:
    text = page.get_text()
    if '31-12-' in text and ('secuencia' in text.lower() or 'vencimiento' in text.lower()):
        print(f'=== Page {page.number+1} ===')
        # Find the specific line with 31-12-
        for line in text.split('\n'):
            if '31-12-' in line:
                print(f'  >>> {line.strip()}')
