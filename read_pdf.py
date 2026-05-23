import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
path = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica\e-CF 32 v.1.0.xsd'
with open(path, encoding='utf-8') as f:
    content = f.read()
# Find IdDoc to see all its children in order
idx = content.find('name="IdDoc"')
end = content.find('</xs:sequence>', idx) + 20
print(content[idx:end])
