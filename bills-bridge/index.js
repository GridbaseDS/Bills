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
  const timestamp = new Date().toLocaleTimeString();
  const line = `[${timestamp}] ${msg}`;
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

const HTML_DASHBOARD = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gridbase BillsBridge Control Panel</title>
  <style>
    :root {
      --bg: #0b0f19;
      --card-bg: #151c2c;
      --border: #26334d;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --primary: #3b82f6;
      --success: #22c55e;
      --warning: #eab308;
      --danger: #ef4444;
    }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 24px; font-size: 14px; }
    .container { max-width: 960px; margin: 0 auto; }
    .header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 24px; }
    .title-group { display: flex; align-items: center; gap: 14px; }
    .logo { width: 42px; height: 42px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
    .badge { padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .badge-success { background: rgba(34,197,94,0.15); color: var(--success); border: 1px solid rgba(34,197,94,0.3); }
    .badge-warning { background: rgba(234,179,8,0.15); color: var(--warning); border: 1px solid rgba(234,179,8,0.3); }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
    .card-title { font-size: 16px; font-weight: 700; margin-top: 0; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; color: #fff; }
    .btn { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 10px 16px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; }
    .btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .btn-secondary { background: #26334d; color: var(--text); }
    .form-group { margin-bottom: 14px; }
    label { display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; font-weight: 600; }
    input, select { width: 100%; box-sizing: border-box; background: #0b0f19; border: 1px solid var(--border); color: var(--text); padding: 10px 12px; border-radius: 8px; font-size: 13px; outline: none; }
    input:focus, select:focus { border-color: var(--primary); }
    .terminal { background: #050811; border: 1px solid var(--border); border-radius: 12px; padding: 16px; font-family: "JetBrains Mono", Consolas, monospace; font-size: 12px; height: 280px; overflow-y: auto; color: #cbd5e1; }
    .log-line { margin-bottom: 6px; line-height: 1.5; word-break: break-all; }
    .log-error { color: #f87171; font-weight: 600; }
    .log-success { color: #4ade80; font-weight: 600; }
    .log-warn { color: #facc15; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="title-group">
        <div class="logo">B</div>
        <div>
          <h2 style="margin:0; font-size:20px;">Gridbase BillsBridge Dashboard</h2>
          <span style="font-size:13px; color:var(--text-muted);">Servicio de Comunicación POS & Impresión Silent v1.4.0 GUI Edition</span>
        </div>
      </div>
      <div id="status-badge" class="badge badge-warning">Consultando estado...</div>
    </div>

    <div class="grid">
      <div class="card">
        <div class="card-title">⚙️ Dominio y Vinculación</div>
        <div class="form-group">
          <label>Dominio Autorizado de Bills</label>
          <input type="text" id="input-domain" placeholder="bills.floristeriabraulio.com.do">
        </div>
        <button class="btn" onclick="saveDomain()">Guardar y Vincular Dominio</button>
      </div>

      <div class="card">
        <div class="card-title">🖨️ Impresoras del Sistema</div>
        <div class="form-group">
          <label>Impresora de Caja Detectada</label>
          <select id="select-printer"><option>Buscando impresoras del sistema...</option></select>
        </div>
        <button class="btn btn-secondary" onclick="loadPrinters()">Refrescar Impresoras</button>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        <span>📝 Consola de Debug en Vivo</span>
        <button class="btn btn-secondary" onclick="clearTerminal()" style="font-size:11px; padding:6px 12px;">Limpiar Pantalla</button>
      </div>
      <div class="terminal" id="terminal">
        <div class="log-line">[BillsBridge] Servidor iniciado correctamente en http://localhost:8080</div>
      </div>
    </div>
  </div>

  <script>
    async function updateStatus() {
      try {
        const res = await fetch('/status');
        const data = await res.json();
        const badge = document.getElementById('status-badge');
        if (data.linked) {
          badge.className = 'badge badge-success';
          badge.innerHTML = '✓ Vinculado: ' + data.allowed_domain;
          document.getElementById('input-domain').value = data.allowed_domain;
        } else {
          badge.className = 'badge badge-warning';
          badge.innerHTML = '⚠️ Sin Vincular';
        }
      } catch(e){}
    }

    async function loadPrinters() {
      try {
        const res = await fetch('/printers');
        const data = await res.json();
        const sel = document.getElementById('select-printer');
        if (data.success && data.printers && data.printers.length > 0) {
          sel.innerHTML = data.printers.map(function(p) { return '<option value="' + p + '">' + p + '</option>'; }).join('');
        } else {
          sel.innerHTML = '<option>No se detectaron impresoras instaladas</option>';
        }
      } catch(e){}
    }

    async function saveDomain() {
      const domain = document.getElementById('input-domain').value;
      if (!domain) return alert('Por favor ingresa un dominio válido');
      const res = await fetch('/configure', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ domain: domain })
      });
      const data = await res.json();
      alert(data.message);
      updateStatus();
    }

    function clearTerminal() {
      document.getElementById('terminal').innerHTML = '';
    }

    async function pollLogs() {
      try {
        const res = await fetch('/logs');
        const data = await res.json();
        if (data.success && data.logs) {
          const term = document.getElementById('terminal');
          term.innerHTML = data.logs.map(function(l) {
            let cls = 'log-line';
            if (l.indexOf('ERROR') !== -1 || l.indexOf('Error') !== -1 || l.indexOf('❌') !== -1) cls += ' log-error';
            if (l.indexOf('APROBADA') !== -1 || l.indexOf('✅') !== -1 || l.indexOf('✓') !== -1) cls += ' log-success';
            if (l.indexOf('⚠️') !== -1 || l.indexOf('WARN') !== -1) cls += ' log-warn';
            return '<div class="' + cls + '">' + l + '</div>';
          }).join('');
          term.scrollTop = term.scrollHeight;
        }
      } catch(e){}
    }

    updateStatus();
    loadPrinters();
    setInterval(pollLogs, 1500);
  </script>
</body>
</html>\`;

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

          const amountVal = parseFloat(amount || '1');
          const amountCents = Math.round(amountVal >= 100 ? amountVal : amountVal * 100);
          const targetUrl = `http://${ip}:${port}/tx_sale?amount=${amountCents}`;
          const payload = { amount: amountCents };
          if (merchant_id) payload.merchantId = merchant_id;
          if (terminal_id) payload.terminalId = terminal_id;
          const payloadData = JSON.stringify(payload);

          const req = http.request(targetUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Content-Length': Buffer.byteLength(payloadData)
            },
            timeout: 8000
          }, (posRes) => {
            let posBody = '';
            posRes.on('data', chunk => posBody += chunk);
            posRes.on('end', () => {
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

          req.on('timeout', () => {
            req.destroy();
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, error: 'TIMEOUT', message: `El dispositivo en ${ip}:${port} no respondió en 8 segundos.` }));
          });

          req.on('error', (err) => {
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, error: 'CONNECTION_FAILED', message: `Fallo de conexión a ${ip}:${port}: ${err.message}` }));
          });

          req.write(payloadData);
          req.end();
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
        let cmd = '';
        if (process.platform === 'win32') {
          cmd = `powershell -Command "Get-CimInstance -ClassName Win32_Printer | Select-Object -ExpandProperty Name"`;
        } else {
          cmd = `lpstat -e`;
        }

        exec(cmd, (err, stdout, stderr) => {
          if (err) {
            res.writeHead(500, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: 'Error listando impresoras: ' + err.message, printers: [] }));
            return;
          }

          const printers = stdout.split(/\r?\n/)
            .map(p => p.trim())
            .filter(p => p.length > 0);

          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: true, printers }));
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

          console.log(`[BillsBridge Print] Petición de impresión silenciosa enviada a: ${printer_name || 'Impresora Predeterminada'}`);
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
      console.error(`[!] El puerto ${PORT} está ocupado por otra instancia o aplicación.`);
      console.error(`[!] Reintentando en puerto alternativo 8081...`);
      setTimeout(() => {
        try { server.listen(8081, '0.0.0.0'); } catch(e) {}
      }, 1000);
    } else {
      console.error('[!] Error en el servidor HTTP:', err.message);
    }
  });

  server.listen(PORT, '0.0.0.0', () => {
    console.log(`==================================================`);
    console.log(` BillsBridge v1.4.0 (GUI Edition) - Iniciado en puerto ${PORT}`);
    if (allowedDomain === '*') {
      console.log(` [⚠️] ESTADO: Sin vincular.`);
      console.log(` Abre el panel de Bills y haz clic en "Vincular"`);
    } else {
      console.log(` [✓] ESTADO: Vinculado.`);
      console.log(` Dominio autorizado: https://${allowedDomain}`);
      // Iniciar el polling de nube al arrancar
      startCloudPolling();
    }
    console.log(`==================================================`);

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

  console.log(`[BillsBridge] Iniciando sondeo de nube en https://${allowedDomain} ...`);

  setInterval(async () => {
    if (isPollingActive) return;
    isPollingActive = true;

    try {
      const url = `https://${allowedDomain}/api/pos/bridge/poll`;
      const response = await makeGetRequest(url);
      
      if (response && response.success && response.pending && response.transaction) {
        const tx = response.transaction;
        console.log(`[BillsBridge] [Nube] Transacción recibida para Factura #${tx.invoice_id} (RD$ ${tx.amount})`);

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
            result = { success: false, message: `Driver '${tx.driver}' no soportado.` };
        }

        // Reportar respuesta a la nube
        console.log(`[BillsBridge] [Nube] Enviando resultado al servidor...`);
        const reportUrl = `https://${allowedDomain}/api/pos/bridge/respond`;
        await makePostRequest(reportUrl, {
          invoice_id: String(tx.invoice_id),
          status: result.success ? 'approved' : 'declined',
          auth_code: result.auth_code || '000000',
          card_number: result.card_number || '************0000',
          card_type: result.card_type || 'Tarjeta',
          message: result.message || (result.success ? 'Aprobada' : 'Declinada')
        });

        console.log(`[BillsBridge] [Nube] Resultado reportado con éxito.`);
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

    console.log(`[BillsBridge] Conectando a Cardnet por socket TCP en ${ip}:${targetPort}...`);
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
      resolve({ success: false, message: `Error de conexión física con Verifone: ${err.message}` });
    });

    function sendPayload() {
      state = 2;
      const amountStr = Math.round(parseFloat(amount) * 100).toString().padStart(12, '0');
      const taxStr = '000000000000';
      const otherTaxesStr = '000000000000';
      const ticketStr = (invoiceId || '000000').slice(-6).padStart(6, '0');

      const txMessage = `CN00${FS}${amountStr}${FS}${taxStr}${FS}${otherTaxesStr}${FS}${ticketStr}${FS}`;
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

    const endpoints = [
      `/tx_sale?amount=${amountVal}`,
      `/tx_sale?amount=${amountCents}`,
      `/tx_sale`
    ];

    let endpointIdx = 0;
    let payloadIdx = 0;
    let pollCount = 0;

    function tryNextCombination() {
      if (endpointIdx >= endpoints.length) {
        return resolve({
          success: false,
          message: `El Verifone CardNET no completó la transacción. Revisa la pantalla del Verifone.`
        });
      }

      const endpoint = endpoints[endpointIdx];
      const payloadObj = payloadVariants[payloadIdx];
      const postData = JSON.stringify(payloadObj);
      const url = `http://${ip}:${targetPort}${endpoint}`;

      console.log(`[BillsBridge CardNET] Enviando a Verifone (${url}): ${postData}`);

      const req = http.request(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(postData)
        },
        timeout: Math.min(timeoutSec, 60) * 1000
      }, (res) => {
        let body = '';
        res.on('data', chunk => { body += chunk; });
        res.on('end', () => {
          console.log(`[BillsBridge CardNET] HTTP ${res.statusCode} | Respuesta: "${body}"`);

          if (res.statusCode === 200) {
            try {
              const data = JSON.parse(body || '{}');
              const authCode = data.approbationNumber || data.authCode || data.auth_code || data.approvalCode || '';
              const txnMessage = data.txnMessage || data.resultMessage || data.message || data.error || 'Tarjeta Declinada / Error';
              const code = data.code;

              // 1. SI LA TRANSACCIÓN ESTÁ EN PROGRESO (esperando que el cliente pase la tarjeta en el Verifone)
              if (body.includes('Transacción en progreso') || (data.error && data.error.includes('progreso'))) {
                pollCount++;
                console.log(`[BillsBridge CardNET] ⏳ Transacción activa en la pantalla del Verifone (intento #${pollCount}). Esperando 3s a que pasen la tarjeta...`);
                if (pollCount < 20) {
                  setTimeout(() => tryNextCombination(), 3000);
                } else {
                  resolve({ success: false, message: 'Tiempo de espera agotado al pasar la tarjeta en el Verifone.' });
                }
                return;
              }

              // 2. SI EL MONTO FUE RECHAZADO POR RANGO O FORMATO (ej: 280000 excedió límite)
              if (code === -1 && (body.includes('formato') || body.includes('mayor') || body.includes('menor'))) {
                console.warn(`[BillsBridge CardNET] Formato rechazado (${body}). Probando variante siguiente...`);
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
                console.log(`[BillsBridge CardNET] ✅ APROBADA! Auth: ${authCode}`);
                return resolve({
                  success: true,
                  status: 'approved',
                  auth_code: String(authCode),
                  card_number: maskedPan,
                  card_type: cardSubType,
                  message: txnMessage
                });
              } else {
                console.warn(`[BillsBridge CardNET] ❌ DECLINADA / RECHAZADA: ${txnMessage}`);
                return resolve({ success: false, message: `CardNET: ${txnMessage}` });
              }
            } catch (e) {
              console.error(`[BillsBridge CardNET] Error parseando JSON:`, e.message);
              return resolve({ success: false, message: 'Error procesando respuesta del POS CardNET.' });
            }
          } else if (res.statusCode === 404) {
            endpointIdx++;
            payloadIdx = 0;
            return tryNextCombination();
          } else {
            return resolve({ success: false, message: `El Verifone Cardnet respondió con error HTTP ${res.statusCode}` });
          }
        });
      });

      req.on('timeout', () => {
        req.destroy();
        endpointIdx++;
        payloadIdx = 0;
        if (endpointIdx < endpoints.length) {
          tryNextCombination();
        } else {
          resolve({ success: false, message: `Tiempo de espera agotado conectando con el Verifone (${ip}:${targetPort}).` });
        }
      });

      req.on('error', (err) => {
        endpointIdx++;
        payloadIdx = 0;
        if (endpointIdx < endpoints.length) {
          tryNextCombination();
        } else {
          resolve({ success: false, message: `No se pudo conectar a la IP ${ip}:${targetPort} del Verifone (${err.message}).` });
        }
      });

      req.write(postData);
      req.end();
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

    const url = `http://${ip}:${targetPort}/azul/charge`;
    const postData = JSON.stringify({ amount: parseFloat(amount), tax: 0.00 });

    const req = http.request(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      },
      timeout: timeoutSec * 1000
    }, (res) => {
      let body = '';
      res.on('data', chunk => { body += chunk; });
      res.on('end', () => {
        if (res.statusCode === 200) {
          try {
            const data = JSON.parse(body);
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

    req.on('timeout', () => {
      req.destroy();
      resolve({ success: false, message: 'Tiempo de espera agotado conectando con Azul.' });
    });

    req.on('error', (err) => {
      resolve({ success: false, message: `Fallo de comunicación con Azul: ${err.message}` });
    });

    req.write(postData);
    req.end();
  });
}

function handleSilentPrint(pdfUrl, printerName) {
  return new Promise((resolve) => {
    try {
      const httpLib = pdfUrl.startsWith('https') ? https : http;
      const tempPath = path.join(os.tmpdir(), `ticket_${Date.now()}.pdf`);
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
                const printFlag = printerName ? `--print-to-printer="${printerName}"` : '--print-to-default';
                cmd = `"${browserExe}" --headless ${printFlag} "${tempPath}"`;
              } else {
                const printFlag = printerName ? `--print-to-printer="${printerName}"` : '--print-to-default';
                cmd = `cmd /c msedge --headless ${printFlag} "${tempPath}"`;
              }
            } else {
              if (printerName) {
                cmd = `lp -d "${printerName}" "${tempPath}"`;
              } else {
                cmd = `lp "${tempPath}"`;
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
