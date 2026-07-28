const http = require('http');
const PORT = 2001;

const server = http.createServer((req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  if ((req.url === '/tx_sale' || req.url === '/sale' || req.url === '/') && req.method === 'POST') {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        const data = JSON.parse(body || '{}');
        const amount = data.amount || 0;
        console.log(`\n==================================================`);
        console.log(` 💳 [Cardnet Saturn 1000 Sim] RECIBIDO COBRO`);
        console.log(` Monto: RD$ ${amount}`);
        console.log(` Procesando chip / sin contacto...`);
        console.log(`==================================================`);

        setTimeout(() => {
          const authCode = Math.floor(100000 + Math.random() * 900000).toString();
          const response = {
            approbationNumber: authCode,
            txnMessage: `APROBADA ${authCode}`,
            cardInformation: {
              maskedPAN: "411111******9547",
              cardSubType: "VISA"
            }
          };

          console.log(` ✅ [Cardnet Saturn 1000 Sim] TRANSACCIÓN APROBADA!`);
          console.log(` Código de Autorización: ${authCode}`);
          console.log(`==================================================\n`);

          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify(response));
        }, 2000);
      } catch(e) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Payload JSON inválido' }));
      }
    });
    return;
  }

  res.writeHead(200, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ status: 'Saturn 1000 Sim Running', port: PORT }));
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`==================================================`);
  console.log(` 🚀 Simulador Cardnet Saturn 1000 iniciado en puerto ${PORT}`);
  console.log(` Esperando cobros de Bills...`);
  console.log(`==================================================`);
});
