process.on('uncaughtException', (err) => {
  console.error('[!] Excepción no capturada en BillsBridge:', err.message);
});

process.on('unhandledRejection', (reason) => {
  console.error('[!] Promesa rechazada no capturada:', reason);
});

if (process.stdin && process.stdin.resume) {
  process.stdin.resume();
}

// ─────────────────────────────────────────────────────────────
// CAPTURA DE LOGS EN MEMORIA PARA LA CONSOLA WEB EN VIVO
// ─────────────────────────────────────────────────────────────
const logsMemory = [];
function addLog(msg) {
  var timestamp = new Date().toLocaleTimeString();
  var line = '[' + timestamp + '] ' + msg;
  logsMemory.push(line);
  if (logsMemory.length > 200) logsMemory.shift();
}

const origLog = console.log;
const origErr = console.error;
const origWarn = console.warn;

console.log = function(...args) {
  origLog.apply(console, args);
  addLog(args.join(' '));
};

console.error = function(...args) {
  origErr.apply(console, args);
  addLog('❌ ERROR: ' + args.join(' '));
};

console.warn = function(...args) {
  origWarn.apply(console, args);
  addLog('⚠️ WARN: ' + args.join(' '));
};

const http = require('http');
const https = require('https');
const net = require('net');
const { URL } = require('url');
const path = require('path');
const fs = require('fs');
const os = require('os');
const { exec } = require('child_process');

const PORT = 8080;

var HTML_DASHBOARD = '';
try {
  HTML_DASHBOARD = fs.readFileSync(path.join(__dirname, 'dashboard.html'), 'utf8');
} catch (e) {
  HTML_DASHBOARD = '<html><body><h1>BillsBridge v1.4.0</h1><p>Dashboard file not found. Service is running on port 8080.</p></body></html>';
}

// Caracteres especiales del protocolo Cardnet (SPDH/Sockets)
const SYN = 0x16;
const EOM = 0x19;
const ENQ = 0x05;
const ACK = 0x06;
const STX = 0x02;
const ETX = 0x03;
const FS = '\x1C'; // File Separator (0x1C)

// Determinar el directorio del ejecutable real
const isPackaged = process.pkg !== undefined;
const exeDir = isPackaged ? path.dirname(process.execPath) : __dirname;
const configPath = path.join(exeDir, 'config.json');

let currentConfig = null;
let allowedDomain = '*';
let isPollingStarted = false;

// Cargar configuración inicial si existe
if (fs.existsSync(configPath)) {
  try {
    const raw = fs.readFileSync(configPath, 'utf8');
    currentConfig = JSON.parse(raw);
    allowedDomain = currentConfig.domain || '*';
  } catch (err) {
    console.error('[!] Error leyendo config.json:', err.message);
  }
}

// Iniciar servidor HTTP
startServer();

// ─────────────────────────────────────────────────────────────
// FUNCIÓN PARA INSTALAR EL SERVICIO EN SEGUNDO PLANO
// ─────────────────────────────────────────────────────────────
function installScheduledTask() {
  if (process.platform !== 'win32') return;
  console.log('[BillsBridge] Intentando registrar servicio de Windows en segundo plano...');
  try {
    const exePath = isPackaged ? process.execPath : path.join(__dirname, 'index.js');
    const workingDir = exeDir;
    const taskName = "BillsBridge";

    let psCommand;
    if (isPackaged) {
      psCommand = 'Register-ScheduledTask -TaskName \'' + taskName + '\' -Trigger (New-ScheduledTaskTrigger -AtStartup) -Action (New-ScheduledTaskAction -Execute \'' + exePath + '\' -WorkingDirectory \'' + workingDir + '\') -Settings (New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries) -Force';
    } else {
      const nodeExe = process.execPath;
      psCommand = 'Register-ScheduledTask -TaskName \'' + taskName + '\' -Trigger (New-ScheduledTaskTrigger -AtStartup) -Action (New-ScheduledTaskAction -Execute \'' + nodeExe + '\' -Argument \'' + exePath + '\' -WorkingDirectory \'' + workingDir + '\') -Settings (New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries) -Force';
    }

    exec('powershell -NoProfile -ExecutionPolicy Bypass -Command "' + psCommand + '"', function() {});
  } catch (err) {}
}

// ─────────────────────────────────────────────────────────────
// SERVIDOR HTTP CON CONTROL CORS DINÁMICO
// ─────────────────────────────────────────────────────────────
function startServer() {
  const server = http.createServer((req, res) => {
    const origin = req.headers.origin;

    // CORS dinámico y permisivo para peticiones de la app y puente local
    if (origin) {
      res.setHeader('Access-Control-Allow-Origin', origin);
    } else {
      res.setHeader('Access-Control-Allow-Origin', '*');
    }

    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    res.setHeader('Access-Control-Allow-Credentials', 'true');

    if (req.method === 'OPTIONS') {
      res.writeHead(204);
      res.end();
      return;
    }

    const parsedUrl = new URL(req.url, 'http://' + (req.headers.host || 'localhost:8080'));

    // Dashboard UI principal
    if (parsedUrl.pathname === '/' && req.method === 'GET') {
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(HTML_DASHBOARD);
      return;
    }

    // Endpoint de logs en vivo
    if (parsedUrl.pathname === '/logs' && req.method === 'GET') {
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ success: true, logs: logsMemory }));
      return;
    }

    // Endpoint de diagnóstico
    if (parsedUrl.pathname === '/status' && req.method === 'GET') {
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({
        success: true,
        service: 'BillsBridge',
        status: 'running',
        version: '1.4.0',
        linked: allowedDomain !== '*',
        allowed_domain: allowedDomain
      }));
      return;
    }

    // Endpoint de vinculación web (Configuración)
    if (parsedUrl.pathname === '/configure' && req.method === 'POST') {
      let body = '';
      req.on('data', chunk => { body += chunk; });
      req.on('end', () => {
        try {
          // Si ya está configurado y el origen de la petición no coincide, denegar para seguridad
          if (allowedDomain !== '*' && origin && new URL(origin).hostname !== allowedDomain) {
            res.writeHead(403, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'El Bridge ya está vinculado a otro dominio.' }));
            return;
          }

          const params = JSON.parse(body);
          let domain = params.domain ? params.domain.trim() : '';
          domain = domain.replace(/^(https?:\/\/)?(www\.)?/, '').replace(/\/$/, '');

          if (!domain) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Dominio inválido.' }));
            return;
          }

          // Guardar configuración (en disco y memoria)
          const config = { domain: domain };
          try {
            fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
          } catch (fsErr) {
            console.warn('[!] Advertencia: No se pudo escribir config.json en disco:', fsErr.message);
          }
          currentConfig = config;
          allowedDomain = domain;

          console.log('==================================================');
          console.log('[BillsBridge] VINCULACIÓN EXITOSA');
          console.log('Dominio autorizado: https://' + domain);
          console.log('==================================================');

          // Intentar registrar el servicio de Windows
          installScheduledTask();

          // Activar el sondeo del servidor en la nube inmediatamente
          startCloudPolling();

          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: true, message: 'Bridge vinculado a ' + domain + ' con éxito.' }));

        } catch (err) {
          res.writeHead(500, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: false, message: 'Error en la vinculación: ' + err.message }));
        }
      });
      return;
    }

    // Endpoint para procesar cobros
    if (parsedUrl.pathname === '/charge' && req.method === 'POST') {
      // Bloquear cobros si el bridge no está vinculado
      if (allowedDomain === '*') {
        res.writeHead(403, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, message: 'El Bridge no ha sido vinculado a ningún dominio de Bills.' }));
        return;
      }

      let body = '';
      req.on('data', chunk => { body += chunk; });
      req.on('end', async () => {
        try {
          const params = JSON.parse(body);
          const { driver, amount, ip, port, merchant_id, terminal_id, invoice_id, timeout = 60 } = params;

          console.log('[BillsBridge] Iniciando cobro: ' + amount + ' via ' + driver + ' (Factura #' + invoice_id + ')');

          if (!driver || !amount) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Faltan parámetros: driver o amount.' }));
            return;
          }

          let result;
          switch (driver) {
            case 'mock':
              result = await handleMockCharge(amount);
              break;
            case 'cardnet_local':
              result = await handleCardnetLocalCharge(amount, ip, port, invoice_id, timeout);
              break;
            case 'cardnet_android':
              result = await handleCardnetAndroidCharge(amount, ip, port, merchant_id, terminal_id, invoice_id, timeout);
              break;
            case 'azul_local':
              result = await handleAzulLocalCharge(amount, ip, port, timeout);
              break;
            default:
              res.writeHead(400, { 'Content-Type': 'application/json' });
              res.end(JSON.stringify({ success: false, message: "Driver '" + driver + "' no soportado." }));
              return;
          }

          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify(result));

        } catch (err) {
          console.error('[BillsBridge] Error en transacción:', err);
          res.writeHead(500, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: false, message: 'Error interno: ' + err.message }));
        }
      });
      return;
    }

    // Endpoint para inspección diagnóstica avanzada del POS
    if (parsedUrl.pathname === '/inspect-pos' && req.method === 'POST') {
      let body = '';
      req.on('data', chunk => { body += chunk; });
      req.on('end', async () => {
        try {
          const params = JSON.parse(body);
          const { ip, port = 2001, amount = 0.01, merchant_id, terminal_id } = params;

          if (!ip) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'IP requerida.' }));
            return;
          }

          var amountVal = parseFloat(amount || '1');
          var amountCents = Math.round(amountVal >= 100 ? amountVal : amountVal * 100);
          var targetUrl = 'http://' + ip + ':' + port + '/tx_sale?amount=' + amountCents;
          var payload = { amount: amountCents };
          if (merchant_id) payload.merchantId = merchant_id;
          if (terminal_id) payload.terminalId = terminal_id;
          var payloadData = JSON.stringify(payload);

          var posReq = http.request(targetUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Content-Length': Buffer.byteLength(payloadData)
            },
            timeout: 8000
          }, function(posRes) {
            var posBody = '';
            posRes.on('data', function(chunk) { posBody += chunk; });
            posRes.on('end', function() {
              res.writeHead(200, { 'Content-Type': 'application/json' });
              res.end(JSON.stringify({
                success: true,
                target_url: targetUrl,
                status_code: posRes.statusCode,
                headers: posRes.headers,
                raw_response: posBody,
                sent_payload: payload
              }));
            });
          });

          posReq.on('timeout', function() {
            posReq.destroy();
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, error: 'TIMEOUT', message: 'El dispositivo en ' + ip + ':' + port + ' no respondió en 8 segundos.' }));
          });

          posReq.on('error', function(err) {
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, error: 'CONNECTION_FAILED', message: 'Fallo de conexión a ' + ip + ':' + port + ': ' + err.message }));
          });

          posReq.write(payloadData);
          posReq.end();
        } catch(e) {
          res.writeHead(500, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: false, message: e.message }));
        }
      });
      return;
    }

    // Endpoint para listar las impresoras instaladas en el sistema (Windows / Mac / Linux)
    if (parsedUrl.pathname === '/printers' && req.method === 'GET') {
      try {
        var cmd = '';
        if (process.platform === 'win32') {
          cmd = 'powershell -Command "Get-CimInstance -ClassName Win32_Printer | Select-Object -ExpandProperty Name"';
        } else {
          cmd = 'lpstat -e';
        }

        exec(cmd, function(err, stdout, stderr) {
          if (err) {
            res.writeHead(500, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Error listando impresoras: ' + err.message, printers: [] }));
            return;
          }

          var printersList = stdout.split(/\r?\n/)
            .map(function(p) { return p.trim(); })
            .filter(function(p) { return p.length > 0; });

          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: true, printers: printersList }));
        });
      } catch (err) {
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, message: err.message, printers: [] }));
      }
      return;
    }

    // Endpoint para impresión silenciosa directa en la impresora de caja
    if (parsedUrl.pathname === '/print-ticket' && req.method === 'POST') {
      let body = '';
      req.on('data', chunk => { body += chunk; });
      req.on('end', async () => {
        try {
          const params = JSON.parse(body);
          const { pdf_url, printer_name } = params;

          if (!pdf_url) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Falta parámetro pdf_url.' }));
            return;
          }

          console.log('[BillsBridge Print] Petición de impresión silenciosa enviada a: ' + (printer_name || 'Impresora Predeterminada'));
          const result = await handleSilentPrint(pdf_url, printer_name);

          const statusCode = result.success ? 200 : 500;
          res.writeHead(statusCode, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify(result));

        } catch (err) {
          console.error('[BillsBridge Print] Error en impresión:', err);
          res.writeHead(500, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: false, message: 'Error interno: ' + err.message }));
        }
      });
      return;
    }

    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ success: false, message: 'Ruta no encontrada.' }));
  });

  server.on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
      console.error('[!] El puerto ' + PORT + ' está ocupado por otra instancia o aplicación.');
      console.error('[!] Reintentando en puerto alternativo 8081...');
      setTimeout(() => {
        try { server.listen(8081, '0.0.0.0'); } catch(e) {}
      }, 1000);
    } else {
      console.error('[!] Error en el servidor HTTP:', err.message);
    }
  });

  server.listen(PORT, '0.0.0.0', () => {
    console.log('==================================================');
    console.log(' BillsBridge v1.4.0 (GUI Edition) - Iniciado en puerto ' + PORT);
    if (allowedDomain === '*') {
      console.log(' [WARNING] ESTADO: Sin vincular.');
      console.log(' Abre el panel de Bills y haz clic en "Vincular"');
    } else {
      console.log(' [OK] ESTADO: Vinculado.');
      console.log(' Dominio autorizado: https://' + allowedDomain);
      // Iniciar el polling de nube al arrancar
      startCloudPolling();
    }
    console.log('==================================================');

    try {
      if (process.platform === 'win32') {
        exec('start http://localhost:8080');
      } else if (process.platform === 'darwin') {
        exec('open http://localhost:8080');
      }
    } catch(e){}
  });
}

// ─────────────────────────────────────────────────────────────
// SISTEMA DE SONDEO (POLLING) DE LA NUBE PARA MULTI-DISPOSITIVO
// ─────────────────────────────────────────────────────────────
let isPollingActive = false;

function startCloudPolling() {
  if (allowedDomain === '*' || isPollingStarted) return;
  isPollingStarted = true;

  console.log('[BillsBridge] Iniciando sondeo de nube en https://' + allowedDomain + ' ...');

  setInterval(async () => {
    if (isPollingActive) return;
    isPollingActive = true;

    try {
      var pollUrl = 'https://' + allowedDomain + '/api/pos/bridge/poll';
      var response = await makeGetRequest(pollUrl);
      
      if (response && response.success && response.pending && response.transaction) {
        const tx = response.transaction;
        console.log('[BillsBridge] [Nube] Transacción recibida para Factura #' + tx.invoice_id + ' (RD$ ' + tx.amount + ')');

        let result;
        switch (tx.driver) {
          case 'mock':
            result = await handleMockCharge(tx.amount);
            break;
          case 'cardnet_local':
            result = await handleCardnetLocalCharge(tx.amount, tx.ip, tx.port, tx.invoice_id, tx.timeout);
            break;
          case 'cardnet_android':
            result = await handleCardnetAndroidCharge(tx.amount, tx.ip, tx.port, tx.merchant_id, tx.terminal_id, tx.invoice_id, tx.timeout);
            break;
          case 'azul_local':
            result = await handleAzulLocalCharge(tx.amount, tx.ip, tx.port, tx.timeout);
            break;
          default:
            result = { success: false, message: "Driver '" + tx.driver + "' no soportado." };
        }

        // Reportar respuesta a la nube
        console.log('[BillsBridge] [Nube] Enviando resultado al servidor...');
        var reportUrl = 'https://' + allowedDomain + '/api/pos/bridge/respond';
        await makePostRequest(reportUrl, {
          invoice_id: String(tx.invoice_id),
          status: result.success ? 'approved' : 'declined',
          auth_code: result.auth_code || '000000',
          card_number: result.card_number || '************0000',
          card_type: result.card_type || 'Tarjeta',
          message: result.message || (result.success ? 'Aprobada' : 'Declinada')
        });

        console.log('[BillsBridge] [Nube] Resultado reportado con exito.');
      }
    } catch (err) {
      // Ignorar errores silenciosamente para no inundar consola por fallos temporales de red
    } finally {
      isPollingActive = false;
    }
  }, 2000);
}

// ─────────────────────────────────────────────────────────────
// CLIENTE HTTP LIVIANO INTEGRADO (SIN DEPENDENCIAS)
// ─────────────────────────────────────────────────────────────
function makeGetRequest(urlStr) {
  return new Promise((resolve) => {
    try {
      const u = new URL(urlStr);
      const httpLib = u.protocol === 'https:' ? https : http;
      const req = httpLib.get(urlStr, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
          try {
            resolve(JSON.parse(data));
          } catch (e) {
            resolve({ success: false, message: 'JSON inválido' });
          }
        });
      });
      req.on('error', (err) => resolve({ success: false, message: err.message }));
      req.setTimeout(8000, () => { try { req.destroy(); } catch(e){} resolve({ success: false, message: 'Timeout' }); });
    } catch(e) {
      resolve({ success: false, message: e.message });
    }
  });
}

function makePostRequest(urlStr, body) {
  return new Promise((resolve) => {
    try {
      const u = new URL(urlStr);
      const httpLib = u.protocol === 'https:' ? https : http;
      const postData = typeof body === 'string' ? body : JSON.stringify(body);

      const options = {
        hostname: u.hostname,
        port: u.port || (u.protocol === 'https:' ? 443 : 80),
        path: u.pathname + u.search,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(postData)
        }
      };

      const req = httpLib.request(options, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
          try {
            resolve(JSON.parse(data));
          } catch (e) {
            resolve({ success: false, message: 'JSON inválido' });
          }
        });
      });
      req.on('error', (err) => resolve({ success: false, message: err.message }));
      req.setTimeout(8000, () => { try { req.destroy(); } catch(e){} resolve({ success: false, message: 'Timeout' }); });
      req.write(postData);
      req.end();
    } catch(e) {
      resolve({ success: false, message: e.message });
    }
  });
}

// ─────────────────────────────────────────────────────────────
// PROCESADORES DE COBRO (DRIVERS)
// ─────────────────────────────────────────────────────────────

function handleMockCharge(amount) {
  return new Promise((resolve) => {
    console.log('[BillsBridge] Procesando cobro SIMULADO...');
    setTimeout(() => {
      if (parseFloat(amount) === 99.99) {
        resolve({
          success: false,
          message: 'Transacción Declinada: Fondos Insuficientes (Simulado)'
        });
      } else {
        const randAuth = Math.floor(100000 + Math.random() * 900000).toString();
        const randCard = '411111******' + Math.floor(1000 + Math.random() * 9000).toString();
        resolve({
          success: true,
          status: 'approved',
          auth_code: randAuth,
          card_number: randCard,
          card_type: 'Visa',
          message: 'Transacción Aprobada (Simulador Local)'
        });
      }
    }, 3000);
  });
}

function handleCardnetLocalCharge(amount, ip, port, invoiceId, timeoutSec) {
  return new Promise((resolve) => {
    const targetPort = port || 7060;
    if (!ip) {
      return resolve({ success: false, message: 'IP del terminal no configurada.' });
    }

    console.log('[BillsBridge] Conectando a Cardnet por socket TCP en ' + ip + ':' + targetPort + '...');
    const socket = new net.Socket();
    let state = 0;
    let responseBuffer = Buffer.alloc(0);
    let timeoutTimer = null;

    const cleanUp = () => {
      if (timeoutTimer) clearTimeout(timeoutTimer);
      socket.destroy();
    };

    timeoutTimer = setTimeout(() => {
      console.log('[BillsBridge] Timeout superado en la conexión TCP.');
      cleanUp();
      resolve({ success: false, message: 'Tiempo de espera agotado en el Verifone.' });
    }, timeoutSec * 1000);

    socket.connect(targetPort, ip, () => {
      console.log('[BillsBridge] Socket conectado. Iniciando handshake (enviando SYN)...');
      socket.write(Buffer.from([SYN]));
    });

    socket.on('data', (chunk) => {
      if (state === 0) {
        if (chunk[0] === EOM) {
          state = 1;
          if (chunk.length > 1 && chunk[1] === ENQ) {
            sendPayload();
          }
        }
      } else if (state === 1) {
        if (chunk[0] === ENQ) {
          sendPayload();
        }
      } else if (state === 2) {
        if (chunk[0] === ACK) {
          state = 3;
        }
      } else if (state === 3) {
        responseBuffer = Buffer.concat([responseBuffer, chunk]);
        const etxIndex = responseBuffer.indexOf(ETX);
        if (etxIndex !== -1) {
          const stxIndex = responseBuffer.indexOf(STX);
          let dataBuffer;
          if (stxIndex !== -1 && stxIndex < etxIndex) {
            dataBuffer = responseBuffer.subarray(stxIndex + 1, etxIndex);
          } else {
            dataBuffer = responseBuffer.subarray(0, etxIndex);
          }

          const responseText = dataBuffer.toString('ascii');
          cleanUp();

          const fields = responseText.split(FS);
          const authCode = fields[8] ? fields[8].trim() : '';
          const cardNo = fields[3] ? fields[3].trim() : '************0000';
          const cardType = fields[1] ? fields[1].trim() : 'Tarjeta';

          if (fields[0] && fields[0].trim() === '99') {
            return resolve({
              success: false,
              message: 'Transacción declinada o no progresó en el Verifone.'
            });
          }

          if (authCode && authCode !== '000000') {
            return resolve({
              success: true,
              status: 'approved',
              auth_code: authCode,
              card_number: cardNo,
              card_type: cardType,
              message: 'Transacción Aprobada'
            });
          }

          let errText = 'Transacción declinada por el Verifone.';
          for (const f of fields) {
            if (f.includes('DECLINADA') || f.includes('FONDOS INSUF') || f.includes('PIN INVALIDO') || f.includes('ERROR')) {
              errText = f.trim();
              break;
            }
          }
          resolve({ success: false, message: errText });
        }
      }
    });

    socket.on('error', (err) => {
      console.error('[BillsBridge] Error en socket:', err.message);
      cleanUp();
      resolve({ success: false, message: 'Error de conexion fisica con Verifone: ' + err.message });
    });

    function sendPayload() {
      state = 2;
      const amountStr = Math.round(parseFloat(amount) * 100).toString().padStart(12, '0');
      const taxStr = '000000000000';
      const otherTaxesStr = '000000000000';
      const ticketStr = (invoiceId || '000000').slice(-6).padStart(6, '0');

      var txMessage = 'CN00' + FS + amountStr + FS + taxStr + FS + otherTaxesStr + FS + ticketStr + FS;
      socket.write(Buffer.from(txMessage, 'ascii'));
    }
  });
}

function handleCardnetAndroidCharge(amount, ip, port, merchantId, terminalId, invoiceId, timeoutSec) {
    // Guard: si se pasó timeout (ej: 90 o 60) como 4to argumento por firma legacy
    let actualMerchantId = merchantId;
    if (typeof actualMerchantId === 'number' && (timeoutSec === undefined || actualMerchantId <= 300)) {
      if (!timeoutSec) timeoutSec = actualMerchantId;
      actualMerchantId = null;
    }

    const amountVal = Math.round(parseFloat(amount) * 100) / 100;
    const amountCents = Math.round(amountVal * 100);

    // En CardNET Android SmartPOS REST, el monto se envía en Pesos (ej: 2800) o Centavos (ej: 280000).
    const payloadVariants = [
      { amount: amountVal },
      { amount: amountVal.toFixed(2) },
      { amount: amountCents }
    ];

    if (actualMerchantId) payloadVariants.forEach(p => p.merchantId = actualMerchantId);
    if (terminalId) payloadVariants.forEach(p => p.terminalId = terminalId);

    var endpoints = [
      '/tx_sale?amount=' + amountVal,
      '/tx_sale?amount=' + amountCents,
      '/tx_sale'
    ];

    let endpointIdx = 0;
    let payloadIdx = 0;
    let pollCount = 0;

    function tryNextCombination() {
      if (endpointIdx >= endpoints.length) {
        return resolve({
          success: false,
          message: 'El Verifone CardNET no completo la transaccion. Revisa la pantalla del Verifone.'
        });
      }

      const endpoint = endpoints[endpointIdx];
      const payloadObj = payloadVariants[payloadIdx];
      const postData = JSON.stringify(payloadObj);
      var reqUrl = 'http://' + ip + ':' + targetPort + endpoint;

      console.log('[BillsBridge CardNET] Enviando a Verifone (' + reqUrl + '): ' + postData);

      var cardReq = http.request(reqUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(postData)
        },
        timeout: Math.min(timeoutSec, 60) * 1000
      }, function(cardRes) {
        var respBody = '';
        cardRes.on('data', function(chunk) { respBody += chunk; });
        cardRes.on('end', function() {
          console.log('[BillsBridge CardNET] HTTP ' + cardRes.statusCode + ' | Respuesta: "' + respBody + '"');

          if (cardRes.statusCode === 200) {
            try {
              var data = JSON.parse(respBody || '{}');
              const authCode = data.approbationNumber || data.authCode || data.auth_code || data.approvalCode || '';
              const txnMessage = data.txnMessage || data.resultMessage || data.message || data.error || 'Tarjeta Declinada / Error';
              const code = data.code;

              // 1. SI LA TRANSACCIÓN ESTÁ EN PROGRESO (esperando que el cliente pase la tarjeta en el Verifone)
              if (respBody.indexOf('Transaccion en progreso') !== -1 || respBody.indexOf('Transacción en progreso') !== -1 || (data.error && data.error.indexOf('progreso') !== -1)) {
                pollCount++;
                console.log('[BillsBridge CardNET] Transaccion activa en pantalla del Verifone (intento #' + pollCount + '). Esperando 3s...');
                if (pollCount < 20) {
                  setTimeout(() => tryNextCombination(), 3000);
                } else {
                  resolve({ success: false, message: 'Tiempo de espera agotado al pasar la tarjeta en el Verifone.' });
                }
                return;
              }

              // 2. SI EL MONTO FUE RECHAZADO POR RANGO O FORMATO (ej: 280000 excedió límite)
              if (code === -1 && (respBody.indexOf('formato') !== -1 || respBody.indexOf('mayor') !== -1 || respBody.indexOf('menor') !== -1)) {
                console.warn('[BillsBridge CardNET] Formato rechazado (' + respBody + '). Probando variante siguiente...');
                payloadIdx++;
                if (payloadIdx >= payloadVariants.length) {
                  payloadIdx = 0;
                  endpointIdx++;
                }
                return tryNextCombination();
              }

              const cardInfo = data.cardInformation || data.ticket || data.cardInfo || {};
              const maskedPan = cardInfo.maskedPAN || cardInfo.CardNumber || cardInfo.cardNumber || '************0000';
              const cardSubType = cardInfo.cardSubType || cardInfo.CardType || cardInfo.cardType || 'Tarjeta';

              if (authCode && authCode !== '000000' && authCode !== 0) {
                console.log('[BillsBridge CardNET] APROBADA! Auth: ' + authCode);
                return resolve({
                  success: true,
                  status: 'approved',
                  auth_code: String(authCode),
                  card_number: maskedPan,
                  card_type: cardSubType,
                  message: txnMessage
                });
              } else {
                console.warn('[BillsBridge CardNET] DECLINADA / RECHAZADA: ' + txnMessage);
                return resolve({ success: false, message: 'CardNET: ' + txnMessage });
              }
            } catch (e) {
              console.error('[BillsBridge CardNET] Error parseando JSON:', e.message);
              return resolve({ success: false, message: 'Error procesando respuesta del POS CardNET.' });
            }
          } else if (cardRes.statusCode === 404) {
            endpointIdx++;
            payloadIdx = 0;
            return tryNextCombination();
          } else {
            return resolve({ success: false, message: 'El Verifone Cardnet respondio con error HTTP ' + cardRes.statusCode });
          }
        });
      });

      cardReq.on('timeout', function() {
        cardReq.destroy();
        endpointIdx++;
        payloadIdx = 0;
        if (endpointIdx < endpoints.length) {
          tryNextCombination();
        } else {
          resolve({ success: false, message: 'Tiempo de espera agotado conectando con el Verifone (' + ip + ':' + targetPort + ').' });
        }
      });

      cardReq.on('error', function(err) {
        endpointIdx++;
        payloadIdx = 0;
        if (endpointIdx < endpoints.length) {
          tryNextCombination();
        } else {
          resolve({ success: false, message: 'No se pudo conectar a la IP ' + ip + ':' + targetPort + ' del Verifone (' + err.message + ').' });
        }
      });

      cardReq.write(postData);
      cardReq.end();
    }

    tryNextCombination();
  });
}

function handleAzulLocalCharge(amount, ip, port, timeoutSec) {
  return new Promise((resolve) => {
    const targetPort = port || 80;
    if (!ip) {
      return resolve({ success: false, message: 'IP del Bridge de Azul no configurada.' });
    }

    var azulUrl = 'http://' + ip + ':' + targetPort + '/azul/charge';
    const postData = JSON.stringify({ amount: parseFloat(amount), tax: 0.00 });

    var azulReq = http.request(azulUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      },
      timeout: timeoutSec * 1000
    }, function(azulRes) {
      var azulBody = '';
      azulRes.on('data', function(chunk) { azulBody += chunk; });
      azulRes.on('end', function() {
        if (azulRes.statusCode === 200) {
          try {
            var data = JSON.parse(azulBody);
            resolve({
              success: true,
              status: data.status || 'approved',
              auth_code: data.auth_code || '000000',
              card_number: data.card_number || '************0000',
              card_type: data.card_type || 'Tarjeta',
              message: data.message || 'Transacción Aprobada'
            });
          } catch (e) {
            resolve({ success: false, message: 'Error procesando respuesta de Azul.' });
          }
        } else {
          resolve({ success: false, message: 'El terminal Azul rechazó la transacción.' });
        }
      });
    });

    azulReq.on('timeout', function() {
      azulReq.destroy();
      resolve({ success: false, message: 'Tiempo de espera agotado conectando con Azul.' });
    });

    azulReq.on('error', function(err) {
      resolve({ success: false, message: 'Fallo de comunicacion con Azul: ' + err.message });
    });

    azulReq.write(postData);
    azulReq.end();
  });
}

function handleSilentPrint(pdfUrl, printerName) {
  return new Promise((resolve) => {
    try {
      const httpLib = pdfUrl.startsWith('https') ? https : http;
      var tempPath = path.join(os.tmpdir(), 'ticket_' + Date.now() + '.pdf');
      const file = fs.createWriteStream(tempPath);

      httpLib.get(pdfUrl, (res) => {
        res.pipe(file);
        file.on('finish', () => {
          file.close(() => {
            let cmd = '';
            if (process.platform === 'win32') {
              const programFilesX86 = process.env['ProgramFiles(x86)'] || 'C:\\Program Files (x86)';
              const programFiles = process.env['ProgramFiles'] || 'C:\\Program Files';
              const localAppData = process.env['LOCALAPPDATA'] || '';

              const edgeCandidates = [
                path.join(programFilesX86, 'Microsoft', 'Edge', 'Application', 'msedge.exe'),
                path.join(programFiles, 'Microsoft', 'Edge', 'Application', 'msedge.exe'),
                path.join(localAppData, 'Microsoft', 'Edge', 'Application', 'msedge.exe'),
                'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe'
              ];

              const chromeCandidates = [
                path.join(programFiles, 'Google', 'Chrome', 'Application', 'chrome.exe'),
                path.join(programFilesX86, 'Google', 'Chrome', 'Application', 'chrome.exe'),
                path.join(localAppData, 'Google', 'Chrome', 'Application', 'chrome.exe')
              ];

              let browserExe = edgeCandidates.find(p => p && fs.existsSync(p)) || chromeCandidates.find(p => p && fs.existsSync(p));

              if (browserExe) {
                var printFlag = printerName ? '--print-to-printer="' + printerName + '"' : '--print-to-default';
                cmd = '"' + browserExe + '" --headless ' + printFlag + ' "' + tempPath + '"';
              } else {
                var printFlag2 = printerName ? '--print-to-printer="' + printerName + '"' : '--print-to-default';
                cmd = 'cmd /c msedge --headless ' + printFlag2 + ' "' + tempPath + '"';
              }
            } else {
              if (printerName) {
                cmd = 'lp -d "' + printerName + '" "' + tempPath + '"';
              } else {
                cmd = 'lp "' + tempPath + '"';
              }
            }

            exec(cmd, (err, stdout, stderr) => {
              setTimeout(() => { try { fs.unlinkSync(tempPath); } catch(e){} }, 4000);

              if (err) {
                console.error('[BillsBridge Print] Error ejecutando comando de impresión:', err.message);
                resolve({ success: false, message: 'Fallo al imprimir en sistema: ' + err.message });
              } else {
                console.log('[BillsBridge Print] Ticket enviado silenciosamente a la impresora');
                resolve({ success: true, message: 'Ticket impreso silenciosamente.' });
              }
            });
          });
        });
      }).on('error', (err) => {
        resolve({ success: false, message: 'Error descargando PDF para impresión: ' + err.message });
      });
    } catch(e) {
      resolve({ success: false, message: 'Error procesando impresión silenciosa: ' + e.message });
    }
  });
}
