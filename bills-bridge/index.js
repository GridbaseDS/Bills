const http = require('http');
const https = require('https');
const net = require('net');
const { URL } = require('url');
const path = require('path');
const fs = require('fs');
const os = require('os');
const { exec } = require('child_process');

const PORT = 8080;

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
    const { execSync } = require('child_process');
    const exePath = isPackaged ? process.execPath : path.join(__dirname, 'index.js');
    const workingDir = exeDir;
    const taskName = "BillsBridge";

    // Comando PowerShell usando comillas simples para soportar rutas con espacios
    let psCommand;
    if (isPackaged) {
      psCommand = `Register-ScheduledTask -TaskName '${taskName}' -Trigger (New-ScheduledTaskTrigger -AtStartup) -Action (New-ScheduledTaskAction -Execute '${exePath}' -WorkingDirectory '${workingDir}') -Settings (New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries) -User 'SYSTEM' -Force`;
    } else {
      const nodeExe = process.execPath;
      psCommand = `Register-ScheduledTask -TaskName '${taskName}' -Trigger (New-ScheduledTaskTrigger -AtStartup) -Action (New-ScheduledTaskAction -Execute '${nodeExe}' -Argument '${exePath}' -WorkingDirectory '${workingDir}') -Settings (New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries) -User 'SYSTEM' -Force`;
    }

    execSync(`powershell -NoProfile -ExecutionPolicy Bypass -Command "${psCommand}"`, { stdio: 'pipe' });
    console.log('✅ ¡BillsBridge registrado en el Programador de Tareas de Windows (arrancará al iniciar la PC)!');
  } catch (err) {
    console.log(`\n[⚠️] Nota: No se pudo registrar la tarea automáticamente en Windows.`);
    if (err.stderr) {
      console.log(`Detalle del error:\n${err.stderr.toString().trim()}`);
    } else {
      console.log(`Detalle del error: ${err.message}`);
    }
    console.log(`--------------------------------------------------`);
  }
}

// ─────────────────────────────────────────────────────────────
// SERVIDOR HTTP CON CONTROL CORS DINÁMICO
// ─────────────────────────────────────────────────────────────
function startServer() {
  const server = http.createServer((req, res) => {
    const origin = req.headers.origin;

    // CORS dinámico según configuración
    if (origin) {
      try {
        const originUrl = new URL(origin);
        if (allowedDomain === '*' || originUrl.hostname === allowedDomain) {
          res.setHeader('Access-Control-Allow-Origin', origin);
        } else {
          console.warn(`[BillsBridge] Origen bloqueado: ${origin}`);
        }
      } catch (e) {
        // Formato de origen inválido
      }
    } else if (allowedDomain === '*') {
      res.setHeader('Access-Control-Allow-Origin', '*');
    }

    res.setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
      res.writeHead(204);
      res.end();
      return;
    }

    const parsedUrl = new URL(req.url, `http://${req.headers.host}`);

    // Endpoint de diagnóstico
    if (parsedUrl.pathname === '/status' && req.method === 'GET') {
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({
        success: true,
        service: 'BillsBridge',
        status: 'running',
        version: '1.2.0',
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
          const config = { domain };
          try {
            fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
          } catch (fsErr) {
            console.warn('[!] Advertencia: No se pudo escribir config.json en disco, pero se mantendrá en memoria:', fsErr.message);
          }
          currentConfig = config;
          allowedDomain = domain;

          console.log(`==================================================`);
          console.log(`[BillsBridge] VINCULACIÓN EXITOSA`);
          console.log(`Dominio autorizado: https://${domain}`);
          console.log(`==================================================`);

          // Intentar registrar el servicio de Windows
          installScheduledTask();

          // Activar el sondeo del servidor en la nube inmediatamente
          startCloudPolling();

          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({ success: true, message: `Bridge vinculado a ${domain} con éxito.` }));

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

          console.log(`[BillsBridge] Iniciando cobro: ${amount} via ${driver} (Factura #${invoice_id})`);

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
              res.end(JSON.stringify({ success: false, message: `Driver '${driver}' no soportado.` }));
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

  server.listen(PORT, '0.0.0.0', () => {
    console.log(`==================================================`);
    console.log(` BillsBridge v1.2.0 - Iniciado en puerto ${PORT}`);
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
  return new Promise((resolve, reject) => {
    const req = https.get(urlStr, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve(JSON.parse(data));
        } catch (e) {
          reject(new Error('Formato JSON inválido.'));
        }
      });
    });
    req.on('error', reject);
  });
}

function makePostRequest(urlStr, body) {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(urlStr);
    const postData = JSON.stringify(body);

    const options = {
      hostname: urlObj.hostname,
      port: 443,
      path: urlObj.pathname,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      }
    };

    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve(JSON.parse(data));
        } catch (e) {
          reject(new Error('Formato JSON inválido.'));
        }
      });
    });
    req.on('error', reject);
    req.write(postData);
    req.end();
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
  return new Promise((resolve) => {
    const targetPort = port || 2001;
    if (!ip) {
      return resolve({ success: false, message: 'IP del terminal Android no configurada.' });
    }

    const amountVal = Math.round(parseFloat(amount) * 100) / 100;
    const amountCents = Math.round(amountVal * 100);

    // En CardNET Android SmartPOS REST, el monto se envía en Pesos (ej: 2800) o Centavos (ej: 280000).
    const payloadVariants = [
      { amount: amountVal },
      { amount: amountVal.toFixed(2) },
      { amount: amountCents }
    ];

    if (merchantId) payloadVariants.forEach(p => p.merchantId = merchantId);
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
              if (printerName) {
                cmd = `powershell -Command "Start-Process -FilePath '${tempPath}' -Verb PrintTo -ArgumentList '${printerName}'"`;
              } else {
                cmd = `powershell -Command "Start-Process -FilePath '${tempPath}' -Verb Print"`;
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
