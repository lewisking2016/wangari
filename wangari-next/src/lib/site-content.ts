"use client";

import * as React from "react";

/**
 * Fetches editable page content from the backend (managed in the super-admin
 * Website module). Returns null data while loading / on failure — callers
 * render their hardcoded fallbacks in that case, so the marketing site can
 * never be taken down by missing or malformed content.
 */
export function useSiteContent<T>(page: "pricing" | "contact"): { data: T | null; updatedAt: string | null } {
  const [data, setData] = React.useState<T | null>(null);
  const [updatedAt, setUpdatedAt] = React.useState<string | null>(null);

  React.useEffect(() => {
    const base = process.env.NEXT_PUBLIC_BACKEND_URL || "https://api.wangari.imeantech.com";
    let cancelled = false;
    fetch(`${base}/api/site-content/${page}`)
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
      .then((j) => {
        if (!cancelled && j?.data) {
          setData(j.data as T);
          setUpdatedAt(j.updatedAt ?? null);
        }
      })
      .catch(() => {
        /* fallbacks render */
      });
    return () => {
      cancelled = true;
    };
  }, [page]);

  return { data, updatedAt };
}
