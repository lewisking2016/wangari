"use client";

import * as React from "react";
import { adminApi } from "@/lib/admin-client";

interface Overview {
  totals: { farms: number; users: number; workers: number; activeSubscriptions: number; mrrKes: number; openTickets: number };
  byPlan: Record<string, { count: number; revenue: number }>;
  signups: { date: string; count: number }[];
  recentUsers: { id: number; name: string; email: string; createdAt: string }[];
  recentPayments: { id: number; planName: string; amount: string; startsAt: string; reference: string | null; user: { name: string; email: string } }[];
}

export default function AdminOverviewPage() {
  const [data, setData] = React.useState<Overview | null>(null);
  const [error, setError] = React.useState("");

  React.useEffect(() => {
    adminApi.get<Overview>("/overview").then(setData).catch((e) => setError(e.message));
  }, []);

  if (error) return <div className="rounded-xl border border-red-900/60 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>;
  if (!data) return <div className="animate-pulse text-sm text-slate-400">Loading overview…</div>;

  const kpis = [
    { label: "MRR (active subs)", value: `KES ${data.totals.mrrKes.toLocaleString()}`, accent: "text-emerald-400" },
    { label: "Active subscriptions", value: data.totals.activeSubscriptions, accent: "" },
    { label: "Farms", value: data.totals.farms, accent: "" },
    { label: "Farm owners", value: data.totals.users, accent: "" },
    { label: "Workers", value: data.totals.workers, accent: "" },
    { label: "Open tickets", value: data.totals.openTickets, accent: data.totals.openTickets > 0 ? "text-amber-400" : "" },
  ];
  const maxSignups = Math.max(1, ...data.signups.map((s) => s.count));

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-white">Overview</h1>
        <p className="mt-1 text-sm text-slate-400">Platform-wide health at a glance.</p>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-6">
        {kpis.map((k) => (
          <div key={k.label} className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <div className="text-[11px] font-medium uppercase tracking-wider text-slate-500">{k.label}</div>
            <div className={`mt-1.5 text-2xl font-semibold ${k.accent || "text-white"}`}>{k.value}</div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
          <h2 className="text-sm font-semibold text-white">Signups — last 7 days</h2>
          <div className="mt-4 flex h-32 items-end gap-2">
            {data.signups.map((s) => (
              <div key={s.date} className="flex flex-1 flex-col items-center gap-1.5">
                <div className="text-[11px] text-slate-400">{s.count || ""}</div>
                <div
                  className="w-full rounded-t bg-emerald-500/70"
                  style={{ height: `${Math.max(4, (s.count / maxSignups) * 100)}%` }}
                />
                <div className="text-[10px] text-slate-500">{s.date.slice(5)}</div>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
          <h2 className="text-sm font-semibold text-white">Active subscriptions by plan</h2>
          <div className="mt-4 space-y-2.5">
            {Object.keys(data.byPlan).length === 0 && <div className="text-sm text-slate-500">No active subscriptions yet.</div>}
            {Object.entries(data.byPlan).map(([plan, v]) => (
              <div key={plan} className="flex items-center justify-between text-sm">
                <span className="text-slate-300">{plan}</span>
                <span className="text-slate-400">
                  {v.count} · KES {v.revenue.toLocaleString()}
                </span>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
          <h2 className="text-sm font-semibold text-white">Recent signups</h2>
          <div className="mt-3 divide-y divide-slate-800">
            {data.recentUsers.length === 0 && <div className="py-2 text-sm text-slate-500">No signups in the last 7 days.</div>}
            {data.recentUsers.map((u) => (
              <div key={u.id} className="flex items-center justify-between py-2.5 text-sm">
                <div>
                  <div className="text-slate-200">{u.name}</div>
                  <div className="text-xs text-slate-500">{u.email}</div>
                </div>
                <div className="text-xs text-slate-500">{new Date(u.createdAt).toLocaleDateString()}</div>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
          <h2 className="text-sm font-semibold text-white">Recent payments</h2>
          <div className="mt-3 divide-y divide-slate-800">
            {data.recentPayments.length === 0 && <div className="py-2 text-sm text-slate-500">No payments yet.</div>}
            {data.recentPayments.map((p) => (
              <div key={p.id} className="flex items-center justify-between py-2.5 text-sm">
                <div>
                  <div className="text-slate-200">{p.user?.name || p.user?.email || `User #${p.id}`}</div>
                  <div className="text-xs text-slate-500">{p.planName} · {p.reference || "no reference"}</div>
                </div>
                <div className="font-medium text-emerald-400">KES {Number(p.amount).toLocaleString()}</div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
