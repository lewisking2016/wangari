/**
 * Wangari Preload — Secure IPC bridge (contextBridge)
 * Only whitelisted functions are exposed to the renderer.
 */
'use strict';

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('wangari', {
  /** Activate a license key against the license server */
  activate: (key) => ipcRenderer.invoke('license:activate', key),

  /** Get current license validity status */
  licenseStatus: () => ipcRenderer.invoke('license:status'),

  /** Signal main process that activation succeeded and app should open */
  confirmActivation: (jwt) => ipcRenderer.send('license:confirmed', jwt),

  /** Open an external HTTPS URL in the system browser */
  openUrl: (url) => ipcRenderer.invoke('app:open-url', url),
});

