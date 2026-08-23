/**
 * Wangari Preload — Secure IPC bridge (contextBridge)
 * Exposes: license, offline DB, sync engine, connection status.
 * Only whitelisted functions reach the renderer.
 */
'use strict';

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('wangari', {
  // ── Auth ──
  login: (email, password) => ipcRenderer.invoke('auth:login', email, password),
  loginConfirmed: (user) => ipcRenderer.send('auth:login-confirmed', user),
  // ── License ──
  activate: (key) => ipcRenderer.invoke('license:activate', key),
  licenseStatus: () => ipcRenderer.invoke('license:status'),
  confirmActivation: (jwt) => ipcRenderer.send('license:confirmed', jwt),
  openUrl: (url) => ipcRenderer.invoke('app:open-url', url),

  // ── Offline Database ──
  db: {
    getStats:    ()       => ipcRenderer.invoke('db:getStats'),
    getDashboard: ()      => ipcRenderer.invoke('db:getDashboard'),
    query:       (opts)   => ipcRenderer.invoke('db:query', opts),
    insert:      (opts)   => ipcRenderer.invoke('db:insert', opts),
    update:      (opts)   => ipcRenderer.invoke('db:update', opts),
    remove:      (opts)   => ipcRenderer.invoke('db:delete', opts),
  },

  // ── Sync Engine ──
  sync: {
    status: ()            => ipcRenderer.invoke('sync:status'),
    push:   (action)      => ipcRenderer.invoke('sync:push', action),
    pull:   ()            => ipcRenderer.invoke('sync:pull'),
    forceFull: ()         => ipcRenderer.invoke('sync:forceFull'),
    onComplete: (cb)      => ipcRenderer.on('sync:complete', (_e, data) => cb(data)),
    onStatusChange: (cb)  => ipcRenderer.on('sync:status-change', (_e, status) => cb(status)),
  },

  // Legacy compatibility
  syncStatus: () => ipcRenderer.invoke('sync:status'),
  syncPush: (action) => ipcRenderer.invoke('sync:push', action),
  syncPull: () => ipcRenderer.invoke('sync:pull'),
  onSyncComplete: (cb) => ipcRenderer.on('sync:complete', (_e, data) => cb(data)),
});
