/**
 * Wangari Desktop App — Electron Main Process
 * Handles: window lifecycle, PHP server spawn, license validation, hardware fingerprinting
 */

'use strict';

const { app, BrowserWindow, ipcMain, dialog, shell, Menu } = require('electron');
const path   = require('path');
const fs     = require('fs');
const crypto = require('crypto');
const http   = require('http');
const https  = require('https');
const { spawn, execSync } = require('child_process');
const os     = require('os');

// ─────────────────────────────────────────────────────────────────────────────
// CONSTANTS
// ─────────────────────────────────────────────────────────────────────────────
const APP_VERSION    = '1.0.0';
const PHP_PORT       = 18432; // obscure internal port
const LICENSE_DIR    = path.join(os.homedir(), '.wangari');
const LICENSE_FILE   = path.join(LICENSE_DIR, '.lic');
const GRACE_MS       = 14 * 24 * 60 * 60 * 1000; // 14-day offline grace
const LICENSE_SERVER = 'https://license.wangari.app';

// ─────────────────────────────────────────────────────────────────────────────
// HARDWARE FINGERPRINT  (MAC + CPU + Disk serial)
// ─────────────────────────────────────────────────────────────────────────────
function getHardwareFingerprint() {
  try {
    // Primary MAC address
    const nets = os.networkInterfaces();
    let mac = 'nomac';
    for (const iface of Object.values(nets)) {
      for (const info of iface) {
        if (!info.internal && info.mac && info.mac !== '00:00:00:00:00:00') {
          mac = info.mac;
          break;
        }
      }
      if (mac !== 'nomac') break;
    }

    const cpuModel = os.cpus()[0]?.model ?? 'unknown-cpu';
    const hostname  = os.hostname();
    const platform  = os.platform();

    // Windows: disk serial via PowerShell (wmic removed in newer Windows 11)
    let diskSerial = 'nodisk';
    try {
      if (platform === 'win32') {
        const out = execSync(
          'powershell -NoProfile -NonInteractive -Command "(Get-WmiObject Win32_DiskDrive | Select-Object -First 1 SerialNumber).SerialNumber"',
          { timeout: 4000 }
        ).toString().trim();
        diskSerial = out || 'nodisk';
      }
    } catch (_) { /* ignore — fingerprint works without disk serial */ }

    const raw = [mac, cpuModel, hostname, diskSerial].join('||');
    return crypto.createHash('sha256').update(raw).digest('hex');
  } catch (e) {
    console.error('[fingerprint] failed:', e.message);
    return crypto.createHash('sha256').update(os.hostname()).digest('hex');
  }
}

const HARDWARE_ID = getHardwareFingerprint();
console.log('[wangari] HW:', HARDWARE_ID.slice(0, 16) + '...');

// ─────────────────────────────────────────────────────────────────────────────
// LICENSE ENCRYPTION  (AES-256-GCM keyed to this machine's hardware ID)
// ─────────────────────────────────────────────────────────────────────────────
const SALT = 'WANGARI_AES_2025_SECURE_SALT';

function deriveKey(hwId) {
  return crypto.createHash('sha256').update(SALT + hwId).digest(); // 32 bytes
}

function encryptLicense(payload, hwId) {
  const key    = deriveKey(hwId);
  const iv     = crypto.randomBytes(12);
  const cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
  const enc    = Buffer.concat([cipher.update(JSON.stringify(payload), 'utf8'), cipher.final()]);
  const tag    = cipher.getAuthTag();
  return Buffer.concat([iv, tag, enc]).toString('base64');
}

function decryptLicense(data, hwId) {
  try {
    const buf = Buffer.from(data, 'base64');
    const iv  = buf.subarray(0, 12);
    const tag = buf.subarray(12, 28);
    const enc = buf.subarray(28);
    const key = deriveKey(hwId);
    const dec = crypto.createDecipheriv('aes-256-gcm', key, iv);
    dec.setAuthTag(tag);
    const plain = Buffer.concat([dec.update(enc), dec.final()]).toString('utf8');
    return JSON.parse(plain);
  } catch (_) {
    return null; // tampered or wrong machine
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// LICENSE PERSISTENCE
// ─────────────────────────────────────────────────────────────────────────────
function saveLicense(payload) {
  if (!fs.existsSync(LICENSE_DIR)) fs.mkdirSync(LICENSE_DIR, { recursive: true });
  fs.writeFileSync(LICENSE_FILE, encryptLicense(payload, HARDWARE_ID), 'utf8');
}

function loadLicense() {
  try {
    if (!fs.existsSync(LICENSE_FILE)) return null;
    return decryptLicense(fs.readFileSync(LICENSE_FILE, 'utf8'), HARDWARE_ID);
  } catch (_) {
    return null;
  }
}

function isLicenseValid(lic) {
  if (!lic || !lic.jwt || !lic.hardware_id || !lic.expires_at) return false;
  if (lic.hardware_id !== HARDWARE_ID) return false; // copied to different machine
  if (Date.now() > lic.expires_at) return false;     // offline grace expired
  return true;
}

// ─────────────────────────────────────────────────────────────────────────────
// LICENSE SERVER COMMUNICATION
// ─────────────────────────────────────────────────────────────────────────────
function callLicenseServer(endpoint, body) {
  return new Promise((resolve, reject) => {
    const payload = JSON.stringify(body);
    const url = new URL(endpoint, LICENSE_SERVER);
    const isSecure = url.protocol === 'https:';
    const opts = {
      hostname: url.hostname,
      port: url.port || (isSecure ? 443 : 80),
      path: url.pathname,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload),
        'X-Wangari-Version': APP_VERSION,
      },
      timeout: 10000,
    };

    const proto = isSecure ? https : http;
    const req = proto.request(opts, (res) => {
      let data = '';
      res.on('data', d => (data += d));
      res.on('end', () => {
        try {
          const parsed = JSON.parse(data);
          if (res.statusCode === 200) resolve(parsed);
          else reject(new Error(parsed.error || `HTTP ${res.statusCode}`));
        } catch (_) {
          reject(new Error('Invalid server response'));
        }
      });
    });

    req.on('timeout', () => { req.destroy(); reject(new Error('Connection timed out')); });
    req.on('error', reject);
    req.write(payload);
    req.end();
  });
}

async function activateLicense(licenseKey) {
  return callLicenseServer('/api/license/activate', {
    license_key: licenseKey,
    hardware_id: HARDWARE_ID,
    app_version: APP_VERSION,
  });
}

async function heartbeat() {
  const lic = loadLicense();
  if (!lic?.license_key) return;
  try {
    const fresh = await activateLicense(lic.license_key);
    saveLicense({
      license_key: lic.license_key,
      jwt:         fresh.jwt,
      hardware_id: HARDWARE_ID,
      expires_at:  Date.now() + GRACE_MS,
      plan:        fresh.plan ?? lic.plan,
    });
    console.log('[license] Heartbeat OK');
  } catch (e) {
    console.warn('[license] Offline heartbeat skipped:', e.message);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// PHP EMBEDDED SERVER
// ─────────────────────────────────────────────────────────────────────────────
let phpProcess = null;

function getPhpBinary() {
  const bundled = path.join(process.resourcesPath ?? __dirname, '..', 'php', 'php.exe');
  if (fs.existsSync(bundled)) return bundled;
  return 'php'; // rely on system PATH
}

function startPhpServer(jwtToken) {
  if (phpProcess) return;

  const appRoot = app.isPackaged
    ? path.join(process.resourcesPath, 'app')
    : path.join(__dirname, '..');

  const routerPHP = path.join(appRoot, 'router.php');

  phpProcess = spawn(getPhpBinary(), ['-S', `127.0.0.1:${PHP_PORT}`, routerPHP], {
    cwd: appRoot,
    env: {
      ...process.env,
      WANGARI_MODE:          'desktop',
      WANGARI_LICENSE_TOKEN: jwtToken,
      WANGARI_HW_ID:         HARDWARE_ID,
    },
    stdio: ['ignore', 'pipe', 'pipe'],
  });

  phpProcess.stdout.on('data', d => {
    const msg = d.toString().trim();
    if (msg) console.log('[php]', msg);
  });
  phpProcess.stderr.on('data', d => {
    const msg = d.toString().trim();
    if (msg && !msg.includes('Development Server started')) console.error('[php-err]', msg);
  });
  phpProcess.on('exit', code => {
    console.warn('[php] exited with code', code);
    phpProcess = null;
  });

  console.log(`[php] Server started → http://127.0.0.1:${PHP_PORT}`);
}

function stopPhpServer() {
  if (phpProcess) { phpProcess.kill(); phpProcess = null; }
}

function waitForPhp(retries = 40) {
  return new Promise((resolve, reject) => {
    const check = (n) => {
      const req = http.get(`http://127.0.0.1:${PHP_PORT}/status.php`, () => resolve());
      req.on('error', () => {
        if (n <= 0) return reject(new Error('PHP server did not start'));
        setTimeout(() => check(n - 1), 350);
      });
      req.end();
    };
    check(retries);
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// WINDOWS
// ─────────────────────────────────────────────────────────────────────────────
let mainWindow    = null;
let activationWin = null;

function createSplashWindow() {
  const win = new BrowserWindow({
    width: 480, height: 340, frame: false, center: true, resizable: false,
    webPreferences: { contextIsolation: true },
    backgroundColor: '#0D3320',
    alwaysOnTop: true,
  });
  win.loadFile(path.join(__dirname, 'splash.html'));
  return win;
}

function createActivationWindow() {
  activationWin = new BrowserWindow({
    width: 540, height: 580, frame: false, center: true, resizable: false,
    webPreferences: {
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js'),
    },
    backgroundColor: '#0D3320',
  });
  activationWin.loadFile(path.join(__dirname, 'activation.html'));
}

function createMainWindow(jwtToken) {
  mainWindow = new BrowserWindow({
    width: 1440, height: 880,
    minWidth: 1024, minHeight: 650,
    show: false,
    titleBarStyle: process.platform === 'darwin' ? 'hiddenInset' : 'default',
    webPreferences: {
      contextIsolation: true,
      nodeIntegration: false,
      preload: path.join(__dirname, 'preload.js'),
    },
    icon: path.join(__dirname, '..', 'Frontend', 'assets', 'img', 'logo.png'),
  });

  Menu.setApplicationMenu(null);

  mainWindow.loadURL(`http://127.0.0.1:${PHP_PORT}/Frontend/admin/dashboard.php`);
  mainWindow.once('ready-to-show', () => { mainWindow.show(); mainWindow.focus(); });
  mainWindow.on('closed', () => { mainWindow = null; stopPhpServer(); });

  // Heartbeat every 6 hours
  setInterval(heartbeat, 6 * 60 * 60 * 1000);
}

// ─────────────────────────────────────────────────────────────────────────────
// IPC HANDLERS
// ─────────────────────────────────────────────────────────────────────────────
ipcMain.handle('license:activate', async (_e, licenseKey) => {
  try {
    const response = await activateLicense(licenseKey.trim());
    saveLicense({
      license_key: licenseKey.trim(),
      jwt:         response.jwt,
      hardware_id: HARDWARE_ID,
      expires_at:  Date.now() + GRACE_MS,
      plan:        response.plan ?? 'basic',
    });
    return { ok: true, plan: response.plan, jwt: response.jwt };
  } catch (e) {
    return { ok: false, error: e.message };
  }
});

ipcMain.handle('license:status', () => {
  const lic = loadLicense();
  if (!lic) return { valid: false, reason: 'no_license' };
  if (!isLicenseValid(lic)) return { valid: false, reason: 'expired_or_mismatch' };
  return { valid: true, plan: lic.plan, expires_at: lic.expires_at, hw_prefix: HARDWARE_ID.slice(0, 8) };
});

ipcMain.on('license:confirmed', (_e, jwt) => {
  activationWin?.close();
  activationWin = null;
  const s = createSplashWindow();
  startPhpServer(jwt);
  waitForPhp().then(() => {
    s.close();
    createMainWindow(jwt);
  }).catch(err => {
    s.close();
    dialog.showErrorBox('Startup Error', 'Could not start the embedded server.\n\n' + err.message);
    app.quit();
  });
});

ipcMain.handle('app:open-url', (_e, url) => {
  if (url.startsWith('https://')) shell.openExternal(url);
});

// ─────────────────────────────────────────────────────────────────────────────
// APP LIFECYCLE
// ─────────────────────────────────────────────────────────────────────────────
app.whenReady().then(async () => {
  const splash = createSplashWindow();
  await new Promise(r => setTimeout(r, 1200)); // let splash render

  const lic   = loadLicense();
  const licOk = isLicenseValid(lic);

  if (!licOk) {
    splash.close();
    createActivationWindow();
    return;
  }

  startPhpServer(lic.jwt);
  try {
    await waitForPhp();
    splash.close();
    createMainWindow(lic.jwt);
    setTimeout(heartbeat, 5000); // background refresh shortly after open
  } catch (e) {
    splash.close();
    dialog.showErrorBox('Startup Error', 'Could not start the embedded PHP server.\n\n' + e.message);
    app.quit();
  }
});

app.on('window-all-closed', () => {
  stopPhpServer();
  if (process.platform !== 'darwin') app.quit();
});

app.on('activate', () => {
  if (!mainWindow && !activationWin) {
    const lic = loadLicense();
    if (isLicenseValid(lic)) {
      startPhpServer(lic.jwt);
      waitForPhp().then(() => createMainWindow(lic.jwt));
    }
  }
});

