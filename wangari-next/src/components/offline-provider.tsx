"use client";

import * as React from "react";
import { WifiOff } from "lucide-react";

/**
 * Registers the offline service worker and shows a slim banner when the
 * device loses connectivity. Farmers on flaky rural connections instantly
 * see whether they're working offline (cached data) or live.
 */
export function OfflineProvider({ children }: { children: React.ReactNode }) {
  const [online, setOnline] = React.useState(true);

  React.useEffect(() => {
    // Register the service worker (PWA offline support).
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("/sw.js").catch(() => {});
    }

    setOnline(navigator.onLine);
    const goOffline = () => setOnline(false);
    const goOnline = () => setOnline(true);
    window.addEventListener("offline", goOffline);
    window.addEventListener("online", goOnline);
    return () => {
      window.removeEventListener("offline", goOffline);
      window.removeEventListener("online", goOnline);
    };
  }, []);

  return (
    <>
      {children}
      {!online && (
        <div className="fixed inset-x-0 top-0 z-[100] flex items-center justify-center gap-2 bg-amber-500 py-1.5 text-xs font-semibold text-white shadow-md">
          <WifiOff className="h-3.5 w-3.5" />
          You&apos;re offline — showing saved data. Changes will need a connection.
        </div>
      )}
    </>
  );
}
