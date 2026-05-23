import sys, io, os
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
base = r'D:\WEBS\GRIDBASE DIGITAL SOLUTIONS\Documentación Facturación Electónica'
for f in os.listdir(base):
    if f.startswith('e-CF') and f.endswith('.xsd'):
        path = os.path.join(base, f)
        with open(path, encoding='utf-8') as fh:
            lines = fh.readlines()
        print(f'\n=== {f} - IdDoc fields ===')
        in_iddoc = False
        for line in lines:
            if 'name="IdDoc"' in line or (in_iddoc and '</xs:sequence>' not in line):
                in_iddoc = True
                stripped = line.strip()
                if 'element' in stripped and 'name=' in stripped:
                    print(f'  {stripped}')
            if in_iddoc and '</xs:sequence>' in line:
                in_iddoc = False
