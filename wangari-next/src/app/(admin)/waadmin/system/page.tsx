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

  if (error) return <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>;
  if (!info) return <div className="animate-pulse text-sm text-slate-400">Checking system health…</div>;

  const dbHealthy = info.dbLatencyMs < 500;
  const uptimeDays = (info.uptimeSec / 86400).toFixed(1);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-white">System Health</h1>
          <p className="mt-1 text-sm text-slate-400">Live API status — auto-refreshes every 30 seconds.</p>
        </div>
        <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ${
          info.status === "ok" ? "bg-emerald-500/15 text-emerald-400" : "bg-red-500/15 text-red-400"
        }`}>
          <span className={`h-1.5 w-1.5 rounded-full ${info.status === "ok" ? "bg-emerald-400" : "bg-red-400"}`} />
          {info.status === "ok" ? "Operational" : "Degraded"}
        </span>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
          <div className="text-[11px] font-medium uppercase tracking-wider text-slate-500">Database latency</div>
          <div className={`mt-1.5 text-2xl font-semibold ${dbHealthy ? "text-emerald-400" : "text-amber-400"}`}>{info.dbLatencyMs} ms</div>
        </div>
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
          <div className="text-[11px] font-medium uppercase tracking-wider text-slate-500">API uptime</div>
          <div className="mt-1.5 text-2xl font-semibold text-white">{uptimeDays} d</div>
        </div>
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
          <div className="text-[11px] font-medium uppercase tracking-wider text-slate-500">Node</div>
          <div className="mt-1.5 text-2xl font-semibold text-white">{info.nodeVersion}</div>
        </div>
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
          <div className="text-[11px] font-medium uppercase tracking-wider text-slate-500">Records</div>
          <div className="mt-1.5 text-sm text-slate-300">{info.counts.farms} farms · {info.counts.users} users · {info.counts.subs} subs</div>
        </div>
      </div>
    </div>
  );
}
