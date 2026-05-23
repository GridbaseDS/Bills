import fitz, re, sys
sys.stdout.reconfigure(encoding='utf-8')
doc = fitz.open(r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica\Descripcion-tecnica-de-facturacion-electronica.pdf')
text = ''.join(p.get_text() for p in doc)

# Find the full curl example for facturaselectronicas
idx = text.find('facturaselectronic')
if idx > -1:
    print("=== E-CF ENDPOINT DETAILS ===")
    print(text[max(0,idx-200):idx+800])

print("\n\n=== RFCE ENDPOINT DETAILS ===")
idx2 = text.find('recepcionfc/api')
if idx2 > -1:
    print(text[max(0,idx2-200):idx2+800])
