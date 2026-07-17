const http = require('http');
const net = require('net');
const { URL } = require('url');
const path = require('path');
const fs = require('fs');

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
        version: '1.0.0',
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

          // Guardar configuración
          const config = { domain };
          fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
          currentConfig = config;
          allowedDomain = domain;

          console.log(`==================================================`);
          console.log(`[BillsBridge] VINCULACIÓN EXITOSA`);
          console.log(`Dominio autorizado: https://${domain}`);
          console.log(`==================================================`);

          // Intentar registrar el servicio de Windows
          installScheduledTask();

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
          const { driver, amount, ip, port, invoice_id, timeout = 60 } = params;

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
              result = await handleCardnetAndroidCharge(amount, ip, port, timeout);
              break;
            case 'azul_local':
              result = await handleAzulLocalCharge(amount, ip, port, timeout);
              break;
            default:
              res.writeHead(400, { 'Content-Type': 'application/json' });
              res.end(JSON.stringify({ success: false, message: `Driver '${driver}' no soportado.` }));
              return;
          }

          const statusCode = result.success ? 200 : 402;
          res.writeHead(statusCode, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify(result));

        } catch (err) {
          console.error('[BillsBridge] Error en transacción:', err);
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
    console.log(` BillsBridge v1.1.0 - Iniciado en puerto ${PORT}`);
    if (allowedDomain === '*') {
      console.log(` [⚠️] ESTADO: Sin vincular.`);
      console.log(` Abre el panel de Bills y haz clic en "Vincular"`);
    } else {
      console.log(` [✓] ESTADO: Vinculado.`);
      console.log(` Dominio autorizado: https://${allowedDomain}`);
    }
    console.log(`==================================================`);
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

function handleCardnetAndroidCharge(amount, ip, port, timeoutSec) {
  return new Promise((resolve) => {
    const targetPort = port || 2001;
    if (!ip) {
      return resolve({ success: false, message: 'IP del terminal Android no configurada.' });
    }

    const amountCents = Math.round(parseFloat(amount) * 100);
    const url = `http://${ip}:${targetPort}/tx_sale?amount=${amountCents}`;

    const req = http.request(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      timeout: timeoutSec * 1000
    }, (res) => {
      let body = '';
      res.on('data', chunk => { body += chunk; });
      res.on('end', () => {
        if (res.statusCode === 200) {
          try {
            const data = JSON.parse(body);
            const authCode = data.approbationNumber || '';
            const txnMessage = data.txnMessage || 'Tarjeta Declinada / Error';
            const cardInfo = data.cardInformation || {};
            const maskedPan = cardInfo.maskedPAN || '************0000';
            const cardSubType = cardInfo.cardSubType || 'Tarjeta';

            if (authCode && authCode !== '000000') {
              resolve({
                success: true,
                status: 'approved',
                auth_code: authCode,
                card_number: maskedPan,
                card_type: cardSubType,
                message: txnMessage
              });
            } else {
              resolve({ success: false, message: txnMessage });
            }
          } catch (e) {
            resolve({ success: false, message: 'Error procesando respuesta del POS Android.' });
          }
        } else {
          resolve({ success: false, message: `El terminal respondió con error HTTP ${res.statusCode}` });
        }
      });
    });

    req.on('timeout', () => {
      req.destroy();
      resolve({ success: false, message: 'Tiempo de espera agotado conectando con Verifone Android.' });
    });

    req.on('error', (err) => {
      resolve({ success: false, message: `Fallo de comunicación con Verifone Android: ${err.message}` });
    });

    req.write(JSON.stringify({ amount: amountCents }));
    req.end();
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
