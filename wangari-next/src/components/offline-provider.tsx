"use client";

import * as React from "react";
import { WifiOff, CloudUpload, Loader2 } from "lucide-react";
import { queueSize, flushQueue, oldestQueuedMinutes } from "@/lib/offline-queue";
import { API_BASE } from "@/lib/api-client";
import { getToken } from "@/lib/auth-client";

/**
 * Registers the offline service worker, shows a connectivity banner, and
 * flushes the offline write queue whenever the device is back online.
 */
export function OfflineProvider({ children }: { children: React.ReactNode }) {
  const [online, setOnline] = React.useState(true);
  const [pending, setPending] = React.useState(0);
  const [syncing, setSyncing] = React.useState(false);
  const [justSynced, setJustSynced] = React.useState<string | null>(null);

  const refreshCount = React.useCallback(() => setPending(queueSize()), []);

  const sync = React.useCallback(async () => {
    if (queueSize() === 0 || !navigator.onLine) return;
    setSyncing(true);
    try {
      const { sent, failed } = await flushQueue(async (path, method, body, clientId) => {
        const res = await fetch(`${API_BASE}${path}`, {
          method,
          headers: {
            "Content-Type": "application/json",
            ...(getToken() ? { Authorization: `Bearer ${getToken()}` } : {}),
          },
          body: JSON.stringify({ ...(body as object), clientId }),
        });
        if (!res.ok) {
          const data = await res.json().catch(() => ({}));
          const err = new Error(data.error || `Sync failed: ${res.status}`) as Error & { status?: number };
          err.status = res.status;
          throw err;
        }
      });
      setPending(queueSize());
      if (sent > 0) {
        setJustSynced(`${sent} saved record${sent === 1 ? "" : "s"} synced successfully`);
        setTimeout(() => setJustSynced(null), 5000);
        window.dispatchEvent(new CustomEvent("wangari:data_synced"));
      }
      if (failed > 0) console.warn(`[offline-queue] ${failed} record(s) rejected by server and discarded`);
    } finally {
      setSyncing(false);
    }
  }, []);

  React.useEffect(() => {
    // Register the service worker (PWA offline support).
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("/sw.js").catch(() => {});
    }

    setOnline(navigator.onLine);
    refreshCount();

    const goOffline = () => setOnline(false);
    const goOnline = () => {
      setOnline(true);
      sync();
    };
    window.addEventListener("offline", goOffline);
    window.addEventListener("online", goOnline);
    window.addEventListener("wangari:write_queued", refreshCount);
    window.addEventListener("wangari:queue_changed", refreshCount as EventListener);

    // Also try a flush on load (records queued while the tab was closed).
    sync();

    return () => {
      window.removeEventListener("offline", goOffline);
      window.removeEventListener("online", goOnline);
      window.removeEventListener("wangari:write_queued", refreshCount);
      window.removeEventListener("wangari:queue_changed", refreshCount as EventListener);
    };
  }, [sync, refreshCount]);

  return (
    <>
      {children}
      {!online && (
        <div className="fixed inset-x-0 top-0 z-[100] flex items-center justify-center gap-2 bg-amber-500 py-1.5 text-xs font-semibold text-white shadow-md">
          <WifiOff className="h-3.5 w-3.5" />
          You&apos;re offline — data is saved on your device
          {pending > 0 && ` · ${pending} record${pending === 1 ? "" : "s"} waiting to sync`}
          {oldestQueuedMinutes() >= 60 ? " · will sync automatically" : ""}
        </div>
      )}
      {online && syncing && (
        <div className="fixed inset-x-0 top-0 z-[100] flex items-center justify-center gap-2 bg-wangari-green-700 py-1.5 text-xs font-semibold text-white shadow-md">
          <Loader2 className="h-3.5 w-3.5 animate-spin" />
          Syncing your saved records…
        </div>
      )}
      {online && !syncing && justSynced && (
        <div className="fixed inset-x-0 top-0 z-[100] flex items-center justify-center gap-2 bg-wangari-green-600 py-1.5 text-xs font-semibold text-white shadow-md">
          <CloudUpload className="h-3.5 w-3.5" />
          {justSynced}
        </div>
      )}
    </>
  );
}
