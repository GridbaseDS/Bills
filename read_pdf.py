import sys, io, re
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
path = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica\e-CF 31 v.1.0.xsd'
with open(path, encoding='utf-8') as f:
    content = f.read()
m = re.search(r'name="FechaValidationType".*?</xs:simpleType>', content, re.DOTALL)
if m: print("FechaValidationType:", m.group())
m2 = re.search(r'name="DateTimeValidationType".*?</xs:simpleType>', content, re.DOTALL)
if m2: print("DateTimeValidationType:", m2.group())
