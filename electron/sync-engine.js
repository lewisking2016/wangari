/**
 * Wangari Desktop — Bi-directional Sync Engine
 * Handles push/pull between local SQLite and remote MySQL.
 * Conflict resolution: last-write-wins with server priority.
 * Queue-based: offline changes are queued and synced when online.
 */
'use strict';

const https = require('https');
const http = require('http');
const { URL } = require('url');
const database = require('./database');

const SYNC_SERVER = 'https://wangari.imeantech.com';
const SYNC_ENDPOINT = '/api/v2.php';
const SYNC_TABLES = ['users', 'products', 'animals', 'inventory', 'orders', 'order_items', 'financial_records', 'tasks', 'lpos'];
const PULL_INTERVAL = 5 * 60 * 1000;  // 5 minutes
const PUSH_INTERVAL = 2 * 60 * 1000;  // 2 minutes

let pushTimer = null;
let pullTimer = null;
let isSyncing = false;
let onStatusChange = null;
let mainWindow = null;

// ─────────────────────────────────────────────────────────────────────────────
// HTTP HELPER
// ─────────────────────────────────────────────────────────────────────────────

function httpRequest(url, options = {}) {
  return new Promise((resolve, reject) => {
    const urlObj = new URL(url);
    const isSecure = urlObj.protocol === 'https:';
    const payload = options.body ? JSON.stringify(options.body) : null;

    const reqOpts = {
      hostname: urlObj.hostname,
      port: urlObj.port || (isSecure ? 443 : 80),
      path: urlObj.pathname + urlObj.search,
      method: options.method || 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Wangari-Client': 'desktop',
        'X-Wangari-Version': '1.1.0',
        ...options.headers,
      },
      timeout: options.timeout || 15000,
    };

    if (payload) {
      reqOpts.headers['Content-Length'] = Buffer.byteLength(payload);
    }

    const proto = isSecure ? https : http;
    const req = proto.request(reqOpts, (res) => {
      let data = '';
      res.on('data', d => (data += d));
      res.on('end', () => {
        try {
          const parsed = JSON.parse(data);
          resolve({ status: res.statusCode, data: parsed });
        } catch (e) {
          resolve({ status: res.statusCode, data: data, raw: true });
        }
      });
    });

    req.on('timeout', () => { req.destroy(); reject(new Error('Request timed out')); });
    req.on('error', reject);
    if (payload) req.write(payload);
    req.end();
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// ONLINE DETECTION
// ─────────────────────────────────────────────────────────────────────────────

async function isOnline() {
  try {
    const res = await httpRequest(`${SYNC_SERVER}/api/health.php`, { timeout: 5000 });
    return res.status === 200;
  } catch (e) {
    return false;
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// PUSH (Local → Server)
// ─────────────────────────────────────────────────────────────────────────────

async function pushChanges() {
  if (isSyncing) return { pushed: 0, errors: 0, skipped: true };
  isSyncing = true;
  notifyStatus('syncing');

  let totalPushed = 0;
  let totalErrors = 0;

  try {
    for (const table of SYNC_TABLES) {
      // Push unsynced local changes
      const unsynced = database.getUnsynced(table);
      if (unsynced.length === 0) continue;

      console.log(`[sync] Pushing ${unsynced.length} ${table} records...`);

      const payload = {
        action: 'bulk_push',
        table: table,
        records: unsynced.map(r => ({
          ...r,
          _sync_id: r._sync_id || `local_${r.id}_${Date.now()}`,
        })),
        hardware_id: database.getDatabase().pragma('user_version', { simple: true }) || 'desktop',
      };

      try {
        const res = await httpRequest(`${SYNC_SERVER}${SYNC_ENDPOINT}?module=sync&action=push`, {
          method: 'POST',
          body: payload,
          timeout: 30000,
        });

        if (res.status >= 200 && res.status < 300 && res.data?.success) {
          const ids = unsynced.map(r => r.id);
          database.markSynced(table, ids);
          totalPushed += ids.length;
          console.log(`[sync] Pushed ${ids.length} ${table} records`);
        } else {
          console.warn(`[sync] Push rejected for ${table}:`, res.data?.error || res.status);
          totalErrors += unsynced.length;
        }
      } catch (e) {
        console.warn(`[sync] Push failed for ${table}:`, e.message);
        totalErrors += unsynced.length;
      }

      // Push soft-deleted records
      const deleted = database.getDeleted(table);
      if (deleted.length > 0) {
        try {
          await httpRequest(`${SYNC_SERVER}${SYNC_ENDPOINT}?module=sync&action=delete`, {
            method: 'POST',
            body: {
              table: table,
              ids: deleted.map(r => r.id),
              hardware_id: 'desktop',
            },
          });
          database.markDeletedSynced(table, deleted.map(r => r.id));
          console.log(`[sync] Deleted ${deleted.length} ${table} records on server`);
        } catch (e) {
          console.warn(`[sync] Delete sync failed for ${table}:`, e.message);
        }
      }

      // Update sync metadata
      database.setSyncMeta(table, {
        last_push: new Date().toISOString(),
        last_pull: database.getSyncMeta(table)?.last_pull || null,
        server_version: database.getSyncMeta(table)?.server_version || 0,
        local_version: Date.now(),
      });
    }
  } catch (e) {
    console.error('[sync] Push cycle error:', e.message);
  }

  isSyncing = notifyStatus(totalPushed > 0 ? 'synced' : 'idle');
  return { pushed: totalPushed, errors: totalErrors };
}

// ─────────────────────────────────────────────────────────────────────────────
// PULL (Server → Local)
// ─────────────────────────────────────────────────────────────────────────────

async function pullChanges() {
  if (isSyncing) return { pulled: 0, errors: 0, skipped: true };
  isSyncing = true;
  notifyStatus('syncing');

  let totalPulled = 0;

  try {
    for (const table of SYNC_TABLES) {
      const meta = database.getSyncMeta(table);
      const lastPull = meta?.last_pull || '1970-01-01T00:00:00Z';

      try {
        const res = await httpRequest(`${SYNC_SERVER}${SYNC_ENDPOINT}?module=sync&action=pull`, {
          method: 'POST',
          body: {
            table: table,
            since: lastPull,
            limit: 500,
            hardware_id: 'desktop',
          },
          timeout: 30000,
        });

        if (res.status >= 200 && res.status < 300 && res.data?.success && Array.isArray(res.data.records)) {
          const records = res.data.records;
          if (records.length > 0) {
            const result = database.bulkUpsert(table, records, true);
            totalPulled += result.inserted + result.updated;
            console.log(`[sync] Pulled ${records.length} ${table} records (inserted: ${result.inserted}, updated: ${result.updated})`);
          }

          database.setSyncMeta(table, {
            last_pull: new Date().toISOString(),
            last_push: meta?.last_push || null,
            server_version: res.data.version || 0,
            local_version: meta?.local_version || 0,
          });
        }
      } catch (e) {
        console.warn(`[sync] Pull failed for ${table}:`, e.message);
      }
    }
  } catch (e) {
    console.error('[sync] Pull cycle error:', e.message);
  }

  notifyStatus(totalPulled > 0 ? 'updated' : 'idle');
  return { pulled: totalPulled };
}

// ─────────────────────────────────────────────────────────────────────────────
// FULL SYNC (Push then Pull)
// ─────────────────────────────────────────────────────────────────────────────

async function fullSync() {
  console.log('[sync] Starting full sync cycle...');
  const pushResult = await pushChanges();
  const pullResult = await pullChanges();
  const result = { ...pushResult, ...pullResult, timestamp: Date.now() };

  // Notify renderer
  if (mainWindow && !mainWindow.isDestroyed()) {
    mainWindow.webContents.send('sync:complete', result);
  }

  console.log(`[sync] Full sync complete: pushed ${result.pushed}, pulled ${result.pulled}, errors ${result.errors || 0}`);
  return result;
}

// ─────────────────────────────────────────────────────────────────────────────
// STATUS NOTIFICATION
// ─────────────────────────────────────────────────────────────────────────────

function notifyStatus(status) {
  if (mainWindow && !mainWindow.isDestroyed()) {
    mainWindow.webContents.send('sync:status-change', status);
  }
  if (onStatusChange) onStatusChange(status);
  return status;
}

// ─────────────────────────────────────────────────────────────────────────────
// SERVICE LIFECYCLE
// ─────────────────────────────────────────────────────────────────────────────

function start(mainWin) {
  mainWindow = mainWin;

  // Initial sync after 3 seconds
  setTimeout(() => {
    fullSync().catch(e => console.warn('[sync] Initial sync failed:', e.message));
  }, 3000);

  // Push every 2 minutes
  pushTimer = setInterval(() => {
    pushChanges().catch(e => console.warn('[sync] Push cycle failed:', e.message));
  }, PUSH_INTERVAL);

  // Pull every 5 minutes
  pullTimer = setInterval(() => {
    pullChanges().catch(e => console.warn('[sync] Pull cycle failed:', e.message));
  }, PULL_INTERVAL);

  console.log('[sync] Engine started (push: 2min, pull: 5min)');
}

function stop() {
  if (pushTimer) { clearInterval(pushTimer); pushTimer = null; }
  if (pullTimer) { clearInterval(pullTimer); pullTimer = null; }
  mainWindow = null;
  console.log('[sync] Engine stopped');
}

function setStatusCallback(cb) {
  onStatusChange = cb;
}

// ─────────────────────────────────────────────────────────────────────────────
// EXPORTS
// ─────────────────────────────────────────────────────────────────────────────

module.exports = {
  start,
  stop,
  isOnline,
  pushChanges,
  pullChanges,
  fullSync,
  setStatusCallback,
  SYNC_TABLES,
};
