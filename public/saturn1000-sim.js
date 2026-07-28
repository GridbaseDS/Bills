const http = require('http');
const PORT = 2001;

const server = http.createServer((req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', '*');

  let body = '';
  req.on('data', chunk => { body += chunk; });
  req.on('end', () => {
    const timestamp = new Date().toLocaleTimeString();
    console.log(`\n==================================================`);
    console.log(` 📥 [${timestamp}] PETICIÓN RECIBIDA EN TU S9`);
    console.log(`--------------------------------------------------`);
    console.log(` 🔹 MÉTODO:  ${req.method}`);
    console.log(` 🔹 RUTA:    ${req.url}`);
    console.log(` 🔹 HEADERS: ${JSON.stringify(req.headers, null, 2)}`);
    console.log(`--------------------------------------------------`);
    console.log(` 📦 DATOS RECIBIDOS (BODY):`);
    try {
      console.log(JSON.stringify(JSON.parse(body), null, 2));
    } catch(e) {
      console.log(body || '(Body vacío)');
    }
    console.log(`==================================================`);

    if (req.method === 'OPTIONS') {
      res.writeHead(204);
      res.end();
      return;
    }

    // Responder con éxito a Bills (Simulación Cardnet Saturn 1000)
    const authCode = Math.floor(100000 + Math.random() * 900000).toString();
    const response = {
      approbationNumber: authCode,
      txnMessage: `APROBADA ${authCode}`,
      cardInformation: {
        maskedPAN: "411111******9547",
        cardSubType: "VISA"
      }
    };

    console.log(` ✅ RESPUESTA ENVIADA A BILLS: 200 OK (Autorización: ${authCode})\n`);

    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(response));
  });
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`==================================================`);
  console.log(` 🚀 INSPECTOR DE PETICIONES BILLS ACTIVO (PUERTO ${PORT})`);
  console.log(` Esperando peticiones en tu Samsung S9...`);
  console.log(`==================================================`);
});
