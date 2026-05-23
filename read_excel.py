import openpyxl
import json
import sys

wb = openpyxl.load_workbook(sys.argv[1])
ws = wb.active

data = []
headers = []
for i, row in enumerate(ws.iter_rows(values_only=True)):
    if i == 0:
        headers = [str(c).strip() if c else f'col_{j}' for j, c in enumerate(row)]
        print("HEADERS:", json.dumps(headers, ensure_ascii=False))
        continue
    if all(c is None for c in row):
        continue
    record = {}
    for j, val in enumerate(row):
        key = headers[j] if j < len(headers) else f'col_{j}'
        record[key] = val
    data.append(record)

print(f"\nTOTAL ROWS: {len(data)}")
for i, row in enumerate(data):
    print(f"\nROW {i+1}:", json.dumps(row, ensure_ascii=False, default=str))
