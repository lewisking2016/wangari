"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface SystemInfo {
  status: string;
  dbLatencyMs: number;
  counts: { farms: number; users: number; subs: number };
  uptimeSec: number;
  nodeVersion: string;
}

export default function AdminSystemPage() {
  const [info, setInfo] = React.useState<SystemInfo | null>(null);
  const [error, setError] = React.useState("");

  const load = React.useCallback(() => {
    adminApi.get<SystemInfo>("/system").then(setInfo).catch((e) => setError(e.message));
  }, []);
  React.useEffect(() => {
    load();
    const t = setInterval(load, 30_000); // refresh every 30s
    return () => clearInterval(t);
  }, [load]);

  if (error) return <div className="rounded-xl border border-red-200 bg-badge-red-bg px-4 py-3 text-sm font-medium text-badge-red-text">{error}</div>;
  if (!info) return <div className="animate-pulse text-sm text-wangari-muted">Checking system health…</div>;

  const dbHealthy = info.dbLatencyMs < 500;
  const uptimeDays = (info.uptimeSec / 86400).toFixed(1);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-wangari-heading">System Health</h1>
          <p className="mt-1 text-sm text-wangari-muted">Live API status — auto-refreshes every 30 seconds.</p>
        </div>
        <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ${
          info.status === "ok" ? "bg-wangari-green-50 text-wangari-green-800 border border-wangari-green-200" : "bg-badge-red-bg text-badge-red-text"
        }`}>
          <span className={`h-1.5 w-1.5 rounded-full ${info.status === "ok" ? "bg-emerald-500" : "bg-red-500"}`} />
          {info.status === "ok" ? "Operational" : "Degraded"}
        </span>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div className="rounded-xl border border-wangari-border bg-white p-4">
          <div className="text-[11px] font-medium uppercase tracking-wider text-wangari-subtle">Database latency</div>
          <div className={`mt-1.5 text-2xl font-semibold ${dbHealthy ? "text-emerald-700" : "text-badge-yellow-text"}`}>{info.dbLatencyMs} ms</div>
        </div>
        <div className="rounded-xl border border-wangari-border bg-white p-4">
          <div className="text-[11px] font-medium uppercase tracking-wider text-wangari-subtle">API uptime</div>
          <div className="mt-1.5 text-2xl font-semibold text-wangari-heading">{uptimeDays} d</div>
        </div>
        <div className="rounded-xl border border-wangari-border bg-white p-4">
          <div className="text-[11px] font-medium uppercase tracking-wider text-wangari-subtle">Node</div>
          <div className="mt-1.5 text-2xl font-semibold text-wangari-heading">{info.nodeVersion}</div>
        </div>
        <div className="rounded-xl border border-wangari-border bg-white p-4">
          <div className="text-[11px] font-medium uppercase tracking-wider text-wangari-subtle">Records</div>
          <div className="mt-1.5 text-sm text-wangari-text">{info.counts.farms} farms · {info.counts.users} users · {info.counts.subs} subs</div>
        </div>
      </div>
    </div>
  );
}
