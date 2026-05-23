import fitz, re, sys
sys.stdout.reconfigure(encoding='utf-8')

doc = fitz.open(r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica\Firmado de e-CF.pdf')
text = ''.join(p.get_text() for p in doc)

# Print the ENTIRE document
print(text)
