// ─────────────────────────────────────────────────────────────
// BILLSBRIDGE v1.5.0 - ARRANQUE SEGURO (ES5 PURO PARA PKG)
// ─────────────────────────────────────────────────────────────
var http = require('http');
var https = require('https');
var net = require('net');
var url = require('url');
var path = require('path');
var fs = require('fs');
var os = require('os');
var child_process = require('child_process');
var exec = child_process.exec;
var URL = url.URL;

// Directorio del ejecutable
var isPackaged = (typeof process.pkg !== 'undefined');
var exeDir = isPackaged ? path.dirname(process.execPath) : __dirname;
var logFilePath = path.join(exeDir, 'billsbridge-debug.log');

// Función para escribir al archivo de log (diagnóstico de crashes)
function writeLogFile(msg) {
  try {
    var ts = new Date().toISOString();
    fs.appendFileSync(logFilePath, '[' + ts + '] ' + msg + '\n');
  } catch(e) {}
}

writeLogFile('=== BillsBridge v1.5.0 INICIANDO ===');
writeLogFile('Platform: ' + process.platform + ' | Arch: ' + process.arch);
writeLogFile('ExeDir: ' + exeDir);
writeLogFile('Node version: ' + process.version);
writeLogFile('isPackaged: ' + isPackaged);

// Captura global de errores
process.on('uncaughtException', function(err) {
  writeLogFile('UNCAUGHT EXCEPTION: ' + (err && err.stack ? err.stack : String(err)));
  console.error('[!] Excepcion no capturada en BillsBridge:', err.message);
});

process.on('unhandledRejection', function(reason) {
  writeLogFile('UNHANDLED REJECTION: ' + String(reason));
  console.error('[!] Promesa rechazada no capturada:', reason);
});

// Log de salida del proceso
process.on('exit', function(code) {
  writeLogFile('PROCESS EXIT con codigo: ' + code);
});

// Mantener el proceso vivo con keepalive
setInterval(function() {}, 30000);

// Mantener stdin abierto si es posible
try {
  if (process.stdin && process.stdin.resume) {
    process.stdin.resume();
  }
} catch(e) {
  writeLogFile('stdin.resume fallo: ' + e.message);
}

writeLogFile('Fase 1 completa: modulos cargados, handlers registrados');

// ─────────────────────────────────────────────────────────────
// CAPTURA DE LOGS EN MEMORIA PARA LA CONSOLA WEB EN VIVO
// ─────────────────────────────────────────────────────────────
var logsMemory = [];
function addLog(msg) {
  var timestamp = new Date().toLocaleTimeString();
  var line = '[' + timestamp + '] ' + msg;
  logsMemory.push(line);
  if (logsMemory.length > 200) logsMemory.shift();
}

var origLog = console.log;
var origErr = console.error;
var origWarn = console.warn;

console.log = function() {
  var args = Array.prototype.slice.call(arguments);
  origLog.apply(console, args);
  addLog(args.join(' '));
};

console.error = function() {
  var args = Array.prototype.slice.call(arguments);
  origErr.apply(console, args);
  addLog('ERROR: ' + args.join(' '));
  writeLogFile('CONSOLE.ERROR: ' + args.join(' '));
};

console.warn = function() {
  var args = Array.prototype.slice.call(arguments);
  origWarn.apply(console, args);
  addLog('WARN: ' + args.join(' '));
};

writeLogFile('Fase 2 completa: logging interceptado');

var PORT = 8080;

// Cargar HTML del dashboard desde archivo externo
var HTML_DASHBOARD = '';
try {
  var dashboardPath = path.join(__dirname, 'dashboard.html');
  writeLogFile('Cargando dashboard desde: ' + dashboardPath);
  HTML_DASHBOARD = fs.readFileSync(dashboardPath, 'utf8');
  writeLogFile('Dashboard cargado OK (' + HTML_DASHBOARD.length + ' bytes)');
} catch (e) {
  writeLogFile('Dashboard NO encontrado, usando fallback: ' + e.message);
  HTML_DASHBOARD = '<html><body style="background:#111;color:#fff;font-family:sans-serif;padding:40px;"><h1>BillsBridge v1.5.0</h1><p>Dashboard file not found. Service is running on port ' + PORT + '.</p><p>Error: ' + (e && e.message || 'unknown') + '</p></body></html>';
}

// Caracteres especiales del protocolo Cardnet (SPDH/Sockets)
var SYN = 0x16;
var EOM = 0x19;
var ENQ = 0x05;
var ACK = 0x06;
var STX = 0x02;
var ETX = 0x03;
var FS = '\x1C'; // File Separator (0x1C)

// Directorio del ejecutable y config
var configPath = path.join(exeDir, 'config.json');

var currentConfig = null;
var allowedDomain = '*';
var isPollingStarted = false;

// Cargar configuración inicial si existe
if (fs.existsSync(configPath)) {
  try {
    var raw = fs.readFileSync(configPath, 'utf8');
    currentConfig = JSON.parse(raw);
    allowedDomain = currentConfig.domain || '*';
    writeLogFile('Config cargada: dominio=' + allowedDomain);
  } catch (err) {
    writeLogFile('Error leyendo config.json: ' + err.message);
    console.error('[!] Error leyendo config.json:', err.message);
  }
} else {
  writeLogFile('config.json no existe aun (normal en primera ejecucion)');
}

writeLogFile('Fase 3 completa: config cargada, iniciando servidor HTTP...');

// Iniciar servidor HTTP
try {
  startServer();
  writeLogFile('startServer() invocado OK');
} catch(e) {
  writeLogFile('ERROR CRITICO en startServer(): ' + (e && e.stack ? e.stack : String(e)));
}

// ─────────────────────────────────────────────────────────────
// FUNCIÓN PARA INSTALAR EL SERVICIO EN SEGUNDO PLANO
// ─────────────────────────────────────────────────────────────
function installScheduledTask() {
  if (process.platform !== 'win32') return;
  console.log('[BillsBridge] Intentando registrar servicio de Windows en segundo plano...');
  try {
    var exePath = isPackaged ? process.execPath : path.join(__dirname, 'index.js');
    var workingDir = exeDir;
    var taskName = "BillsBridge";

    var psCommand;
    if (isPackaged) {
      psCommand = 'Register-ScheduledTask -TaskName \'' + taskName + '\' -Trigger (New-ScheduledTaskTrigger -AtStartup) -Action (New-ScheduledTaskAction -Execute \'' + exePath + '\' -WorkingDirectory \'' + workingDir + '\') -Settings (New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries) -Force';
    } else {
      var nodeExe = process.execPath;
      psCommand = 'Register-ScheduledTask -TaskName \'' + taskName + '\' -Trigger (New-ScheduledTaskTrigger -AtStartup) -Action (New-ScheduledTaskAction -Execute \'' + nodeExe + '\' -Argument \'' + exePath + '\' -WorkingDirectory \'' + workingDir + '\') -Settings (New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries) -Force';
    }

    exec('powershell -NoProfile -ExecutionPolicy Bypass -Command "' + psCommand + '"', function() {});
  } catch (err) {}
}

// ─────────────────────────────────────────────────────────────
// SERVIDOR HTTP CON CONTROL CORS DINÁMICO
// ─────────────────────────────────────────────────────────────
function startServer() {
  var server = http.createServer(function(req, res) {
    var origin = req.headers.origin;

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
      res.writeHead(204, {
        'Access-Control-Allow-Origin': origin || '*',
        'Access-Control-Allow-Methods': 'GET, POST, OPTIONS, PUT, DELETE',
        'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With',
        'Access-Control-Allow-Credentials': 'true',
        'Access-Control-Max-Age': '86400'
      });
      res.end();
      return;
    }

    var parsedUrl = new URL(req.url, 'http://' + (req.headers.host || 'localhost:8080'));

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
        version: '1.5.0',
        linked: allowedDomain !== '*',
        allowed_domain: allowedDomain
      }));
      return;
    }

    // Endpoint de vinculación web (Configuración)
    if (parsedUrl.pathname === '/configure' && req.method === 'POST') {
      var body = '';
      req.on('data', function(chunk) { body += chunk; });
      req.on('end', function() {
        try {
          // Si ya está configurado y el origen de la petición no coincide, denegar para seguridad
          if (allowedDomain !== '*' && origin && new URL(origin).hostname !== allowedDomain) {
            res.writeHead(403, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'El Bridge ya está vinculado a otro dominio.' }));
            return;
          }

          var params = JSON.parse(body);
          var domain = params.domain ? params.domain.trim() : '';
          domain = domain.replace(/^(https?:\/\/)?(www\.)?/, '').replace(/\/$/, '');

          if (!domain) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Dominio inválido.' }));
            return;
          }

          // Guardar configuración (en disco y memoria)
          var config = { domain: domain };
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

      var body = '';
      req.on('data', function(chunk) { body += chunk; });
      req.on('end', async function() {
        try {
          var params = JSON.parse(body);
          var { driver, amount, ip, port, merchant_id, terminal_id, invoice_id, timeout = 60 } = params;

          console.log('[BillsBridge] Iniciando cobro: ' + amount + ' via ' + driver + ' (Factura #' + invoice_id + ')');

          if (!driver || !amount) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Faltan parámetros: driver o amount.' }));
            return;
          }

          var result;
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
      var body = '';
      req.on('data', function(chunk) { body += chunk; });
      req.on('end', async function() {
        try {
          var params = JSON.parse(body);
          var { ip, port = 2001, amount = 0.01, merchant_id, terminal_id } = params;

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
      var body = '';
      req.on('data', function(chunk) { body += chunk; });
      req.on('end', async function() {
        try {
          var params = JSON.parse(body);
          var { pdf_url, printer_name } = params;

          if (!pdf_url) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Falta parámetro pdf_url.' }));
            return;
          }

          console.log('[BillsBridge Print] Petición de impresión silenciosa enviada a: ' + (printer_name || 'Impresora Predeterminada'));
          var result = await handleSilentPrint(pdf_url, printer_name);

          var statusCode = result.success ? 200 : 500;
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

  server.on('error', function(err) {
    if (err.code === 'EADDRINUSE') {
      console.error('[!] El puerto ' + PORT + ' está ocupado por otra instancia o aplicación.');
      console.error('[!] Reintentando en puerto alternativo 8081...');
      setTimeout(function() {
        try { server.listen(8081, '0.0.0.0'); } catch(e) {}
      }, 1000);
    } else {
      console.error('[!] Error en el servidor HTTP:', err.message);
    }
  });

  server.listen(PORT, '0.0.0.0', function() {
    console.log('==================================================');
    console.log(' BillsBridge v1.5.0 (GUI Edition) - Iniciado en puerto ' + PORT);
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
var isPollingActive = false;

function startCloudPolling() {
  if (allowedDomain === '*' || isPollingStarted) return;
  isPollingStarted = true;

  console.log('[BillsBridge] Iniciando sondeo de nube en https://' + allowedDomain + ' ...');

  setInterval(async function() {
    if (isPollingActive) return;
    isPollingActive = true;

    try {
      var pollUrl = 'https://' + allowedDomain + '/api/pos/bridge/poll';
      var response = await makeGetRequest(pollUrl);
      
      if (response && response.success && response.pending && response.transaction) {
        var tx = response.transaction;
        console.log('[BillsBridge] [Nube] Transacción recibida para Factura #' + tx.invoice_id + ' (RD$ ' + tx.amount + ')');

        var result;
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
  return new Promise(function(resolve) {
    try {
      var u = new URL(urlStr);
      var httpLib = u.protocol === 'https:' ? https : http;
      var req = httpLib.get(urlStr, function(res) {
        var data = '';
        res.on('data', function(chunk) { data += chunk; });
        res.on('end', function() {
          try {
            resolve(JSON.parse(data));
          } catch (e) {
            resolve({ success: false, message: 'JSON inválido' });
          }
        });
      });
      req.on('error', function(err) { resolve({ success: false, message: err.message }); });
      req.setTimeout(8000, function() { try { req.destroy(); } catch(e){} resolve({ success: false, message: 'Timeout' }); });
    } catch(e) {
      resolve({ success: false, message: e.message });
    }
  });
}

function makePostRequest(urlStr, body) {
  return new Promise(function(resolve) {
    try {
      var u = new URL(urlStr);
      var httpLib = u.protocol === 'https:' ? https : http;
      var postData = typeof body === 'string' ? body : JSON.stringify(body);

      var options = {
        hostname: u.hostname,
        port: u.port || (u.protocol === 'https:' ? 443 : 80),
        path: u.pathname + u.search,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(postData)
        }
      };

      var req = httpLib.request(options, function(res) {
        var data = '';
        res.on('data', function(chunk) { data += chunk; });
        res.on('end', function() {
          try {
            resolve(JSON.parse(data));
          } catch (e) {
            resolve({ success: false, message: 'JSON inválido' });
          }
        });
      });
      req.on('error', function(err) { resolve({ success: false, message: err.message }); });
      req.setTimeout(8000, function() { try { req.destroy(); } catch(e){} resolve({ success: false, message: 'Timeout' }); });
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
  return new Promise(function(resolve) {
    console.log('[BillsBridge] Procesando cobro SIMULADO...');
    setTimeout(function() {
      if (parseFloat(amount) === 99.99) {
        resolve({
          success: false,
          message: 'Transacción Declinada: Fondos Insuficientes (Simulado)'
        });
      } else {
        var randAuth = Math.floor(100000 + Math.random() * 900000).toString();
        var randCard = '411111******' + Math.floor(1000 + Math.random() * 9000).toString();
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
  return new Promise(function(resolve) {
    var targetPort = port || 7060;
    if (!ip) {
      return resolve({ success: false, message: 'IP del terminal no configurada.' });
    }

    console.log('[BillsBridge] Conectando a Cardnet por socket TCP en ' + ip + ':' + targetPort + '...');
    var socket = new net.Socket();
    var state = 0;
    var responseBuffer = Buffer.alloc(0);
    var timeoutTimer = null;

    var cleanUp = function() {
      if (timeoutTimer) clearTimeout(timeoutTimer);
      socket.destroy();
    };

    timeoutTimer = setTimeout(function() {
      console.log('[BillsBridge] Timeout superado en la conexión TCP.');
      cleanUp();
      resolve({ success: false, message: 'Tiempo de espera agotado en el Verifone.' });
    }, timeoutSec * 1000);

    socket.connect(targetPort, ip, function() {
      console.log('[BillsBridge] Socket conectado. Iniciando handshake (enviando SYN)...');
      socket.write(Buffer.from([SYN]));
    });

    socket.on('data', function(chunk) {
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
        var etxIndex = responseBuffer.indexOf(ETX);
        if (etxIndex !== -1) {
          var stxIndex = responseBuffer.indexOf(STX);
          var dataBuffer;
          if (stxIndex !== -1 && stxIndex < etxIndex) {
            dataBuffer = responseBuffer.subarray(stxIndex + 1, etxIndex);
          } else {
            dataBuffer = responseBuffer.subarray(0, etxIndex);
          }

          var responseText = dataBuffer.toString('ascii');
          cleanUp();

          var fields = responseText.split(FS);
          var authCode = fields[8] ? fields[8].trim() : '';
          var cardNo = fields[3] ? fields[3].trim() : '************0000';
          var cardType = fields[1] ? fields[1].trim() : 'Tarjeta';

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

          var errText = 'Transacción declinada por el Verifone.';
          for (var fi = 0; fi < fields.length; fi++) {
            var f = fields[fi];
            if (f.indexOf('DECLINADA') !== -1 || f.indexOf('FONDOS INSUF') !== -1 || f.indexOf('PIN INVALIDO') !== -1 || f.indexOf('ERROR') !== -1) {
              errText = f.trim();
              break;
            }
          }
          resolve({ success: false, message: errText });
        }
      }
    });

    socket.on('error', function(err) {
      console.error('[BillsBridge] Error en socket:', err.message);
      cleanUp();
      resolve({ success: false, message: 'Error de conexion fisica con Verifone: ' + err.message });
    });

    function sendPayload() {
      state = 2;
      var amountStr = Math.round(parseFloat(amount) * 100).toString().padStart(12, '0');
      var taxStr = '000000000000';
      var otherTaxesStr = '000000000000';
      var ticketStr = (invoiceId || '000000').slice(-6).padStart(6, '0');

      var txMessage = 'CN00' + FS + amountStr + FS + taxStr + FS + otherTaxesStr + FS + ticketStr + FS;
      socket.write(Buffer.from(txMessage, 'ascii'));
    }
  });
}

function handleCardnetAndroidCharge(amount, ip, port, merchantId, terminalId, invoiceId, timeoutSec) {
  return new Promise(function(resolve) {
    // Guard: si se pasó timeout (ej: 90 o 60) como 4to argumento por firma legacy
    var actualMerchantId = merchantId;
    if (typeof actualMerchantId === 'number' && (timeoutSec === undefined || actualMerchantId <= 300)) {
      if (!timeoutSec) timeoutSec = actualMerchantId;
      actualMerchantId = null;
    }

    var targetPort = port || 2001;
    var amountVal = Math.round(parseFloat(amount) * 100) / 100;
    var amountCents = Math.round(amountVal * 100);

    // En CardNET Android SmartPOS REST, el monto se envía en Pesos (ej: 2800) o Centavos (ej: 280000).
    var payloadVariants = [
      { amount: amountVal },
      { amount: amountVal.toFixed(2) },
      { amount: amountCents }
    ];

    if (actualMerchantId) payloadVariants.forEach(function(p) { p.merchantId = actualMerchantId; });
    if (terminalId) payloadVariants.forEach(function(p) { p.terminalId = terminalId; });

    var endpoints = [
      '/tx_sale?amount=' + amountVal,
      '/tx_sale?amount=' + amountCents,
      '/tx_sale'
    ];

    var endpointIdx = 0;
    var payloadIdx = 0;
    var pollCount = 0;

    function tryNextCombination() {
      if (endpointIdx >= endpoints.length) {
        return resolve({
          success: false,
          message: 'El Verifone CardNET no completo la transaccion. Revisa la pantalla del Verifone.'
        });
      }

      var endpoint = endpoints[endpointIdx];
      var payloadObj = payloadVariants[payloadIdx];
      var postData = JSON.stringify(payloadObj);
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
              var authCode = data.approbationNumber || data.authCode || data.auth_code || data.approvalCode || '';
              var txnMessage = data.txnMessage || data.resultMessage || data.message || data.error || 'Tarjeta Declinada / Error';
              var code = data.code;

              // 1. SI LA TRANSACCIÓN ESTÁ EN PROGRESO (esperando que el cliente pase la tarjeta en el Verifone)
              if (respBody.indexOf('Transaccion en progreso') !== -1 || respBody.indexOf('Transacción en progreso') !== -1 || (data.error && data.error.indexOf('progreso') !== -1)) {
                pollCount++;
                console.log('[BillsBridge CardNET] Transaccion activa en pantalla del Verifone (intento #' + pollCount + '). Esperando 3s...');
                if (pollCount < 20) {
                  setTimeout(function() { tryNextCombination(); }, 3000);
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

              var cardInfo = data.cardInformation || data.ticket || data.cardInfo || {};
              var maskedPan = cardInfo.maskedPAN || cardInfo.CardNumber || cardInfo.cardNumber || '************0000';
              var cardSubType = cardInfo.cardSubType || cardInfo.CardType || cardInfo.cardType || 'Tarjeta';

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
  return new Promise(function(resolve) {
    var targetPort = port || 80;
    if (!ip) {
      return resolve({ success: false, message: 'IP del Bridge de Azul no configurada.' });
    }

    var azulUrl = 'http://' + ip + ':' + targetPort + '/azul/charge';
    var postData = JSON.stringify({ amount: parseFloat(amount), tax: 0.00 });

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
  return new Promise(function(resolve) {
    try {
      var httpLib = pdfUrl.startsWith('https') ? https : http;
      var tempPath = path.join(os.tmpdir(), 'ticket_' + Date.now() + '.pdf');
      var file = fs.createWriteStream(tempPath);

      httpLib.get(pdfUrl, function(res) {
        res.pipe(file);
        file.on('finish', function() {
          file.close(function() {
            var cmd = '';
            if (process.platform === 'win32') {
              var programFilesX86 = process.env['ProgramFiles(x86)'] || 'C:\\Program Files (x86)';
              var programFiles = process.env['ProgramFiles'] || 'C:\\Program Files';
              var localAppData = process.env['LOCALAPPDATA'] || '';

              var edgeCandidates = [
                path.join(programFilesX86, 'Microsoft', 'Edge', 'Application', 'msedge.exe'),
                path.join(programFiles, 'Microsoft', 'Edge', 'Application', 'msedge.exe'),
                path.join(localAppData, 'Microsoft', 'Edge', 'Application', 'msedge.exe'),
                'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe'
              ];

              var chromeCandidates = [
                path.join(programFiles, 'Google', 'Chrome', 'Application', 'chrome.exe'),
                path.join(programFilesX86, 'Google', 'Chrome', 'Application', 'chrome.exe'),
                path.join(localAppData, 'Google', 'Chrome', 'Application', 'chrome.exe')
              ];

              var browserExe = edgeCandidates.find(function(p) { return p && fs.existsSync(p); }) || chromeCandidates.find(function(p) { return p && fs.existsSync(p); });

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

            exec(cmd, function(err, stdout, stderr) {
              setTimeout(function() { try { fs.unlinkSync(tempPath); } catch(e){} }, 4000);

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
      }).on('error', function(err) {
        resolve({ success: false, message: 'Error descargando PDF para impresión: ' + err.message });
      });
    } catch(e) {
      resolve({ success: false, message: 'Error procesando impresión silenciosa: ' + e.message });
    }
  });
}
