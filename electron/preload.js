/**
 * Wangari Preload — Secure IPC bridge (contextBridge)
 * Only whitelisted functions are exposed to the renderer.
 */
'use strict';

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('wangari', {
  // License
  activate: (key) => ipcRenderer.invoke('license:activate', key),
  licenseStatus: () => ipcRenderer.invoke('license:status'),
  confirmActivation: (jwt) => ipcRenderer.send('license:confirmed', jwt),
  openUrl: (url) => ipcRenderer.invoke('app:open-url', url),

  // Offline Sync
  syncStatus: () => ipcRenderer.invoke('sync:status'),
  syncPush: (action) => ipcRenderer.invoke('sync:push', action),
  syncPull: () => ipcRenderer.invoke('sync:pull'),
  onSyncComplete: (cb) => ipcRenderer.on('sync:complete', (_e, data) => cb(data)),
});

