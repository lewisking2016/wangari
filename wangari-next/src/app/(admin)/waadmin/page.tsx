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

  if (error) return <div className="rounded-xl border border-red-200 bg-badge-red-bg px-4 py-3 text-sm font-medium text-badge-red-text">{error}</div>;
  if (!data) return <div className="animate-pulse text-sm text-wangari-muted">Loading overview…</div>;

  const kpis = [
    { label: "MRR (active subs)", value: `KES ${data.totals.mrrKes.toLocaleString()}`, accent: "text-emerald-700" },
    { label: "Active subscriptions", value: data.totals.activeSubscriptions, accent: "" },
    { label: "Farms", value: data.totals.farms, accent: "" },
    { label: "Farm owners", value: data.totals.users, accent: "" },
    { label: "Workers", value: data.totals.workers, accent: "" },
    { label: "Open tickets", value: data.totals.openTickets, accent: data.totals.openTickets > 0 ? "text-badge-yellow-text" : "" },
  ];
  const maxSignups = Math.max(1, ...data.signups.map((s) => s.count));

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-wangari-heading">Overview</h1>
        <p className="mt-1 text-sm text-wangari-muted">Platform-wide health at a glance.</p>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-6">
        {kpis.map((k) => (
          <div key={k.label} className="rounded-xl border border-wangari-border bg-white p-4">
            <div className="text-[11px] font-medium uppercase tracking-wider text-wangari-subtle">{k.label}</div>
            <div className={`mt-1.5 text-2xl font-semibold ${k.accent || "text-wangari-heading"}`}>{k.value}</div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-wangari-border bg-white p-5">
          <h2 className="text-sm font-semibold text-wangari-heading">Signups — last 7 days</h2>
          <div className="mt-4 flex h-32 items-end gap-2">
            {data.signups.map((s) => (
              <div key={s.date} className="flex flex-1 flex-col items-center gap-1.5">
                <div className="text-[11px] text-wangari-muted">{s.count || ""}</div>
                <div
                  className="w-full rounded-t bg-wangari-green-500"
                  style={{ height: `${Math.max(4, (s.count / maxSignups) * 100)}%` }}
                />
                <div className="text-[10px] text-wangari-subtle">{s.date.slice(5)}</div>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-wangari-border bg-white p-5">
          <h2 className="text-sm font-semibold text-wangari-heading">Active subscriptions by plan</h2>
          <div className="mt-4 space-y-2.5">
            {Object.keys(data.byPlan).length === 0 && <div className="text-sm text-wangari-subtle">No active subscriptions yet.</div>}
            {Object.entries(data.byPlan).map(([plan, v]) => (
              <div key={plan} className="flex items-center justify-between text-sm">
                <span className="text-wangari-text">{plan}</span>
                <span className="text-wangari-muted">
                  {v.count} · KES {v.revenue.toLocaleString()}
                </span>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-wangari-border bg-white p-5">
          <h2 className="text-sm font-semibold text-wangari-heading">Recent signups</h2>
          <div className="mt-3 divide-y divide-wangari-border">
            {data.recentUsers.length === 0 && <div className="py-2 text-sm text-wangari-subtle">No signups in the last 7 days.</div>}
            {data.recentUsers.map((u) => (
              <div key={u.id} className="flex items-center justify-between py-2.5 text-sm">
                <div>
                  <div className="text-wangari-heading">{u.name}</div>
                  <div className="text-xs text-wangari-subtle">{u.email}</div>
                </div>
                <div className="text-xs text-wangari-subtle">{new Date(u.createdAt).toLocaleDateString()}</div>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-wangari-border bg-white p-5">
          <h2 className="text-sm font-semibold text-wangari-heading">Recent payments</h2>
          <div className="mt-3 divide-y divide-wangari-border">
            {data.recentPayments.length === 0 && <div className="py-2 text-sm text-wangari-subtle">No payments yet.</div>}
            {data.recentPayments.map((p) => (
              <div key={p.id} className="flex items-center justify-between py-2.5 text-sm">
                <div>
                  <div className="text-wangari-heading">{p.user?.name || p.user?.email || `User #${p.id}`}</div>
                  <div className="text-xs text-wangari-subtle">{p.planName} · {p.reference || "no reference"}</div>
                </div>
                <div className="font-medium text-emerald-700">KES {Number(p.amount).toLocaleString()}</div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
