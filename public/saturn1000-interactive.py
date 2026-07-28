import http.server, json

class H(http.server.BaseHTTPRequestHandler):
    def do_POST(self):
        length = int(self.headers.get('Content-Length', 0))
        data = self.rfile.read(length).decode('utf-8')
        
        print('\n' + '='*50)
        print(' 📥 PETICIÓN RECIBIDA DE BILLS EN TU S9')
        print('-'*50)
        print(f' 🔹 MÉTODO:  {self.command}')
        print(f' 🔹 RUTA:    {self.path}')
        print(f' 🔹 HEADERS: {dict(self.headers)}')
        print('-'*50)
        print(' 📦 DATOS RECIBIDOS (BODY):')
        try:
            parsed = json.loads(data)
            print(json.dumps(parsed, indent=2))
            amount = parsed.get('amount', 'N/A')
            print(f'\n 💰 MONTO DE COBRO: RD$ {amount}')
        except Exception:
            print(data or '(Vacío)')
        print('='*50)

        ans = input('\n 👉 ¿Deseas APROBAR esta transacción? (s/n) [Defecto: s]: ').strip().lower()
        
        if ans in ['', 's', 'y', 'si', 'yes']:
            auth_code = '419886'
            res = {
                'approbationNumber': auth_code,
                'txnMessage': f'APROBADA {auth_code}',
                'cardInformation': {
                    'maskedPAN': '411111******9547',
                    'cardSubType': 'VISA'
                }
            }
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(res).encode('utf-8'))
            print(f' ✅ [APROBADA] Respuesta 200 OK enviada a Bills (Autorización: {auth_code})\n')
        else:
            res = {
                'approbationNumber': None,
                'txnMessage': 'DECLINADA POR EL USUARIO',
                'cardInformation': None
            }
            self.send_response(402)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(res).encode('utf-8'))
            print(' ❌ [DECLINADA] Transacción rechazada enviada a Bills\n')

s = http.server.HTTPServer(('0.0.0.0', 2001), H)
print('==================================================')
print(' 🚀 SIMULADOR INTERACTIVO SATURN 1000 ACTIVO')
print(' Puerto 2001 — Esperando cobros de Bills en tu S9...')
print('==================================================')
s.serve_forever()
